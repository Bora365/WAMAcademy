<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — video yayımlama / yayından kaldırma (POST + sesskey).
// GET ile işlem yapılmaz.

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new moodle_exception('invalidrequest', 'error');
}

require_sesskey();

global $DB, $USER;

$cmid = required_param('cmid', PARAM_INT);
$action = required_param('action', PARAM_ALPHA); // publish | unpublish
$wantsjson = optional_param('json', 0, PARAM_BOOL);

if (!in_array($action, ['publish', 'unpublish'], true) || $cmid <= 0) {
    if ($wantsjson) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => get_string('publishinvalid', 'local_wamakademi')]);
        exit;
    }
    throw new moodle_exception('invalidrequest', 'error');
}

$cm = get_coursemodule_from_id('resource', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
require_login($course, false);

if (!\local_wamakademi\access::can_manage_videos((int)$USER->id, (int)$course->id)) {
    if ($wantsjson) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => get_string('publishforbidden', 'local_wamakademi')]);
        exit;
    }
    throw new required_capability_exception(
        context_course::instance($course->id),
        'local/wamakademi:managevideos',
        'nopermissions',
        ''
    );
}

$visible = ($action === 'publish') ? 1 : 0;
set_coursemodule_visible($cmid, $visible, 1, true);

$redirect = new moodle_url('/local/wamakademi/videos.php', [
    'courseid' => (int)$course->id,
]);

$message = $visible
    ? get_string('publishsuccess', 'local_wamakademi')
    : get_string('unpublishsuccess', 'local_wamakademi');

if ($wantsjson) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'visible' => $visible,
        'message' => $message,
        'redirect' => $redirect->out(false),
    ]);
    exit;
}

redirect($redirect, $message, null, \core\output\notification::NOTIFY_SUCCESS);
