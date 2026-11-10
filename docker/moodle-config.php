<?php  // Moodle configuration file for the Docker image.

// All site-specific values come from the container environment so that no
// credentials end up in Git or in the image. A config.php bind-mounted over
// /var/www/html/config.php replaces this file entirely.

unset($CFG);
global $CFG;
$CFG = new stdClass();

$moodleenv = static function (string $name, ?string $default = null): ?string {
    $value = getenv($name);
    return ($value === false || $value === '') ? $default : $value;
};

$dbpass = $moodleenv('MOODLE_DB_PASS');
$dbpassfile = $moodleenv('MOODLE_DB_PASS_FILE');
if ($dbpass === null && $dbpassfile !== null && is_readable($dbpassfile)) {
    $dbpass = rtrim(file_get_contents($dbpassfile), "\r\n");
}

$CFG->dbtype    = $moodleenv('MOODLE_DB_TYPE', 'mariadb');
$CFG->dblibrary = 'native';
$CFG->dbhost    = $moodleenv('MOODLE_DB_HOST', 'localhost');
$CFG->dbname    = $moodleenv('MOODLE_DB_NAME');
$CFG->dbuser    = $moodleenv('MOODLE_DB_USER');
$CFG->dbpass    = $dbpass;
$CFG->prefix    = $moodleenv('MOODLE_DB_PREFIX', 'mdl_');
$CFG->dboptions = [
    'dbpersist' => 0,
    'dbport' => $moodleenv('MOODLE_DB_PORT', ''),
    'dbsocket' => $moodleenv('MOODLE_DB_SOCKET', ''),
    'dbcollation' => $moodleenv('MOODLE_DB_COLLATION', 'utf8mb4_unicode_ci'),
];

$CFG->wwwroot  = $moodleenv('MOODLE_WWWROOT');
$CFG->dataroot = $moodleenv('MOODLE_DATAROOT', '/var/moodledata');
// Compiled theme CSS and JS live here. Keep it inside the container so the
// live host Apache (different code) and this image never serve each other's
// cached assets from the shared dataroot.
$CFG->localcachedir = $moodleenv('MOODLE_LOCALCACHEDIR', '/var/cache/moodle-local');
$CFG->admin    = 'admin';
$CFG->routerconfigured = true;
$CFG->directorypermissions = 02777;

// The live host Apache and this container may share one host name, and
// browser cookies are not separated by port.
$CFG->sessioncookie = $moodleenv('MOODLE_SESSION_COOKIE', 'docker');

if ($moodleenv('MOODLE_SSLPROXY') === '1') {
    $CFG->sslproxy = true;
}

unset($moodleenv, $dbpass, $dbpassfile);

require_once(__DIR__ . '/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
