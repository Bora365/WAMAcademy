<?php

require_once('../../config.php');

require_login();

$PAGE->set_url(new moodle_url('/local/wamakademi/index.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title('WAM Akademi');
$PAGE->set_heading('');

/*
 * Giriş yapan kullanıcının kayıtlı olduğu kursları getir.
 */
$usercourses = enrol_get_users_courses(
    $USER->id,
    true,
    'id,fullname,shortname,category,visible,startdate,enddate'
);

$courses = [];
$ongoingcount = 0;
$completedcount = 0;

foreach ($usercourses as $course) {

    if (empty($course->visible)) {
        continue;
    }

    $published = \local_wamakademi\course_videos::published_resources_for_user($course, (int)$USER->id);
    $summary = \local_wamakademi\progress::for_published_lessons((int)$USER->id, $published);
    $progress = (int)$summary['courseprogress'];
    $iscompleted = !empty($summary['iscompleted']);

    if ($iscompleted) {
        $completedcount++;
    } else {
        $ongoingcount++;
    }

    $categoryname = $DB->get_field(
        'course_categories',
        'name',
        ['id' => $course->category]
    );

    if (!$categoryname) {
        $categoryname = 'Eğitim';
    }

    $continue = \local_wamakademi\course_cards::continue_target((int)$USER->id, $published);
    $nav = \local_wamakademi\course_cards::navigation(
        (int)$USER->id,
        $course,
        $published,
        $continue['cmid']
    );

    $courseimage = null;
    $coursecontext = context_course::instance($course->id);
    $fs = get_file_storage();
    $files = $fs->get_area_files(
        $coursecontext->id,
        'course',
        'overviewfiles',
        false,
        'sortorder, id',
        false
    );

    foreach ($files as $file) {
        if ($file->is_valid_image()) {
            $courseimage = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                'course',
                'overviewfiles',
                null,
                $file->get_filepath(),
                $file->get_filename()
            )->out(false);
            break;
        }
    }

    $actionlabel = $nav['actionlabel'];
    if ($iscompleted && !$nav['canmanage']) {
        $actionlabel = 'Tamamlandı';
    }

    $courses[] = [
        'id' => $course->id,
        'fullname' => format_string($course->fullname),
        'shortname' => format_string($course->shortname),
        'category' => format_string($categoryname),
        'courseimage' => $courseimage,
        'hascourseimage' => !empty($courseimage),
        'progress' => $progress,
        'iscompleted' => $iscompleted,
        // Özet kart filtreleri: tamamlanan vs devam eden (atananlar dahil).
        'status' => $iscompleted ? 'completed' : 'ongoing',
        'courseurl' => $nav['courseurl'],
        'continueurl' => $nav['actionurl'],
        'actionlabel' => $actionlabel,
        'canmanage' => $nav['canmanage'],
        'hasactionlink' => !($iscompleted && !$nav['canmanage']),
        'continuecmid' => $nav['continuecmid'],
        'continuecurrenttime' => $continue['currenttime'],
    ];
}

/*
 * ---------------------------------------------------------
 * HERO - KALDIĞIN YERDEN DEVAM ET
 * ---------------------------------------------------------
 */

$herocontinueurl = null;

foreach ($courses as $courseitem) {
    if (!empty($courseitem['iscompleted'])) {
        continue;
    }
    if ((int)$courseitem['progress'] <= 0) {
        continue;
    }
    if (!empty($courseitem['continueurl'])) {
        $herocontinueurl = $courseitem['continueurl'];
        break;
    }
}

if (empty($herocontinueurl)) {
    $herocontinueurl = (new moodle_url(
        '/local/wamakademi/mycourses.php'
    ))->out(false);
}

$templatecontext = [
    'firstname' => $USER->firstname,
    'courses' => array_values($courses),
    'hascourses' => !empty($courses),
    'ongoingcount' => $ongoingcount,
    'completedcount' => $completedcount,
    'herocontinueurl' => $herocontinueurl,
];

$PAGE->requires->js(new moodle_url('/local/wamakademi/js/dashboard.js', [
    'v' => (string)@filemtime($CFG->dirroot . '/local/wamakademi/js/dashboard.js'),
]));

echo $OUTPUT->header();
echo $OUTPUT->render_from_template(
    'local_wamakademi/dashboard',
    $templatecontext
);
echo $OUTPUT->footer();
