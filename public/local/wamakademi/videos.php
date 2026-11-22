<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — kurs bazlı video yönetimi (liste, yayın, sıra).

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

require_login();

global $DB, $USER, $PAGE, $OUTPUT, $CFG;

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
$context = context_course::instance($course->id);

require_login($course, false);

if (!\local_wamakademi\access::can_manage_videos((int)$USER->id, (int)$course->id)) {
    throw new required_capability_exception(
        $context,
        'local/wamakademi:managevideos',
        'nopermissions',
        ''
    );
}

// Bölüm içi taşıma.
$move = optional_param('move', '', PARAM_ALPHA);
$movecmid = optional_param('cmid', 0, PARAM_INT);
if ($move !== '' && $movecmid > 0) {
    require_sesskey();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new moodle_exception('invalidrequest', 'error');
    }
    $direction = ($move === 'up') ? 'up' : (($move === 'down') ? 'down' : '');
    if ($direction === '') {
        throw new moodle_exception('invalidrequest', 'error');
    }
    $cm = get_coursemodule_from_id('resource', $movecmid, $course->id, false, MUST_EXIST);
    if (!\local_wamakademi\course_videos::is_mp4_resource($cm)) {
        throw new moodle_exception('invalidrequest', 'error');
    }
    \local_wamakademi\course_videos::move_in_section($course, $movecmid, $direction);
    rebuild_course_cache($course->id, true);
    redirect(
        new moodle_url('/local/wamakademi/videos.php', ['courseid' => (int)$course->id]),
        get_string('videosmoved', 'local_wamakademi'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$pageurl = new moodle_url('/local/wamakademi/videos.php', ['courseid' => (int)$course->id]);
$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('videostitle', 'local_wamakademi'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->add_body_class('wam-content');
$PAGE->requires->css(new moodle_url('/local/wamakademi/css/wam-content.css', [
    'v' => (string)@filemtime($CFG->dirroot . '/local/wamakademi/css/wam-content.css'),
]));

$videos = \local_wamakademi\course_videos::list_for_management($course);
$uploadurl = new moodle_url('/local/wamakademi/upload.php', ['courseid' => (int)$course->id]);
$sesskey = sesskey();

$formaction = $pageurl->out(false);
$publishurl = (new moodle_url('/local/wamakademi/publish.php'))->out(false);

$sections = [];
foreach ($videos as $row) {
    $skey = (string)$row['sectionnum'];
    if (!isset($sections[$skey])) {
        $sections[$skey] = [
            'sectionnum' => $row['sectionnum'],
            'sectionname' => $row['sectionname'],
            'videos' => [],
        ];
    }
    $row['formaction'] = $formaction;
    $row['courseid'] = (int)$course->id;
    $row['publishurl'] = $publishurl;
    $row['sesskey'] = $sesskey;
    $sections[$skey]['videos'][] = $row;
}

$templatecontext = [
    'coursename' => format_string($course->fullname),
    'courseurl' => (new moodle_url('/course/view.php', ['id' => (int)$course->id]))->out(false),
    'uploadurl' => $uploadurl->out(false),
    'uploadlabel' => get_string('menuuploadvideo', 'local_wamakademi'),
    'hasvideos' => !empty($videos),
    'sections' => array_values($sections),
    'emptytext' => get_string('videosempty', 'local_wamakademi'),
    'strings' => [
        'preview' => get_string('videospreview', 'local_wamakademi'),
        'publish' => get_string('previewpublish', 'local_wamakademi'),
        'unpublish' => get_string('previewunpublish', 'local_wamakademi'),
        'moveup' => get_string('videosmoveup', 'local_wamakademi'),
        'movedown' => get_string('videosmovedown', 'local_wamakademi'),
        'status' => get_string('videosstatus', 'local_wamakademi'),
        'actions' => get_string('videosactions', 'local_wamakademi'),
        'order' => get_string('videosorder', 'local_wamakademi'),
    ],
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_wamakademi/videos', $templatecontext);
echo $OUTPUT->footer();
