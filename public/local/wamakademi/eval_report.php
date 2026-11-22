<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — değerlendirme raporları (tablo | grafik).

require_once(__DIR__ . '/../../config.php');

require_login();

$courseid = required_param('courseid', PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$view = optional_param('view', 'table', PARAM_ALPHA);
$report = optional_param('report', '', PARAM_ALPHANUMEXT);

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

if (!in_array($view, ['table', 'chart'], true)) {
    $view = 'table';
}

$activities = \local_wamakademi\eval_report::list_activities($course);
$selected = null;
foreach ($activities as $act) {
    if ($cmid > 0 && (int)$act['cmid'] === $cmid) {
        $selected = $act;
        break;
    }
}
if (!$selected && count($activities) === 1) {
    $selected = $activities[0];
    $cmid = (int)$selected['cmid'];
}

$PAGE->set_url(new moodle_url('/local/wamakademi/eval_report.php', [
    'courseid' => $course->id,
    'cmid' => $cmid ?: null,
    'view' => $view,
    'report' => $report !== '' ? $report : null,
]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('evalreporttitle', 'local_wamakademi', format_string($course->fullname)));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->add_body_class('wam-report-body');
$PAGE->requires->css(local_wamakademi_asset_url('/local/wamakademi/css/wam-report.css'));

$activityoptions = [];
foreach ($activities as $act) {
    $activityoptions[] = [
        'cmid' => $act['cmid'],
        'name' => $act['name'],
        'type' => $act['type'],
        'typelabel' => $act['type'] === 'quiz'
            ? get_string('evalreporttypequiz', 'local_wamakademi')
            : get_string('evalreporttypeassign', 'local_wamakademi'),
        'selected' => $selected && (int)$act['cmid'] === (int)$selected['cmid'],
        'url' => (new moodle_url('/local/wamakademi/eval_report.php', [
            'courseid' => $course->id,
            'cmid' => $act['cmid'],
            'view' => $view,
        ]))->out(false),
    ];
}

$reports = [];
$summary = null;
$statuses = null;
$activityname = '';

if ($selected) {
    $built = \local_wamakademi\eval_report::build_activity_reports(
        $course,
        $selected['type'],
        (int)$selected['cmid']
    );
    $activityname = $built['activity']['name'];
    $summary = $built['distribution']['summary'];
    $statuses = [
        'missing' => (int)$built['statuses']['missing'],
        'pending' => (int)$built['statuses']['pending'],
        'graded' => (int)$built['statuses']['graded'],
    ];

    $allowedkeys = array_column($built['reports'], 'key');
    if ($report === '' || !in_array($report, $allowedkeys, true)) {
        $report = $allowedkeys[0] ?? '';
    }

    foreach ($built['reports'] as $rep) {
        $isempty = empty($rep['rows']);
        if (str_ends_with($rep['key'], '_distribution') && !empty($built['distribution']['empty'])) {
            $isempty = true;
        }
        if ((str_contains($rep['key'], 'topics') || str_contains($rep['key'], 'criteria'))
                && !empty($built['breakdown']['empty'])) {
            $isempty = true;
        }

        $chartempty = $isempty || empty($rep['chart']);
        $reports[] = [
            'key' => $rep['key'],
            'title' => $rep['title'],
            'active' => $rep['key'] === $report,
            'url' => (new moodle_url('/local/wamakademi/eval_report.php', [
                'courseid' => $course->id,
                'cmid' => $selected['cmid'],
                'view' => $view,
                'report' => $rep['key'],
            ]))->out(false),
            'rows' => $rep['rows'],
            'hasrows' => !empty($rep['rows']),
            'chart' => $rep['chart'],
            'haschart' => !$chartempty,
            'summary' => $rep['summary'] ?? null,
            'hassummary' => !empty($rep['summary']),
            'empty' => $isempty,
        ];
    }
}

$activereport = null;
foreach ($reports as $rep) {
    if (!empty($rep['active'])) {
        $activereport = $rep;
        break;
    }
}

$templatecontext = [
    'coursename' => format_string($course->fullname),
    'courseurl' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
    'backurl' => (new moodle_url('/local/wamakademi/report_course.php', [
        'courseid' => $course->id,
    ]))->out(false),
    'courseid' => (int)$course->id,
    'hasactivities' => !empty($activityoptions),
    'activities' => $activityoptions,
    'hasselection' => $selected !== null,
    'activityname' => $activityname,
    'viewtable' => $view === 'table',
    'viewchart' => $view === 'chart',
    'tableurl' => $selected ? (new moodle_url('/local/wamakademi/eval_report.php', [
        'courseid' => $course->id,
        'cmid' => $selected['cmid'],
        'view' => 'table',
        'report' => $report !== '' ? $report : null,
    ]))->out(false) : '',
    'charturl' => $selected ? (new moodle_url('/local/wamakademi/eval_report.php', [
        'courseid' => $course->id,
        'cmid' => $selected['cmid'],
        'view' => 'chart',
        'report' => $report !== '' ? $report : null,
    ]))->out(false) : '',
    'reports' => $reports,
    'hasreports' => !empty($reports),
    'activereport' => $activereport,
    'summary' => $summary,
    'hassummary' => $summary !== null,
    'statuses' => $statuses,
    'hasstatuses' => $statuses !== null,
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_wamakademi/eval_report', $templatecontext);
echo $OUTPUT->footer();
