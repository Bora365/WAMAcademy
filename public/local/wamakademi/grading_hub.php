<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — ödev / sınav not yönetimi merkezi (Gelişmiş puanlama).

require_once(__DIR__ . '/../../config.php');

require_login();

$cmid = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($cmid);
$context = context_module::instance($cm->id);
$coursecontext = context_course::instance($course->id);

require_login($course, false, $cm);

$modname = $cm->modname;
if (!in_array($modname, ['assign', 'quiz'], true)) {
    throw new moodle_exception('invalidcoursemodule');
}

$canmanage = false;
if ($modname === 'assign') {
    $canmanage = has_capability('mod/assign:grade', $context)
        || has_capability('mod/assign:managegrades', $context)
        || has_capability('moodle/grade:managegradingforms', $context)
        || has_capability('mod/assign:manageoverrides', $context);
} else {
    $canmanage = has_capability('mod/quiz:grade', $context)
        || has_capability('mod/quiz:viewreports', $context)
        || has_capability('mod/quiz:manageoverrides', $context);
}

if (!$canmanage && !\local_wamakademi\access::can_view_reports((int)$USER->id, (int)$course->id)) {
    throw new required_capability_exception(
        $context,
        $modname === 'assign' ? 'mod/assign:grade' : 'mod/quiz:viewreports',
        'nopermissions',
        ''
    );
}

$PAGE->set_url(new moodle_url('/local/wamakademi/grading_hub.php', ['id' => $cm->id]));
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('gradinghubtitle', 'local_wamakademi', format_string($cm->name)));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->add_body_class('wam-report-body');
$PAGE->requires->css(local_wamakademi_asset_url('/local/wamakademi/css/wam-report.css'));

// Secondary sekme anahtarı (settingsnav rewire / ekleme → advgrading).
$PAGE->set_secondary_active_tab('advgrading');

$actions = [];

if ($modname === 'assign') {
    if (has_capability('mod/assign:grade', $context)) {
        $actions[] = [
            'title' => get_string('gradinghubsubmissions', 'local_wamakademi'),
            'desc' => get_string('gradinghubsubmissionsdesc', 'local_wamakademi'),
            'url' => (new moodle_url('/mod/assign/view.php', [
                'id' => $cm->id,
                'action' => 'grading',
            ]))->out(false),
            'primary' => true,
        ];
        $actions[] = [
            'title' => get_string('gradinghubgrader', 'local_wamakademi'),
            'desc' => get_string('gradinghubgraderdesc', 'local_wamakademi'),
            'url' => (new moodle_url('/mod/assign/view.php', [
                'id' => $cm->id,
                'action' => 'grader',
            ]))->out(false),
            'primary' => false,
        ];
        $actions[] = [
            'title' => get_string('gradinghubdownload', 'local_wamakademi'),
            'desc' => get_string('gradinghubdownloaddesc', 'local_wamakademi'),
            'url' => (new moodle_url('/mod/assign/view.php', [
                'id' => $cm->id,
                'action' => 'downloadall',
            ]))->out(false),
            'primary' => false,
        ];
    }
    if (has_capability('mod/assign:manageoverrides', $context)) {
        $actions[] = [
            'title' => get_string('gradinghuboverrides', 'local_wamakademi'),
            'desc' => get_string('gradinghuboverridesdesc', 'local_wamakademi'),
            'url' => (new moodle_url('/mod/assign/overrides.php', [
                'cmid' => $cm->id,
                'mode' => 'user',
            ]))->out(false),
            'primary' => false,
        ];
    }
    if (has_capability('moodle/grade:managegradingforms', $context)) {
        $actions[] = [
            'title' => get_string('gradinghubmethod', 'local_wamakademi'),
            'desc' => get_string('gradinghubmethoddesc', 'local_wamakademi'),
            'url' => (new moodle_url('/grade/grading/manage.php', [
                'contextid' => $context->id,
                'component' => 'mod_assign',
                'area' => 'submissions',
            ]))->out(false),
            'primary' => false,
        ];
    }
} else {
    if (has_capability('mod/quiz:viewreports', $context)) {
        $actions[] = [
            'title' => get_string('gradinghubquizresults', 'local_wamakademi'),
            'desc' => get_string('gradinghubquizresultsdesc', 'local_wamakademi'),
            'url' => (new moodle_url('/mod/quiz/report.php', [
                'id' => $cm->id,
                'mode' => 'overview',
            ]))->out(false),
            'primary' => true,
        ];
        $actions[] = [
            'title' => get_string('gradinghubquizresponses', 'local_wamakademi'),
            'desc' => get_string('gradinghubquizresponsesdesc', 'local_wamakademi'),
            'url' => (new moodle_url('/mod/quiz/report.php', [
                'id' => $cm->id,
                'mode' => 'responses',
            ]))->out(false),
            'primary' => false,
        ];
        $actions[] = [
            'title' => get_string('gradinghubquizstats', 'local_wamakademi'),
            'desc' => get_string('gradinghubquizstatsdesc', 'local_wamakademi'),
            'url' => (new moodle_url('/mod/quiz/report.php', [
                'id' => $cm->id,
                'mode' => 'statistics',
            ]))->out(false),
            'primary' => false,
        ];
    }
    if (has_capability('mod/quiz:grade', $context)) {
        $actions[] = [
            'title' => get_string('gradinghubquizmanual', 'local_wamakademi'),
            'desc' => get_string('gradinghubquizmanualdesc', 'local_wamakademi'),
            'url' => (new moodle_url('/mod/quiz/report.php', [
                'id' => $cm->id,
                'mode' => 'grading',
            ]))->out(false),
            'primary' => false,
        ];
    }
    if (has_capability('mod/quiz:manageoverrides', $context)) {
        $actions[] = [
            'title' => get_string('gradinghuboverrides', 'local_wamakademi'),
            'desc' => get_string('gradinghuboverridesdesc', 'local_wamakademi'),
            'url' => (new moodle_url('/mod/quiz/overrides.php', [
                'cmid' => $cm->id,
                'mode' => 'user',
            ]))->out(false),
            'primary' => false,
        ];
    }
}

