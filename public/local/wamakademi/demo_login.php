<?php
// This file is part of Moodle - http://moodle.org/
//
// Yerel demo öğrenci girişi — parola tarayıcıya yazılmaz.

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/authlib.php');

// Giriş yapılmamış oturumdan (login sayfası sesskey) çağrılır.
require_sesskey();

$guard = \local_wamakademi\demo_env::assert_safe();
if ($guard !== true) {
    throw new moodle_exception('nopermissions', 'error', '', $guard);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new moodle_exception('invalidrequest', 'error');
}

$username = required_param('username', PARAM_USERNAME);
$allowed = \local_wamakademi\demo_env::student_usernames();
if (!in_array($username, $allowed, true)) {
    throw new moodle_exception('nopermissions', 'error', '', 'İzin verilmeyen kullanıcı');
}

$reason = null;
$user = authenticate_user_login($username, \local_wamakademi\demo_env::DEMO_PASSWORD, false, $reason);
if (!$user) {
    throw new moodle_exception('invalidlogin', 'error');
}

complete_user_login($user);

$wantsurl = optional_param('wantsurl', '', PARAM_LOCALURL);
if ($wantsurl !== '') {
    redirect(new moodle_url($wantsurl));
}
redirect(new moodle_url('/local/wamakademi/index.php'));
