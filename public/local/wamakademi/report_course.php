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

$statuscolors = [
    'assigned' => '#94a3b8',
    'active' => '#2563eb',
    'completed' => '#15803d',
    'missing' => '#94a3b8',
    'draft' => '#64748b',
    'submitted' => '#2563eb',
    'late' => '#c2410c',
    'graded' => '#15803d',
];

$chartdata = null;
if ($tab === 'participants' && !empty($data['rows'])) {
    $summary = $data['summary'] ?? [];
    $pie = [];
    foreach (['assigned', 'active', 'completed'] as $st) {
        $count = (int)($summary[$st] ?? 0);
        if ($count <= 0) {
            continue;
        }
        $pie[] = [
            'label' => \local_wamakademi\progress::status_label($st),
            'value' => $count,
            'color' => $statuscolors[$st],
        ];
    }
    $chartrows = $data['rows'];
    usort($chartrows, static function (array $a, array $b): int {
        return ((int)$b['progress'] <=> (int)$a['progress'])
            ?: strcasecmp($a['fullname'], $b['fullname']);
    });
    $categories = [];
    $barA = [];
    $barB = [];
    $lineA = [];
    $list = [];
    foreach ($chartrows as $row) {
        $categories[] = $row['fullname'];
        $done = (int)$row['completedlessons'];
        $total = max(1, (int)$row['lessoncount']);
        $remaining = max(0, $total - $done);
        $progress = (int)$row['progress'];
        $barA[] = $done;
        $barB[] = $remaining;
        $lineA[] = $progress;
        $list[] = [
            'name' => $row['fullname'],
            'progress' => $progress,
            'detail' => $done . ' / ' . $total,
            'status' => (string)($row['statuslabel'] ?? ''),
            'color' => $statuscolors[(string)($row['status'] ?? 'assigned')] ?? '#94a3b8',
        ];
    }
    $chartdata = [
        'pie' => $pie,
        'list' => $list,
        'listtitle' => get_string('reportchartstudentlist', 'local_wamakademi'),
        'combo' => [
            'categories' => $categories,
            'barA' => $barA,
            'barB' => $barB,
            'lineA' => $lineA,
            'lineB' => [],
            'legend' => [
                ['type' => 'bar', 'color' => '#2563eb', 'label' => get_string('reportchartbarcompleted', 'local_wamakademi')],
                ['type' => 'bar', 'color' => '#f97316', 'label' => get_string('reportchartbarremaining', 'local_wamakademi')],
                ['type' => 'line', 'color' => '#16a34a', 'label' => get_string('reportprogress', 'local_wamakademi') . ' %'],
            ],
        ],
    ];
} else if ($tab === 'assignments' && !empty($data['rows'])) {
    $statecounts = [];
    foreach ($data['rows'] as $row) {
        $st = (string)($row['state'] ?? 'missing');
        $statecounts[$st] = ($statecounts[$st] ?? 0) + 1;
    }
    $pie = [];
    foreach (['missing', 'draft', 'submitted', 'late', 'graded'] as $st) {
        if (empty($statecounts[$st])) {
            continue;
        }
        $pie[] = [
            'label' => get_string('reportassign' . $st, 'local_wamakademi'),
            'value' => (int)$statecounts[$st],
            'color' => $statuscolors[$st],
        ];
    }
    // İleri grafik: durum kategorileri — çubuk=adet, çizgi=oran.
    $categories = [];
    $barA = [];
    $barB = [];
    $lineA = [];
    $totalrows = max(1, count($data['rows']));
    foreach ($pie as $slice) {
        $categories[] = $slice['label'];
        $barA[] = $slice['value'];
        $barB[] = 0;
        $lineA[] = (int)round(($slice['value'] / $totalrows) * 100);
    }
    $list = [];
    foreach ($data['rows'] as $row) {
        $st = (string)($row['state'] ?? 'missing');
        $list[] = [
            'name' => $row['fullname'],
            'progress' => null,
            'detail' => trim(($row['assignmentname'] ?? '') . ' · ' . ($row['statelabel'] ?? '')),
            'status' => (string)($row['statelabel'] ?? ''),
            'color' => $statuscolors[$st] ?? '#94a3b8',
        ];
    }
    $chartdata = [
        'pie' => $pie,
        'list' => $list,
        'listtitle' => get_string('reportchartassignlist', 'local_wamakademi'),
        'combo' => [
            'categories' => $categories,
            'barA' => $barA,
            'barB' => $barB,
            'lineA' => $lineA,
            'lineB' => [],
            'legend' => [
                ['type' => 'bar', 'color' => '#2563eb', 'label' => get_string('reportchartbarcount', 'local_wamakademi')],
                ['type' => 'line', 'color' => '#16a34a', 'label' => get_string('reportchartlineratio', 'local_wamakademi')],
            ],
        ],
    ];
}

if ($chartdata) {
    $PAGE->requires->js(new moodle_url('/local/wamakademi/js/report_charts.js', [
        'v' => filemtime($CFG->dirroot . '/local/wamakademi/js/report_charts.js'),
    ]));
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
    'hascharts' => $chartdata !== null,
    // Mustache attribute escape; JS getAttribute ile geri çözülür.
    'chartdatajson' => $chartdata ? json_encode($chartdata, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) : '',
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