if (has_capability('gradereport/grader:view', $coursecontext)
        || has_capability('moodle/grade:viewall', $coursecontext)) {
    $actions[] = [
        'title' => get_string('gradinghubgradebook', 'local_wamakademi'),
        'desc' => get_string('gradinghubgradebookdesc', 'local_wamakademi'),
        'url' => (new moodle_url('/grade/report/grader/index.php', [
            'id' => $course->id,
        ]))->out(false),
        'primary' => false,
    ];
}

if (\local_wamakademi\access::can_view_reports((int)$USER->id, (int)$course->id)) {
    $actions[] = [
        'title' => get_string('evalreportlink', 'local_wamakademi'),
        'desc' => get_string('gradinghubevaldesc', 'local_wamakademi'),
        'url' => (new moodle_url('/local/wamakademi/eval_report.php', [
            'courseid' => $course->id,
            'cmid' => $cm->id,
        ]))->out(false),
        'primary' => false,
    ];
    $actions[] = [
        'title' => get_string('gradinghubprogress', 'local_wamakademi'),
        'desc' => get_string('gradinghubprogressdesc', 'local_wamakademi'),
        'url' => (new moodle_url('/local/wamakademi/report_course.php', [
            'courseid' => $course->id,
            'tab' => $modname === 'assign' ? 'assignments' : 'participants',
        ]))->out(false),
        'primary' => false,
    ];
}

$activityurl = (new moodle_url('/mod/' . $modname . '/view.php', ['id' => $cm->id]))->out(false);

$templatecontext = [
    'activityname' => format_string($cm->name),
    'activityurl' => $activityurl,
    'coursename' => format_string($course->fullname),
    'courseurl' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
    'modlabel' => $modname === 'assign'
        ? get_string('modulename', 'assign')
        : get_string('modulename', 'quiz'),
    'lead' => get_string('gradinghublead', 'local_wamakademi'),
    'actions' => $actions,
    'hasactions' => !empty($actions),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_wamakademi/grading_hub', $templatecontext);
echo $OUTPUT->footer();
