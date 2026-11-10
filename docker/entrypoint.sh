#!/bin/sh
set -eu

CONFIG=/var/www/html/config.php
if [ "${1:-}" = moodle-entrypoint ]; then
    shift
fi
MODE=${1:-apache}

case "$MODE" in
    apache|cron) ;;
    *)
        echo "Unknown mode '$MODE'. Use 'apache' (default) or 'cron'." >&2
        exit 1
        ;;
esac

if [ ! -f "$CONFIG" ]; then
    echo "$CONFIG is missing." >&2
    echo "Refusing to start so Moodle does not create a new database." >&2
    exit 1
fi

as_www_data() {
    setpriv --reuid=www-data --regid=www-data --init-groups "$@"
}

if ! as_www_data test -r "$CONFIG"; then
    echo "$CONFIG is not readable by www-data." >&2
    exit 1
fi

# config.php ends with require_once(__DIR__ . '/lib/setup.php'), which boots
# Moodle (component cache writes, DB connection, installer/upgrade redirects).
# Evaluate a copy next to an empty lib/setup.php so only the $CFG assignments
# run, then check the database read-only before Apache can serve anything.
checkdir=$(mktemp -d)
mkdir "$checkdir/lib"
cp "$CONFIG" "$checkdir/config.php"
: > "$checkdir/lib/setup.php"
chown -R www-data:www-data "$checkdir"

status=0
as_www_data php -d display_errors=stderr -r '
function fail(string $message): void {
    fwrite(STDERR, $message . "\n");
    exit(1);
}

require $argv[1];
if (!isset($CFG) || !is_object($CFG)) {
    fail("config.php did not define \$CFG.");
}

$wwwroot = (string)($CFG->wwwroot ?? "");
if (!preg_match("#^https?://[^/]+#", $wwwroot)) {
    fail("wwwroot is not set (MOODLE_WWWROOT), got \"{$wwwroot}\".");
}
foreach (["dbname" => "MOODLE_DB_NAME", "dbuser" => "MOODLE_DB_USER", "dbpass" => "MOODLE_DB_PASS"] as $key => $env) {
    if ((string)($CFG->$key ?? "") === "") {
        fail("{$key} is empty ({$env}).");
    }
}
$prefix = (string)($CFG->prefix ?? "");
if (!preg_match("/^[a-z0-9_]*$/i", $prefix)) {
    fail("Unexpected table prefix \"{$prefix}\".");
}

$host = strtolower(trim((string)($CFG->dbhost ?? "")));
$port = (int)($CFG->dboptions["dbport"] ?? 0);
$socket = (string)($CFG->dboptions["dbsocket"] ?? "");
if ($host === "") {
    fail("dbhost is empty.");
}
if ($socket !== "") {
    if ($host !== "localhost") {
        fail("dbsocket is only used when dbhost is \"localhost\", got \"{$host}\".");
    }
    if (!file_exists($socket) || filetype($socket) !== "socket") {
        fail("MariaDB socket {$socket} does not exist in this container. Bind-mount the host socket directory.");
    }
} else if (preg_match("/^(localhost|127\.|::1$|\[::1\])/", $host)) {
    fail("dbhost={$host} points at this container, not the host MariaDB. Set MOODLE_DB_SOCKET or a reachable host.");
}

$root = (string)($CFG->dataroot ?? "");
if ($root === "" || !is_dir($root) || !is_writable($root)) {
    fail("Moodle dataroot is missing or not writable by www-data: {$root}");
}

define("MOODLE_INTERNAL", true);
define("MATURITY_ALPHA", 50);
define("MATURITY_BETA", 100);
define("MATURITY_RC", 150);
define("MATURITY_STABLE", 200);
require "/var/www/html/public/version.php";
$codeversion = (float)$version;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli($host, $CFG->dbuser, $CFG->dbpass, $CFG->dbname, $port ?: 3306, $socket !== "" ? $socket : null);
    $stmt = $db->prepare("SELECT value FROM {$prefix}config WHERE name = ?");
    $name = "version";
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    $db->close();
} catch (mysqli_sql_exception $e) {
    fail("Database check failed: " . $e->getMessage() . "\nRefusing to start so Moodle does not run its installer.");
}
if (!$row) {
    fail("{$prefix}config has no version row. Refusing to start so Moodle does not run its installer.");
}
$dbversion = (float)$row[0];
if (abs($dbversion - $codeversion) > 0.000001 && getenv("MOODLE_ALLOW_VERSION_MISMATCH") !== "1") {
    fail("Database is at Moodle version {$row[0]} but this image contains {$version}. " .
        "Refusing to start so the web upgrade is not triggered against the shared database.");
}
echo "Moodle config OK: wwwroot={$wwwroot}, db={$CFG->dbname}@{$host}" . ($socket !== "" ? " via {$socket}" : "") .
    ", version={$row[0]}\n";
' "$checkdir/config.php" || status=$?

rm -rf "$checkdir"
if [ "$status" -ne 0 ]; then
    exit "$status"
fi

if [ "$MODE" = cron ]; then
    echo "Running Moodle cron every 60 seconds as www-data."
    exec setpriv --reuid=www-data --regid=www-data --init-groups sh -c '
        while :; do
            php /var/www/html/admin/cli/cron.php || echo "cron.php exited with $?" >&2
            sleep 60
        done
    '
fi

exec apache2-foreground
