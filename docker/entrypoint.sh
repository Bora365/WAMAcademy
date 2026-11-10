#!/bin/sh
set -eu

CONFIG=/var/www/html/config.php

if [ ! -f "$CONFIG" ]; then
    echo "config.php is not mounted at $CONFIG." >&2
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
# Moodle (component cache writes, DB connection). Evaluate a copy next to an
# empty lib/setup.php so only the $CFG assignments run.
checkdir=$(mktemp -d)
mkdir "$checkdir/lib"
cp "$CONFIG" "$checkdir/config.php"
: > "$checkdir/lib/setup.php"
chown -R www-data:www-data "$checkdir"

status=0
as_www_data php -d display_errors=stderr -r '
require $argv[1];
if (!isset($CFG) || !is_object($CFG)) {
    fwrite(STDERR, "config.php did not define \$CFG.\n");
    exit(1);
}
$host = isset($CFG->dbhost) ? strtolower(trim((string)$CFG->dbhost)) : "";
$socket = isset($CFG->dboptions["dbsocket"]) ? (string)$CFG->dboptions["dbsocket"] : "";
$root = isset($CFG->dataroot) ? (string)$CFG->dataroot : "";
if ($host === "") {
    fwrite(STDERR, "dbhost is empty in config.php.\n");
    exit(1);
}
if (preg_match("/^(localhost|127\.|::1$|\[::1\])/", $host)) {
    fwrite(STDERR, "dbhost={$host} points at this container, not the host MariaDB.\n");
    exit(1);
}
if ($socket !== "") {
    fwrite(STDERR, "dboptions[dbsocket]={$socket} is a local socket that does not exist in this container.\n");
    exit(1);
}
if ($root === "" || !is_dir($root) || !is_writable($root)) {
    fwrite(STDERR, "Moodle dataroot is missing or not writable by www-data: {$root}\n");
    exit(1);
}
' "$checkdir/config.php" || status=$?

rm -rf "$checkdir"
if [ "$status" -ne 0 ]; then
    exit "$status"
fi

exec apache2-foreground
