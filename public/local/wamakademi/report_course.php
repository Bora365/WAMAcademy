<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — kurs ilerleme ve ödev raporu.

require_once(__DIR__ . '/../../config.php');

require_login();

$courseid = required_param('courseid', PARAM_INT);
$tab = optional_param('tab', 'participants', PARAM_ALPHA);
$useridfilter = optional_param('userid', 0, PARAM_INT);
$statusfilter = optional_param('status', '', PARAM_ALPHA);
$usersearch = optional_param('q', '', PARAM_RAW_TRIMMED);
$download = optional_param('download', 0, PARAM_BOOL);

$course = get_course($courseid);
$context = context_course::instance($course->id);

require_login($course);
if (!\local_wamakademi\access::can_view_reports((int)$USER->id, (int)$course->id)) {
    throw new required_capability_exception(
        $context,
        'local/wamakademi:viewreports',
        'nopermissions',
        ''
    );
}

if (!in_array($tab, ['participants', 'assignments'], true)) {
    $tab = 'participants';
}
if (!in_array($statusfilter, ['', 'assigned', 'active', 'completed', 'missing', 'draft', 'submitted', 'late', 'graded'], true)) {
    $statusfilter = '';
}

$PAGE->set_url(new moodle_url('/local/wamakademi/report_course.php', [
    'courseid' => $course->id,
    'tab' => $tab,
    'userid' => $useridfilter ?: null,
    'status' => $statusfilter !== '' ? $statusfilter : null,
    'q' => $usersearch !== '' ? $usersearch : null,
]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('reportcoursetitle', 'local_wamakademi', format_string($course->fullname)));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->add_body_class('wam-report-body');
$PAGE->requires->css(local_wamakademi_asset_url('/local/wamakademi/css/wam-report.css'));

$baseparams = [
    'courseid' => (int)$course->id,
    'tab' => $tab,
];
if ($useridfilter) {
    $baseparams['userid'] = $useridfilter;
}
if ($statusfilter !== '') {
    $baseparams['status'] = $statusfilter;
}
if ($usersearch !== '') {
    $baseparams['q'] = $usersearch;
}

if ($tab === 'assignments') {
    $data = \local_wamakademi\report::course_assignment_rows(
        (int)$course->id,
        $statusfilter,
        $usersearch,
        $useridfilter
    );
} else {
    $data = \local_wamakademi\report::course_participant_rows(
        $course,
        in_array($statusfilter, ['assigned', 'active', 'completed'], true) ? $statusfilter : '',
        $usersearch,
        $useridfilter
    );
}

if ($download) {
    require_sesskey();
    $filename = 'wam-report-course-' . $course->id . '-' . $tab . '-' . date('Ymd-His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    if ($tab === 'assignments') {
        fputcsv($out, [
            'userid', 'fullname', 'email', 'assignment', 'state', 'due', 'submitted', 'grade', 'late',
        ]);
        foreach ($data['rows'] as $row) {
            fputcsv($out, [
                $row['userid'],
                $row['fullname'],
                $row['email'],
                $row['assignmentname'],
                $row['statelabel'],
                $row['duedatefmt'],
                $row['submittedatfmt'],
                $row['grade'],
                $row['islate'] ? '1' : '0',
            ]);
        }
    } else {
        fputcsv($out, [
            'userid', 'fullname', 'email', 'progress', 'completed_lessons', 'lesson_count', 'status', 'latest_activity',
        ]);
        foreach ($data['rows'] as $row) {
            fputcsv($out, [
                $row['userid'],
                $row['fullname'],
                $row['email'],
                $row['progress'],
                $row['completedlessons'],
                $row['lessoncount'],
                $row['statuslabel'],
                $row['latestactivityfmt'],
            ]);
        }
        if ($useridfilter && !empty($data['rows'][0]['lessons'])) {
            fputcsv($out, []);
            fputcsv($out, ['cmid', 'lesson', 'progress', 'completed', 'watched_seconds', 'duration']);
            foreach ($data['rows'][0]['lessons'] as $lesson) {
                fputcsv($out, [
                    $lesson['cmid'],
                    $lesson['name'],
                    $lesson['progress'],
                    $lesson['completed'] ? '1' : '0',
                    $lesson['watchedseconds'],
                    $lesson['duration'],
                ]);
            }
        }
    }
    fclose($out);
    exit;
}

$detailuser = null;
$detaillessons = [];
if ($tab === 'participants' && $useridfilter > 0 && !empty($data['rows'])) {
    $detailuser = $data['rows'][0];
    $detaillessons = $detailuser['lessons'];
}

$filterurl = new moodle_url('/local/wamakademi/report_course.php');
$exporturl = new moodle_url('/local/wamakademi/report_course.php', array_merge($baseparams, [
    'download' => 1,
    'sesskey' => sesskey(),
]));

$statusoptions = [];
if ($tab === 'assignments') {
    foreach (['missing', 'draft', 'submitted', 'late', 'graded'] as $st) {
        $statusoptions[] = [
            'value' => $st,
            'label' => get_string('reportassign' . $st, 'local_wamakademi'),
            'selected' => $statusfilter === $st,
        ];
    }
} else {
    foreach (['assigned', 'active', 'completed'] as $st) {
        $statusoptions[] = [
            'value' => $st,
            'label' => \local_wamakademi\progress::status_label($st),
            'selected' => $statusfilter === $st,
        ];
    }
}

$templatecontext = [
    'coursename' => format_string($course->fullname),
    'courseurl' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
    'overviewurl' => (new moodle_url('/local/wamakademi/report_overview.php'))->out(false),
    'hasoverview' => \local_wamakademi\access::can_view_overview((int)$USER->id),
    'evalreporturl' => (new moodle_url('/local/wamakademi/eval_report.php', [
        'courseid' => $course->id,
    ]))->out(false),
    'hasevalreports' => true,
    'filteraction' => $filterurl->out(false),
    'courseid' => (int)$course->id,
    'tab' => $tab,
    'isparticipants' => $tab === 'participants',
    'isassignments' => $tab === 'assignments',
    'participantsurl' => (new moodle_url('/local/wamakademi/report_course.php', [
        'courseid' => $course->id,
        'tab' => 'participants',
    ]))->out(false),
    'assignmentsurl' => (new moodle_url('/local/wamakademi/report_course.php', [
        'courseid' => $course->id,
        'tab' => 'assignments',
    ]))->out(false),
    'usersearch' => $usersearch,
    'useridfilter' => $useridfilter,
    'statusoptions' => $statusoptions,
    'hasstatusfilter' => $statusfilter !== '',
    'exporturl' => $exporturl->out(false),
    'rows' => $data['rows'],
    'hasrows' => !empty($data['rows']),
    'summary' => $data['summary'] ?? null,
    'hassummary' => $tab === 'participants',
    'lessoncount' => $data['lessoncount'] ?? 0,
    'detailuser' => $detailuser,
    'hasdetail' => $detailuser !== null,
    'detaillessons' => $detaillessons,
    'clearurl' => (new moodle_url('/local/wamakademi/report_course.php', [
        'courseid' => $course->id,
        'tab' => $tab,
    ]))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_wamakademi/report_course', $templatecontext);
echo $OUTPUT->footer();
