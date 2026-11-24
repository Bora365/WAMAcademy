<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — site geneli Ödevler / Sınavlar panosu.

namespace local_wamakademi;

defined('MOODLE_INTERNAL') || die();

/**
 * Kayıtlı kurslardaki ödev ve sınav satırlarını rol bazlı üretir.
 */
class assessment_board {

    /**
     * @param int $userid
     * @param string $modname assign|quiz
     * @param string $q Arama
     * @param int $courseidfilter 0 = tümü
     * @return array{rows:array, summary:array, courses:array, isteacherany:bool}
     */
    public static function for_user(int $userid, string $modname, string $q = '', int $courseidfilter = 0): array {
        if (!in_array($modname, ['assign', 'quiz'], true) || $userid <= 0) {
            return self::empty_payload();
        }

        $courses = enrol_get_users_courses($userid, true, 'id,fullname,shortname,visible');
        if (empty($courses)) {
            return self::empty_payload();
        }

        $qnorm = self::normalize_search($q);
        $rows = [];
        $courseoptions = [];
        $isteacherany = false;
        $summary = [
            'total' => 0,
            'pending' => 0,
            'submitted' => 0,
            'graded' => 0,
            'teacher' => 0,
            'student' => 0,
        ];

        foreach ($courses as $course) {
            $courseid = (int)$course->id;
            if ($courseid <= 1) {
                continue;
            }
            $coursecontext = \context_course::instance($courseid, IGNORE_MISSING);
            if (!$coursecontext) {
                continue;
            }
            if (empty($course->visible) && !has_capability('moodle/course:viewhiddencourses', $coursecontext, $userid)) {
                continue;
            }
            if ($courseidfilter > 0 && $courseid !== $courseidfilter) {
                continue;
            }

            $modinfo = get_fast_modinfo($course, $userid);
            $cms = $modinfo->get_instances_of($modname);
            if (empty($cms)) {
                continue;
            }

            $courseoptions[$courseid] = format_string($course->fullname);

            foreach ($cms as $cm) {
                if (!$cm->uservisible) {
                    continue;
                }
                $row = $modname === 'assign'
                    ? self::assign_row($course, $cm, $userid)
                    : self::quiz_row($course, $cm, $userid);
                if ($row === null) {
                    continue;
                }

                if ($qnorm !== '') {
                    $hay = self::normalize_search($row['name'] . ' ' . $row['coursename']);
                    if (strpos($hay, $qnorm) === false) {
                        continue;
                    }
                }

                if ($row['isteacher']) {
                    $isteacherany = true;
                    $summary['teacher']++;
                } else {
                    $summary['student']++;
                }
                $summary['total']++;
                if (in_array($row['state'], ['missing', 'draft', 'pending', 'assigned'], true)) {
                    $summary['pending']++;
                } else if (in_array($row['state'], ['submitted', 'late', 'inprogress', 'attempted'], true)) {
                    $summary['submitted']++;
                } else if (in_array($row['state'], ['graded', 'completed'], true)) {
                    $summary['graded']++;
                }

                $rows[] = $row;
            }
        }

        usort($rows, static function (array $a, array $b): int {
            $da = (int)($a['duedate'] ?? 0);
            $db = (int)($b['duedate'] ?? 0);
            if ($da > 0 && $db > 0 && $da !== $db) {
                return $da <=> $db;
            }
            if ($da > 0 && $db <= 0) {
                return -1;
            }
            if ($db > 0 && $da <= 0) {
                return 1;
            }
            $c = strcasecmp($a['coursename'], $b['coursename']);
            return $c !== 0 ? $c : strcasecmp($a['name'], $b['name']);
        });

        $courselist = [];
        foreach ($courseoptions as $id => $name) {
            $courselist[] = [
                'id' => (int)$id,
                'name' => $name,
                'selected' => $courseidfilter === (int)$id,
            ];
        }
        usort($courselist, static function (array $a, array $b): int {
            return strcasecmp($a['name'], $b['name']);
        });

        return [
            'rows' => $rows,
            'summary' => $summary,
            'courses' => $courselist,
            'isteacherany' => $isteacherany,
            'hasrows' => !empty($rows),
        ];
    }

    /**
     * @return array
     */
    private static function empty_payload(): array {
        return [
            'rows' => [],
            'summary' => [
                'total' => 0,
                'pending' => 0,
                'submitted' => 0,
                'graded' => 0,
                'teacher' => 0,
                'student' => 0,
            ],
            'courses' => [],
            'isteacherany' => false,
            'hasrows' => false,
        ];
    }

