<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — site geneli Ödevler panosu.

require_once(__DIR__ . '/../../config.php');

require_login();

global $USER, $PAGE, $OUTPUT, $CFG;

$q = optional_param('q', '', PARAM_RAW_TRIMMED);
$courseid = optional_param('courseid', 0, PARAM_INT);

$PAGE->set_url(new moodle_url('/local/wamakademi/assignments.php', [
    'q' => $q !== '' ? $q : null,
    'courseid' => $courseid ?: null,
]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('assessboardassignstitle', 'local_wamakademi'));
$PAGE->set_heading('');
$PAGE->add_body_class('wam-assignments-body');
$PAGE->add_body_class('wam-assess-body');
$PAGE->requires->css(local_wamakademi_asset_url('/local/wamakademi/css/wam-assess.css'));

$data = \local_wamakademi\assessment_board::for_user((int)$USER->id, 'assign', $q, $courseid);

$filterurl = new moodle_url('/local/wamakademi/assignments.php');
$clearurl = new moodle_url('/local/wamakademi/assignments.php');

$templatecontext = [
    'pagetype' => 'assignments',
    'isassignments' => true,
    'isquizzes' => false,
    'title' => get_string('assessboardassignstitle', 'local_wamakademi'),
    'lead' => get_string('assessboardassignslead', 'local_wamakademi'),
    'filteraction' => $filterurl->out(false),
    'clearurl' => $clearurl->out(false),
    'q' => $q,
    'courseid' => $courseid,
    'courses' => $data['courses'],
    'hascoursefilter' => !empty($data['courses']),
    'summary' => $data['summary'],
    'hassummary' => ($data['summary']['total'] ?? 0) > 0,
    'rows' => $data['rows'],
    'hasrows' => !empty($data['rows']),
    'isteacherany' => !empty($data['isteacherany']),
    'emptytext' => get_string('assessboardemptyassign', 'local_wamakademi'),
    'quizzesurl' => (new moodle_url('/local/wamakademi/quizzes.php'))->out(false),
    'assignmentsurl' => (new moodle_url('/local/wamakademi/assignments.php'))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_wamakademi/assessment_board', $templatecontext);
echo $OUTPUT->footer();
