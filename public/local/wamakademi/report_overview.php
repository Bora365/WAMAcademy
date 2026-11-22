<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — kategori / yönetilebilir kurs özeti.

require_once(__DIR__ . '/../../config.php');

require_login();

$download = optional_param('download', 0, PARAM_BOOL);

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/local/wamakademi/report_overview.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('reportoverviewtitle', 'local_wamakademi'));
$PAGE->set_heading('');
$PAGE->add_body_class('wam-report-body');
$PAGE->requires->css(local_wamakademi_asset_url('/local/wamakademi/css/wam-report.css'));

if (!\local_wamakademi\access::can_view_overview((int)$USER->id)) {
    throw new moodle_exception('reportnoperm', 'local_wamakademi');
}

$data = \local_wamakademi\report::category_overview((int)$USER->id);

if ($download) {
    require_sesskey();
    $filename = 'wam-report-overview-' . date('Ymd-His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, [
        'courseid', 'course', 'category', 'enrolled', 'assigned', 'active', 'completed', 'lessons',
    ]);
    foreach ($data['courses'] as $row) {
        fputcsv($out, [
            $row['courseid'],
            $row['fullname'],
            $row['category'],
            $row['enrolled'],
            $row['assigned'],
            $row['active'],
            $row['completed'],
            $row['lessoncount'],
        ]);
    }
    fclose($out);
    exit;
}

$exporturl = new moodle_url('/local/wamakademi/report_overview.php', [
    'download' => 1,
    'sesskey' => sesskey(),
]);

$templatecontext = [
    'totals' => $data['totals'],
    'courses' => $data['courses'],
    'hascourses' => !empty($data['courses']),
    'exporturl' => $exporturl->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_wamakademi/report_overview', $templatecontext);
echo $OUTPUT->footer();