    /**
     * @param \stdClass $course
     * @param \cm_info $cm
     * @param int $userid
     * @return array|null
     */
    private static function assign_row(\stdClass $course, \cm_info $cm, int $userid): ?array {
        global $DB;

        $assign = $DB->get_record('assign', ['id' => $cm->instance], '*', IGNORE_MISSING);
        if (!$assign) {
            return null;
        }

        $modcontext = \context_module::instance($cm->id);
        $isteacher = has_capability('mod/assign:grade', $modcontext, $userid)
            || has_capability('mod/assign:managegrades', $modcontext, $userid);

        $duedate = (int)$assign->duedate;
        $duedatefmt = $duedate
            ? userdate($duedate, get_string('strftimedatetimeshort', 'langconfig'))
            : '—';

        $openurl = (new \moodle_url('/mod/assign/view.php', ['id' => $cm->id]))->out(false);
        $huburl = (new \moodle_url('/local/wamakademi/grading_hub.php', ['id' => $cm->id]))->out(false);
        $canreport = access::can_view_reports($userid, (int)$course->id);
        $reporturl = $canreport
            ? (new \moodle_url('/local/wamakademi/eval_report.php', [
                'courseid' => (int)$course->id,
                'cmid' => (int)$cm->id,
            ]))->out(false)
            : '';

        if ($isteacher) {
            $subs = $DB->get_records('assign_submission', [
                'assignment' => (int)$assign->id,
                'latest' => 1,
            ]);
            $grades = $DB->get_records('assign_grades', ['assignment' => (int)$assign->id]);
            $gradebyuser = [];
            foreach ($grades as $g) {
                $gradebyuser[(int)$g->userid] = $g;
            }
            $submitted = 0;
            $graded = 0;
            $pending = 0;
            foreach ($subs as $sub) {
                $uid = (int)$sub->userid;
                if ($uid <= 0) {
                    continue;
                }
                $status = (string)$sub->status;
                $g = $gradebyuser[$uid] ?? null;
                $isgraded = $g && $g->grade !== null && (string)$g->grade !== '' && (float)$g->grade >= 0;
                if ($isgraded) {
                    $graded++;
                } else if (in_array($status, ['submitted', 'reopened'], true)) {
                    $submitted++;
                    $pending++;
                }
            }
            $state = $pending > 0 ? 'submitted' : ($graded > 0 ? 'graded' : 'pending');
            $metric = get_string('assessboardmetricassign', 'local_wamakademi', (object) [
                'submitted' => $submitted + $graded,
                'graded' => $graded,
                'pending' => $pending,
            ]);

            return self::base_row($course, $cm, $assign->name, $duedate, $duedatefmt, [
                'isteacher' => true,
                'state' => $state,
                'statelabel' => get_string('assessboardstate' . $state, 'local_wamakademi'),
                'metric' => $metric,
                'grade' => '—',
                'openurl' => $openurl,
                'huburl' => $huburl,
                'reporturl' => $reporturl,
                'hasreport' => $canreport,
                'hashub' => true,
            ]);
        }

        // Öğrenci: kendi teslimi.
        $sub = $DB->get_record('assign_submission', [
            'assignment' => (int)$assign->id,
            'userid' => $userid,
            'latest' => 1,
        ]);
        $grade = $DB->get_record('assign_grades', [
            'assignment' => (int)$assign->id,
            'userid' => $userid,
        ]);
        $substatus = $sub ? (string)$sub->status : '';
        $issubmitted = $sub && in_array($substatus, ['submitted', 'reopened'], true);
        $isdraft = $sub && in_array($substatus, ['new', 'draft'], true);
        $isgraded = $grade && $grade->grade !== null && (string)$grade->grade !== '' && (float)$grade->grade >= 0;
        $subtime = $sub ? (int)$sub->timemodified : 0;
        $islate = $issubmitted && $duedate > 0 && $subtime > $duedate;

        if ($isgraded) {
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

        $gradetext = $isgraded ? format_float((float)$grade->grade, 2) : '—';

        return self::base_row($course, $cm, $assign->name, $duedate, $duedatefmt, [
            'isteacher' => false,
            'state' => $state,
            'statelabel' => get_string('reportassign' . $state, 'local_wamakademi'),
            'metric' => $gradetext,
            'grade' => $gradetext,
            'openurl' => $openurl,
            'huburl' => '',
            'reporturl' => '',
            'hasreport' => false,
            'hashub' => false,
        ]);
    }

    /**
     * @param \stdClass $course
     * @param \cm_info $cm
     * @param int $userid
     * @return array|null
     */
    private static function quiz_row(\stdClass $course, \cm_info $cm, int $userid): ?array {
        global $DB;

        $quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', IGNORE_MISSING);
        if (!$quiz) {
            return null;
        }

        $modcontext = \context_module::instance($cm->id);
        $isteacher = has_capability('mod/quiz:viewreports', $modcontext, $userid)
            || has_capability('mod/quiz:grade', $modcontext, $userid);

        $duedate = (int)($quiz->timeclose ?? 0);
        $duedatefmt = $duedate
            ? userdate($duedate, get_string('strftimedatetimeshort', 'langconfig'))
            : '—';

        $openurl = (new \moodle_url('/mod/quiz/view.php', ['id' => $cm->id]))->out(false);
        $huburl = (new \moodle_url('/local/wamakademi/grading_hub.php', ['id' => $cm->id]))->out(false);
        $canreport = access::can_view_reports($userid, (int)$course->id);
        $reporturl = $canreport
            ? (new \moodle_url('/local/wamakademi/eval_report.php', [
                'courseid' => (int)$course->id,
                'cmid' => (int)$cm->id,
            ]))->out(false)
            : '';

        if ($isteacher) {
            $attempts = $DB->get_records_select(
                'quiz_attempts',
                'quiz = ? AND preview = 0',
                [(int)$quiz->id]
            );
            $finished = 0;
            $inprogress = 0;
            $users = [];
            foreach ($attempts as $att) {
                $users[(int)$att->userid] = true;
                if ((string)$att->state === 'finished') {
                    $finished++;
                } else {
                    $inprogress++;
                }
            }
            $state = $inprogress > 0 ? 'inprogress' : ($finished > 0 ? 'graded' : 'pending');
            $metric = get_string('assessboardmetricquiz', 'local_wamakademi', (object) [
                'attempts' => count($attempts),
                'users' => count($users),
                'finished' => $finished,
            ]);

            return self::base_row($course, $cm, $quiz->name, $duedate, $duedatefmt, [
                'isteacher' => true,
                'state' => $state,
                'statelabel' => get_string('assessboardstate' . $state, 'local_wamakademi'),
                'metric' => $metric,
                'grade' => '—',
                'openurl' => $openurl,
                'huburl' => $huburl,
                'reporturl' => $reporturl,
                'hasreport' => $canreport,
                'hashub' => true,
            ]);
        }

        $attempt = $DB->get_records_select(
            'quiz_attempts',
            'quiz = ? AND userid = ? AND preview = 0',
            [(int)$quiz->id, $userid],
            'attempt DESC',
            '*',
            0,
            1
        );
        $att = $attempt ? reset($attempt) : null;
        $grade = $DB->get_record('quiz_grades', [
            'quiz' => (int)$quiz->id,
            'userid' => $userid,
        ]);

        if ($grade && $grade->grade !== null) {
            $state = 'graded';
            $gradetext = format_float((float)$grade->grade, 2);
        } else if ($att && (string)$att->state === 'finished') {
            $state = 'submitted';
            $gradetext = '—';
        } else if ($att) {
            $state = 'inprogress';
            $gradetext = '—';
        } else {
            $state = 'missing';
            $gradetext = '—';
        }

        return self::base_row($course, $cm, $quiz->name, $duedate, $duedatefmt, [
            'isteacher' => false,
            'state' => $state,
            'statelabel' => get_string('assessboardstate' . $state, 'local_wamakademi'),
            'metric' => $gradetext,
            'grade' => $gradetext,
            'openurl' => $openurl,
            'huburl' => '',
            'reporturl' => '',
            'hasreport' => false,
            'hashub' => false,
        ]);
    }

    /**
     * @param \stdClass $course
     * @param \cm_info $cm
     * @param string $name
     * @param int $duedate
     * @param string $duedatefmt
     * @param array $extra
     * @return array
     */
    private static function base_row(
        \stdClass $course,
        \cm_info $cm,
        string $name,
        int $duedate,
        string $duedatefmt,
        array $extra
    ): array {
        return array_merge([
            'cmid' => (int)$cm->id,
            'courseid' => (int)$course->id,
            'name' => format_string($name),
            'coursename' => format_string($course->fullname),
            'courseurl' => (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
            'duedate' => $duedate,
            'duedatefmt' => $duedatefmt,
        ], $extra);
    }

    /**
     * @param string $text
     * @return string
     */
    private static function normalize_search(string $text): string {
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
