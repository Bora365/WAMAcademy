<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi - Eğitimlerim (My Courses) page.

require_once(__DIR__ . '/../../config.php');

require_login();

global $DB, $USER, $PAGE, $OUTPUT, $CFG;

$PAGE->set_url(new moodle_url('/local/wamakademi/mycourses.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Eğitimlerim');
$PAGE->set_heading('');
$PAGE->add_body_class('wam-my-courses-body');

$PAGE->requires->js(new moodle_url('/local/wamakademi/js/mycourses.js'));

/**
 * Build searchable text with Turkish-friendly folding (PHP side).
 *
 * @param string $text Raw text.
 * @return string Normalized lowercase text.
 */
function local_wamakademi_mycourses_normalize_search(string $text): string {
    $text = core_text::strtolower($text);
    $map = [
        'ı' => 'i', 'İ' => 'i', 'i' => 'i', 'I' => 'i',
        'ş' => 's', 'Ş' => 's',
        'ğ' => 'g', 'Ğ' => 'g',
        'ü' => 'u', 'Ü' => 'u',
        'ö' => 'o', 'Ö' => 'o',
        'ç' => 'c', 'Ç' => 'c',
    ];
    return strtr($text, $map);
}

/**
 * Build initials fallback from a course name.
 *
 * @param string $name Course fullname.
 * @return string Up to two uppercase initials.
 */
function local_wamakademi_mycourses_initials(string $name): string {
    $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY);
    if (empty($parts)) {
        return '?';
    }
    $first = core_text::strtoupper(core_text::substr($parts[0], 0, 1));
    if (count($parts) === 1) {
        return $first;
    }
    $last = core_text::strtoupper(core_text::substr($parts[count($parts) - 1], 0, 1));
    return $first . $last;
}

$courses = enrol_get_users_courses(
    $USER->id,
    true,
    'id,fullname,shortname,category,visible'
);

$listedcourses = [];
$hero = null;

foreach ($courses as $course) {

    $coursecontext = context_course::instance($course->id);

    // Hide invisible courses unless the user can view hidden courses.
    if (empty($course->visible) && !has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
        continue;
    }

    $published = \local_wamakademi\course_videos::published_resources_for_user($course, (int)$USER->id);
    $summary = \local_wamakademi\progress::for_published_lessons((int)$USER->id, $published);

    $lessoncount = (int)$summary['lessoncount'];
    $courseprogress = (int)$summary['courseprogress'];
    $completedlessons = (int)$summary['completedlessons'];
    $latestactivity = (int)$summary['latestactivity'];
    $hasstarted = !empty($summary['hasstarted']);
    $iscompleted = !empty($summary['iscompleted']);
    $status = (string)$summary['status'];

    $continue = \local_wamakademi\course_cards::continue_target((int)$USER->id, $published);
    $nav = \local_wamakademi\course_cards::navigation(
        (int)$USER->id,
        $course,
        $published,
        $continue['cmid']
    );

    $statuslabel = \local_wamakademi\progress::status_label($status);
    if ($nav['canmanage']) {
        $actionlabel = $nav['actionlabel'];
    } else if ($iscompleted) {
        $actionlabel = 'Eğitimi incele';
    } else if ($hasstarted) {
        $actionlabel = $nav['actionlabel'];
    } else if ($nav['haspublishedlesson']) {
        $actionlabel = 'Eğitime başla';
    } else {
        $actionlabel = $nav['actionlabel'];
    }

    $actionurl = $nav['actionurl'];

    $courseimage = null;
    $courseinlist = new core_course_list_element($course);
    foreach ($courseinlist->get_course_overviewfiles() as $file) {
        if ($file->is_valid_image()) {
            $courseimage = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                null,
                $file->get_filepath(),
                $file->get_filename()
            )->out();
            break;
        }
    }

    $categoryname = '';
    if (!empty($course->category)) {
        $category = core_course_category::get($course->category, IGNORE_MISSING);
        if ($category) {
            $categoryname = $category->name;
        }
    }

    $fullname = format_string($course->fullname);
    $shortname = format_string($course->shortname);

    $searchtext = local_wamakademi_mycourses_normalize_search(
        $fullname . ' ' . $categoryname . ' ' . $shortname
    );

    $courseitem = [
        'id' => (int)$course->id,
        'fullname' => $fullname,
        'shortname' => $shortname,
        'category' => format_string($categoryname),
        'hascategory' => $categoryname !== '',
        'progress' => (int)$courseprogress,
        'completedlessons' => $completedlessons,
        'lessoncount' => $lessoncount,
        'continuelessonindex' => $continue['lessonindex'],
        'continuelessonname' => $continue['lessonname'],
        'continueurl' => $actionurl,
        'courseurl' => $nav['courseurl'],
        'courseimage' => $courseimage,
        'hascourseimage' => !empty($courseimage),
        'initials' => local_wamakademi_mycourses_initials($fullname),
        'status' => $status,
        'statuslabel' => $statuslabel,
        'actionlabel' => $actionlabel,
        'iscompleted' => $iscompleted,
        'isactive' => ($status === 'active'),
        'isassigned' => ($status === 'assigned'),
        'searchtext' => $searchtext,
        'latestactivity' => $latestactivity,
    ];

    $listedcourses[] = $courseitem;

    // Hero: most recently modified in-progress course.
    if ($status === 'active') {
        if ($hero === null || $latestactivity > (int)$hero['latestactivity']) {
            $hero = $courseitem;
        }
    }
}

$coursecount = count($listedcourses);

$templatecontext = [
    'wwwroot' => $CFG->wwwroot,
    'hascourses' => $coursecount > 0,
    'coursecount' => $coursecount,
    'courses' => array_values($listedcourses),
    'hashero' => !empty($hero),
    'hero' => $hero ?: null,
    'catalogurl' => (new moodle_url('/local/wamakademi/catalog.php'))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_wamakademi/mycourses', $templatecontext);
echo $OUTPUT->footer();
