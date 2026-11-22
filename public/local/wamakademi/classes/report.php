<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — ilerleme ve ödev rapor veri katmanı.

namespace local_wamakademi;

defined('MOODLE_INTERNAL') || die();

/**
 * Kurs ve kategori raporları için salt okunur veri.
 */
class report {

    /**
     * Kurstaki öğrenci kayıtlı kullanıcılar.
     *
     * @param int $courseid
     * @return \stdClass[] userid => user
     */
    public static function enrolled_students(int $courseid): array {
        $context = \context_course::instance($courseid);
        $users = get_enrolled_users($context, 'mod/resource:view', 0, 'u.*', null, 0, 0, true);
        $students = [];
        foreach ($users as $user) {
            if (is_siteadmin($user) && !is_enrolled($context, $user, '', true)) {
                continue;
            }
            // Raporlarda eğitmen / yönetici satırlarını ayır: yalnızca student rolü veya öğrenci arketipi.
            $roles = get_user_roles($context, (int)$user->id, false);
            $isstudent = false;
            foreach ($roles as $role) {
                if ($role->shortname === 'student' || $role->archetype === 'student') {
                    $isstudent = true;
                    break;
                }
            }
            if ($isstudent) {
                $students[(int)$user->id] = $user;
            }
        }
        return $students;
    }

    /**
     * Kurs katılımcı ilerleme satırları.
     *
     * @param \stdClass $course
     * @param string $statusfilter assigned|active|completed|''
     * @param string $usersearch
     * @param int $useridfilter 0 = hepsi
     * @return array{summary:array, rows:array}
     */
    public static function course_participant_rows(
        \stdClass $course,
        string $statusfilter = '',
        string $usersearch = '',
        int $useridfilter = 0
    ): array {
        $published = self::published_resources_for_report($course);

        $students = self::enrolled_students((int)$course->id);
        $rows = [];
        $counts = [
            'enrolled' => 0,
            'assigned' => 0,
            'active' => 0,
            'completed' => 0,
        ];

        $usersearchnorm = self::normalize_search($usersearch);

        foreach ($students as $userid => $user) {
            if ($useridfilter > 0 && (int)$userid !== $useridfilter) {
                continue;
            }

            $fullname = fullname($user);
            $searchhay = self::normalize_search($fullname . ' ' . $user->email . ' ' . $user->username);
            if ($usersearchnorm !== '' && strpos($searchhay, $usersearchnorm) === false) {
                continue;
            }

            $summary = progress::for_published_lessons((int)$userid, $published);
            $counts['enrolled']++;
            $counts[$summary['status']]++;

            if ($statusfilter !== '' && $summary['status'] !== $statusfilter) {
                continue;
            }

            $rows[] = [
                'userid' => (int)$userid,
                'fullname' => $fullname,
                'email' => (string)$user->email,
                'progress' => (int)$summary['courseprogress'],
                'completedlessons' => (int)$summary['completedlessons'],
                'lessoncount' => (int)$summary['lessoncount'],
                'status' => $summary['status'],
                'statuslabel' => progress::status_label($summary['status']),
                'latestactivity' => (int)$summary['latestactivity'],
                'latestactivityfmt' => $summary['latestactivity']
                    ? userdate($summary['latestactivity'], get_string('strftimedatetimeshort', 'langconfig'))
                    : '—',
                'timecompleted' => (int)$summary['timecompleted'],
                'lessons' => array_values($summary['lessons']),
                'detailurl' => (new \moodle_url('/local/wamakademi/report_course.php', [
                    'courseid' => (int)$course->id,
                    'tab' => 'participants',
                    'userid' => (int)$userid,
                ]))->out(false),
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            return strcasecmp($a['fullname'], $b['fullname']);
        });

        return [
            'summary' => $counts,
            'rows' => $rows,
            'lessoncount' => count($published),
            'lessons' => array_map(static function ($cm) {
                return [
                    'cmid' => (int)$cm->id,
                    'name' => format_string($cm->name),
                ];
            }, array_values($published)),
        ];
    }

    /**
     * Rapor için yayımlı (görünür) resource listesi — öğrenci görünürlüğü değil, kurs görünürlüğü.
     *
     * @param \stdClass $course
     * @return \cm_info[]
     */
    public static function published_resources_for_report(\stdClass $course): array {
        $modinfo = get_fast_modinfo($course);
        $published = [];
        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->modname !== 'resource') {
                continue;
            }
            if (empty($cm->visible) || !empty($cm->deletioninprogress)) {
                continue;
            }
            $published[] = $cm;
        }
        return $published;
    }

    /**
     * Ödev satırları (kurs).
     *
     * @param int $courseid
     * @param string $statusfilter
     * @param string $usersearch
     * @param int $useridfilter
     * @return array{assignments:array, rows:array}
     */
    public static function course_assignment_rows(
        int $courseid,
        string $statusfilter = '',
        string $usersearch = '',
        int $useridfilter = 0
    ): array {
        global $DB;

        $assigns = $DB->get_records('assign', ['course' => $courseid], 'name ASC');
        $students = self::enrolled_students($courseid);
        $assignmentmeta = [];
        $rows = [];
        $usersearchnorm = self::normalize_search($usersearch);

        foreach ($assigns as $assign) {
            $cm = get_coursemodule_from_instance('assign', $assign->id, $courseid, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $assignmentmeta[] = [
                'id' => (int)$assign->id,
                'cmid' => (int)$cm->id,
                'name' => format_string($assign->name),
                'duedate' => (int)$assign->duedate,
                'duedatefmt' => !empty($assign->duedate)
                    ? userdate((int)$assign->duedate, get_string('strftimedatetimeshort', 'langconfig'))
                    : '—',
            ];

            $submissions = $DB->get_records('assign_submission', [
                'assignment' => (int)$assign->id,
                'latest' => 1,
            ]);
            $subbyuser = [];
            foreach ($submissions as $sub) {
                $subbyuser[(int)$sub->userid] = $sub;
            }

            $grades = $DB->get_records('assign_grades', ['assignment' => (int)$assign->id]);
            $gradebyuser = [];
            foreach ($grades as $grade) {
                $gradebyuser[(int)$grade->userid] = $grade;
            }

            foreach ($students as $userid => $user) {
                if ($useridfilter > 0 && (int)$userid !== $useridfilter) {
                    continue;
                }
                $fullname = fullname($user);
                $searchhay = self::normalize_search($fullname . ' ' . $user->email);
                if ($usersearchnorm !== '' && strpos($searchhay, $usersearchnorm) === false) {
                    continue;
                }

                $sub = $subbyuser[(int)$userid] ?? null;
                $grade = $gradebyuser[(int)$userid] ?? null;
                $substatus = $sub ? (string)$sub->status : '';
                $issubmitted = $sub && in_array($substatus, ['submitted', 'reopened'], true);
                $isdraft = $sub && in_array($substatus, ['new', 'draft'], true);
                // Moodle assign: grade null veya -1 = not yok.
                $isgraded = $grade
                    && $grade->grade !== null
                    && (string)$grade->grade !== ''
                    && (float)$grade->grade >= 0;

                $duedate = (int)$assign->duedate;
                $subtime = $sub ? (int)$sub->timemodified : 0;
                $islate = $issubmitted && $duedate > 0 && $subtime > $duedate;

                if (!$sub) {
                    $state = 'missing';
                } else if ($isgraded) {
                    $state = 'graded';
                } else if ($issubmitted && $islate) {
                    $state = 'late';
                } else if ($issubmitted) {
                    $state = 'submitted';
                } else if ($isdraft) {
                    $state = 'draft';
                } else {
                    $state = 'missing';
                }

                if ($statusfilter !== '' && $state !== $statusfilter) {
                    continue;
                }

                $rows[] = [
                    'userid' => (int)$userid,
                    'fullname' => $fullname,
                    'email' => (string)$user->email,
                    'assignmentid' => (int)$assign->id,
                    'assignmentname' => format_string($assign->name),
                    'state' => $state,
                    'statelabel' => get_string('reportassign' . $state, 'local_wamakademi'),
                    'duedate' => $duedate,
                    'duedatefmt' => $duedate
                        ? userdate($duedate, get_string('strftimedatetimeshort', 'langconfig'))
                        : '—',
                    'submittedat' => $issubmitted ? $subtime : 0,
                    'submittedatfmt' => $issubmitted
                        ? userdate($subtime, get_string('strftimedatetimeshort', 'langconfig'))
                        : '—',
                    'grade' => $isgraded ? format_float((float)$grade->grade, 2) : '—',
                    'islate' => $islate,
                ];
            }
        }

        usort($rows, static function (array $a, array $b): int {
            $c = strcasecmp($a['assignmentname'], $b['assignmentname']);
            return $c !== 0 ? $c : strcasecmp($a['fullname'], $b['fullname']);
        });

        return [
            'assignments' => $assignmentmeta,
            'rows' => $rows,
        ];
    }

    /**
     * Kategori / yönetilebilir kurs özeti.
     *
     * @param int $userid
     * @return array
     */
    public static function category_overview(int $userid): array {
        global $DB;

        $options = access::report_course_options($userid);
        $courses = [];
        $totals = [
            'courses' => 0,
            'enrolled' => 0,
            'assigned' => 0,
            'active' => 0,
            'completed' => 0,
        ];

        foreach ($options as $courseid => $coursename) {
            $course = $DB->get_record('course', ['id' => $courseid], '*', IGNORE_MISSING);
            if (!$course) {
                continue;
            }
            $data = self::course_participant_rows($course);
            $summary = $data['summary'];
            $totals['courses']++;
            $totals['enrolled'] += (int)$summary['enrolled'];
            $totals['assigned'] += (int)$summary['assigned'];
            $totals['active'] += (int)$summary['active'];
            $totals['completed'] += (int)$summary['completed'];

            $categoryname = '';
            if (!empty($course->category)) {
                $categoryname = (string)$DB->get_field('course_categories', 'name', ['id' => $course->category]);
            }

            $courses[] = [
                'courseid' => (int)$courseid,
                'fullname' => $coursename,
                'category' => format_string($categoryname),
                'enrolled' => (int)$summary['enrolled'],
                'assigned' => (int)$summary['assigned'],
                'active' => (int)$summary['active'],
                'completed' => (int)$summary['completed'],
                'lessoncount' => (int)$data['lessoncount'],
                'url' => (new \moodle_url('/local/wamakademi/report_course.php', [
                    'courseid' => (int)$courseid,
                ]))->out(false),
            ];
        }

        return [
            'totals' => $totals,
            'courses' => $courses,
        ];
    }

    /**
     * @param string $text
     * @return string
     */
    public static function normalize_search(string $text): string {
        $text = \core_text::strtolower(trim($text));
        $map = [
            'ı' => 'i', 'İ' => 'i',
            'ş' => 's', 'Ş' => 's',
            'ğ' => 'g', 'Ğ' => 'g',
            'ü' => 'u', 'Ü' => 'u',
            'ö' => 'o', 'Ö' => 'o',
            'ç' => 'c', 'Ç' => 'c',
        ];
        return strtr($text, $map);
    }
}
