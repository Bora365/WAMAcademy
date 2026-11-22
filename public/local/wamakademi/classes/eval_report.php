<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — değerlendirme rapor hesapları.

namespace local_wamakademi;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/grade/grading/lib.php');

/**
 * Sınav / ödev değerlendirme raporları (tablo + grafik verisi).
 */
final class eval_report {

    public const BUCKETS = [
        '0_40' => ['label' => '0 – <40', 'min' => 0.0, 'max' => 40.0],
        '40_60' => ['label' => '40 – <60', 'min' => 40.0, 'max' => 60.0],
        '60_80' => ['label' => '60 – <80', 'min' => 60.0, 'max' => 80.0],
        '80_100' => ['label' => '80 – 100', 'min' => 80.0, 'max' => 100.01],
    ];

    /**
     * Kurstaki demo sınav / ödev etkinlikleri.
     *
     * @return list<array{type:string,cmid:int,instanceid:int,name:string,idnumber:string}>
     */
    public static function list_activities(\stdClass $course): array {
        global $DB;

        $rows = $DB->get_records_sql(
            "SELECT cm.id AS cmid, cm.instance, cm.idnumber, m.name AS modname, COALESCE(q.name, a.name) AS activityname
               FROM {course_modules} cm
               JOIN {modules} m ON m.id = cm.module
          LEFT JOIN {quiz} q ON m.name = 'quiz' AND q.id = cm.instance
          LEFT JOIN {assign} a ON m.name = 'assign' AND a.id = cm.instance
              WHERE cm.course = ?
                AND m.name IN ('quiz', 'assign')
                AND cm.deletioninprogress = 0
                AND (
                    cm.idnumber IN (?, ?)
                    OR q.name = ?
                    OR a.name = ?
                )
           ORDER BY m.name DESC, activityname ASC",
            [
                $course->id,
                demo_env::QUIZ_IDNUMBER,
                demo_env::ASSIGN_IDNUMBER,
                demo_env::QUIZ_NAME,
                demo_env::ASSIGN_NAME,
            ]
        );

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'type' => $row->modname,
                'cmid' => (int)$row->cmid,
                'instanceid' => (int)$row->instance,
                'name' => format_string($row->activityname),
                'idnumber' => (string)$row->idnumber,
            ];
        }
        return $out;
    }

    /**
     * Grup kısıtlı kayıtlı öğrenciler.
     *
     * @return array<int,\stdClass>
     */
    public static function enrolled_students(\stdClass $course): array {
        $context = \context_course::instance($course->id);
        $groupid = 0;
        if (groups_get_course_groupmode($course) != NOGROUPS) {
            $groupid = groups_get_course_group($course, true) ?: 0;
        }
        $users = get_enrolled_users($context, 'mod/assign:submit', $groupid, 'u.*', 'u.lastname, u.firstname', 0, 0, true);
        $out = [];
        foreach ($users as $user) {
            if (is_siteadmin($user) || isguestuser($user)) {
                continue;
            }
            // Öğretmenleri ayıkla: yalnızca öğrenci rolü olanlar.
            $roles = get_user_roles($context, $user->id, false);
            $isstudent = false;
            foreach ($roles as $role) {
                if ($role->shortname === 'student') {
                    $isstudent = true;
                    break;
                }
            }
            if (!$isstudent) {
                continue;
            }
            $out[(int)$user->id] = $user;
        }
        return $out;
    }

    /**
     * Seçilen etkinlik için iki rapor + dağılım.
     *
     * @return array{
     *   activity:array,
     *   distribution:array,
     *   breakdown:array,
     *   statuses:array
     * }
     */
    public static function build_activity_reports(\stdClass $course, string $type, int $cmid): array {
        $cm = get_coursemodule_from_id($type, $cmid, $course->id, false, MUST_EXIST);
        $students = self::enrolled_students($course);

        if ($type === 'quiz') {
            return self::build_quiz_reports($course, $cm, $students);
        }
        if ($type === 'assign') {
            return self::build_assign_reports($course, $cm, $students);
        }
        throw new \invalid_parameter_exception('Geçersiz etkinlik türü');
    }

    /**
     * @param array<int,\stdClass> $students
     */
    private static function build_quiz_reports(\stdClass $course, \stdClass $cm, array $students): array {
        global $DB;

        $quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
        $item = \grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => 'quiz',
            'iteminstance' => $quiz->id,
            'courseid' => $course->id,
        ]);

        $scores = []; // userid => percent|null
        $statuses = ['missing' => 0, 'pending' => 0, 'graded' => 0];

        foreach ($students as $userid => $user) {
            $attempt = $DB->get_record_sql(
                "SELECT * FROM {quiz_attempts}
                  WHERE quiz = ? AND userid = ? AND preview = 0
               ORDER BY attempt DESC",
                [$quiz->id, $userid],
                IGNORE_MULTIPLE
            );

            $final = null;
            if ($item) {
                $gg = \grade_grade::fetch(['itemid' => $item->id, 'userid' => $userid]);
                if ($gg && $gg->finalgrade !== null) {
                    $final = self::to_percent((float)$gg->finalgrade, (float)$item->grademax);
                }
            }

            if (!$attempt) {
                $statuses['missing']++;
                $scores[$userid] = null;
            } else if ($final === null) {
                $statuses['pending']++;
                $scores[$userid] = null; // puan yok → %0 sayılmaz
            } else {
                $statuses['graded']++;
                $scores[$userid] = $final;
            }
        }

        $distribution = self::make_distribution($scores, (float)($quiz->gradepass ?: 60), count($students));
        $breakdown = self::quiz_topic_breakdown($quiz, $cm, $students, $scores);

        return [
            'activity' => [
                'type' => 'quiz',
                'cmid' => (int)$cm->id,
                'instanceid' => (int)$quiz->id,
                'name' => format_string($quiz->name),
                'gradepass' => (float)($quiz->gradepass ?: 60),
            ],
            'distribution' => $distribution,
            'breakdown' => $breakdown,
            'breakdownkind' => 'topics',
            'statuses' => $statuses,
            'reports' => [
                [
                    'key' => 'quiz_topics',
                    'title' => get_string('evalreportquiztopics', 'local_wamakademi'),
                    'rows' => $breakdown['rows'],
                    'chart' => $breakdown['chart'],
                ],
                [
                    'key' => 'quiz_distribution',
                    'title' => get_string('evalreportquizdist', 'local_wamakademi'),
                    'rows' => $distribution['tablerows'],
                    'chart' => $distribution['chart'],
                    'summary' => $distribution['summary'],
                ],
            ],
        ];
    }

    /**
     * @param array<int,\stdClass> $students
     * @param array<int,?float> $scores
     */
    private static function quiz_topic_breakdown(\stdClass $quiz, \stdClass $cm, array $students, array $scores): array {
        global $DB;

        $ctx = \context_module::instance($cm->id);
        $categories = $DB->get_records_sql(
            "SELECT qc.id, qc.name
               FROM {question_categories} qc
              WHERE qc.contextid = ? AND qc.parent <> 0
           ORDER BY qc.sortorder, qc.name",
            [$ctx->id]
        );

        // Soru → kategori.
        $slotcats = $DB->get_records_sql(
            "SELECT qs.slot, qc.id AS categoryid, qc.name AS categoryname, qs.maxmark
               FROM {quiz_slots} qs
               JOIN {question_references} qr
                 ON qr.itemid = qs.id AND qr.component = 'mod_quiz' AND qr.questionarea = 'slot'
               JOIN {question_bank_entries} qbe ON qbe.id = qr.questionbankentryid
               JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
              WHERE qs.quizid = ?
           ORDER BY qs.slot",
            [$quiz->id]
        );

        $catmeta = [];
        foreach ($categories as $cat) {
            $catmeta[(int)$cat->id] = [
                'id' => (int)$cat->id,
                'name' => format_string($cat->name),
                'possible' => 0.0,
                'earned_total' => 0.0,
                'graded_users' => 0,
            ];
        }
        foreach ($slotcats as $slot) {
            $cid = (int)$slot->categoryid;
            if (!isset($catmeta[$cid])) {
                $catmeta[$cid] = [
                    'id' => $cid,
                    'name' => format_string($slot->categoryname),
                    'possible' => 0.0,
                    'earned_total' => 0.0,
                    'graded_users' => 0,
                ];
            }
            $catmeta[$cid]['possible'] += (float)$slot->maxmark;
        }

        // Öğrenci başına konu: bitmiş denemelerde question_attempts markası.
        foreach ($students as $userid => $user) {
            if ($scores[$userid] === null) {
                continue;
            }
            $attempt = $DB->get_record_sql(
                "SELECT * FROM {quiz_attempts}
                  WHERE quiz = ? AND userid = ? AND preview = 0 AND state = ?
               ORDER BY attempt DESC",
                [$quiz->id, $userid, \mod_quiz\quiz_attempt::FINISHED],
                IGNORE_MULTIPLE
            );
            if (!$attempt) {
                continue;
            }

            $qas = $DB->get_records_sql(
                "SELECT qa.slot, qa.maxmark,
                        (SELECT qas.fraction
                           FROM {question_attempt_steps} qas
                          WHERE qas.questionattemptid = qa.id
                       ORDER BY qas.sequencenumber DESC
                          LIMIT 1) AS fraction
                   FROM {question_attempts} qa
                  WHERE qa.questionusageid = ?",
                [$attempt->uniqueid]
            );
            $earnedbycat = [];
            foreach ($qas as $qa) {
                $slotinfo = null;
                foreach ($slotcats as $sc) {
                    if ((int)$sc->slot === (int)$qa->slot) {
                        $slotinfo = $sc;
                        break;
                    }
                }
                if (!$slotinfo) {
                    continue;
                }
                $cid = (int)$slotinfo->categoryid;
                $earned = ((float)$qa->fraction) * (float)$qa->maxmark;
                $earnedbycat[$cid] = ($earnedbycat[$cid] ?? 0.0) + $earned;
            }
            foreach ($catmeta as $cid => &$meta) {
                if ($meta['possible'] <= 0) {
                    continue;
                }
                $meta['earned_total'] += $earnedbycat[$cid] ?? 0.0;
                $meta['graded_users']++;
            }
            unset($meta);
        }

        $rows = [];
        $chart = [];
        foreach ($catmeta as $meta) {
            if ($meta['possible'] <= 0) {
                continue;
            }
            $pct = 0.0;
            if ($meta['graded_users'] > 0) {
                $avgearned = $meta['earned_total'] / $meta['graded_users'];
                $pct = round(100.0 * $avgearned / $meta['possible'], 1);
            }
            $rows[] = [
                'label' => $meta['name'],
                'value' => $pct,
                'valuelabel' => format_float($pct, 1) . '%',
                'detail' => $meta['graded_users'] . ' puanlanan',
            ];
            $chart[] = [
                'label' => $meta['name'],
                'value' => $pct,
                'width' => max(0, min(100, $pct)),
                'valuelabel' => format_float($pct, 1) . '%',
            ];
        }

        return [
            'rows' => $rows,
            'chart' => $chart,
            'empty' => empty($rows) || array_sum(array_column($rows, 'value')) == 0 && empty(array_filter($scores, fn($s) => $s !== null)),
        ];
    }

    /**
     * @param array<int,\stdClass> $students
     */
    private static function build_assign_reports(\stdClass $course, \stdClass $cm, array $students): array {
        global $DB;

        $assign = $DB->get_record('assign', ['id' => $cm->instance], '*', MUST_EXIST);
        $item = \grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => 'assign',
            'iteminstance' => $assign->id,
            'courseid' => $course->id,
        ]);

        $scores = [];
        $statuses = ['missing' => 0, 'pending' => 0, 'graded' => 0];

        foreach ($students as $userid => $user) {
            $submission = $DB->get_record('assign_submission', [
                'assignment' => $assign->id,
                'userid' => $userid,
                'latest' => 1,
            ]);
            $final = null;
            if ($item) {
                $gg = \grade_grade::fetch(['itemid' => $item->id, 'userid' => $userid]);
                if ($gg && $gg->finalgrade !== null) {
                    $final = self::to_percent((float)$gg->finalgrade, (float)$item->grademax);
                }
            }

            $submitted = $submission && in_array($submission->status, ['submitted', 'reopened'], true);
            if (!$submitted && $final === null) {
                $statuses['missing']++;
                $scores[$userid] = null;
            } else if ($final === null) {
                $statuses['pending']++;
                $scores[$userid] = null;
            } else {
                $statuses['graded']++;
                $scores[$userid] = $final;
            }
        }

        $gradepass = 60.0;
        if ($item && $item->gradepass !== null) {
            $gradepass = self::to_percent((float)$item->gradepass, (float)$item->grademax);
        }

        $distribution = self::make_distribution($scores, $gradepass, count($students));
        $breakdown = self::assign_criteria_breakdown($cm, $students, $scores);

        return [
            'activity' => [
                'type' => 'assign',
                'cmid' => (int)$cm->id,
                'instanceid' => (int)$assign->id,
                'name' => format_string($assign->name),
                'gradepass' => $gradepass,
            ],
            'distribution' => $distribution,
            'breakdown' => $breakdown,
            'breakdownkind' => 'criteria',
            'statuses' => $statuses,
            'reports' => [
                [
                    'key' => 'assign_criteria',
                    'title' => get_string('evalreportassigncriteria', 'local_wamakademi'),
                    'rows' => $breakdown['rows'],
                    'chart' => $breakdown['chart'],
                ],
                [
                    'key' => 'assign_distribution',
                    'title' => get_string('evalreportassigndist', 'local_wamakademi'),
                    'rows' => $distribution['tablerows'],
                    'chart' => $distribution['chart'],
                    'summary' => $distribution['summary'],
                ],
            ],
        ];
    }

    /**
     * @param array<int,\stdClass> $students
     * @param array<int,?float> $scores
     */
    private static function assign_criteria_breakdown(\stdClass $cm, array $students, array $scores): array {
        global $DB;

        $ctx = \context_module::instance($cm->id);
        $manager = get_grading_manager($ctx, 'mod_assign', 'submissions');
        $controller = $manager->get_active_controller();
        if (!$controller || !($controller instanceof \gradingform_rubric_controller) || !$controller->is_form_available()) {
            return ['rows' => [], 'chart' => [], 'empty' => true];
        }

        $definition = $controller->get_definition();
        $critmeta = [];
        foreach ($definition->rubric_criteria as $critid => $crit) {
            $max = 0.0;
            foreach ($crit['levels'] as $level) {
                $max = max($max, (float)$level['score']);
            }
            $critmeta[(int)$critid] = [
                'id' => (int)$critid,
                'name' => format_string($crit['description']),
                'max' => $max,
                'earned_total' => 0.0,
                'graded_users' => 0,
            ];
        }

        foreach ($students as $userid => $user) {
            if ($scores[$userid] === null) {
                continue;
            }
            $agrade = $DB->get_record('assign_grades', [
                'assignment' => $cm->instance,
                'userid' => $userid,
                'attemptnumber' => 0,
            ]);
            if (!$agrade) {
                continue;
            }
            $instance = $DB->get_record('grading_instances', [
                'definitionid' => $definition->id,
                'itemid' => $agrade->id,
            ]);
            if (!$instance) {
                // Bazı kurulumlarda itemid = userid.
                $instance = $DB->get_record('grading_instances', [
                    'definitionid' => $definition->id,
                    'itemid' => $userid,
                ]);
            }
            if (!$instance) {
                continue;
            }
            $fillings = $DB->get_records('gradingform_rubric_fillings', ['instanceid' => $instance->id]);
            $seen = [];
            foreach ($fillings as $filling) {
                $cid = (int)$filling->criterionid;
                if (!isset($critmeta[$cid])) {
                    continue;
                }
                $score = 0.0;
                if (!empty($filling->levelid) && isset($definition->rubric_criteria[$cid]['levels'][$filling->levelid])) {
                    $score = (float)$definition->rubric_criteria[$cid]['levels'][$filling->levelid]['score'];
                }
                $critmeta[$cid]['earned_total'] += $score;
                if (!isset($seen[$cid])) {
                    $critmeta[$cid]['graded_users']++;
                    $seen[$cid] = true;
                }
            }
        }

        $rows = [];
        $chart = [];
        foreach ($critmeta as $meta) {
            if ($meta['max'] <= 0) {
                continue;
            }
            $pct = 0.0;
            if ($meta['graded_users'] > 0) {
                $avg = $meta['earned_total'] / $meta['graded_users'];
                $pct = round(100.0 * $avg / $meta['max'], 1);
            }
            $rows[] = [
                'label' => $meta['name'],
                'value' => $pct,
                'valuelabel' => format_float($pct, 1) . '%',
                'detail' => 'ölçüt / ' . format_float($meta['max'], 0) . ' puan',
            ];
            $chart[] = [
                'label' => $meta['name'],
                'value' => $pct,
                'width' => max(0, min(100, $pct)),
                'valuelabel' => format_float($pct, 1) . '%',
            ];
        }

        return [
            'rows' => $rows,
            'chart' => $chart,
            'empty' => empty($rows),
        ];
    }

    /**
     * @param array<int,?float> $scores userid => percent or null
     */
    private static function make_distribution(array $scores, float $gradepass, int $participantcount): array {
        $graded = [];
        foreach ($scores as $pct) {
            if ($pct === null) {
                continue;
            }
            $graded[] = (float)$pct;
        }

        $buckets = [];
        foreach (self::BUCKETS as $key => $def) {
            $buckets[$key] = [
                'key' => $key,
                'label' => $def['label'],
                'count' => 0,
                'min' => $def['min'],
                'max' => $def['max'],
            ];
        }
        foreach ($graded as $pct) {
            foreach ($buckets as $key => &$bucket) {
                if ($pct >= $bucket['min'] && $pct < $bucket['max']) {
                    $bucket['count']++;
                    break;
                }
            }
            unset($bucket);
        }

        $gradedcount = count($graded);
        $passed = 0;
        foreach ($graded as $pct) {
            if ($pct >= $gradepass) {
                $passed++;
            }
        }
        $avg = $gradedcount ? round(array_sum($graded) / $gradedcount, 1) : null;
        $passpct = $gradedcount ? round(100.0 * $passed / $gradedcount, 1) : null;

        $chart = [];
        $maxcount = max(1, ...array_column($buckets, 'count'));
        foreach ($buckets as $bucket) {
            $chart[] = [
                'label' => $bucket['label'],
                'value' => $bucket['count'],
                'width' => round(100.0 * $bucket['count'] / $maxcount, 1),
                'valuelabel' => (string)$bucket['count'],
            ];
        }

        $tablerows = [];
        foreach ($buckets as $bucket) {
            $tablerows[] = [
                'label' => $bucket['label'],
                'value' => $bucket['count'],
                'valuelabel' => (string)$bucket['count'],
                'detail' => '',
            ];
        }

        return [
            'buckets' => array_values($buckets),
            'chart' => $chart,
            'tablerows' => $tablerows,
            'scores' => $graded,
            'pass' => [
                'passed' => $passed,
                'graded' => $gradedcount,
                'percent' => $passpct,
                'threshold' => $gradepass,
            ],
            'summary' => [
                'participants' => $participantcount,
                'graded' => $gradedcount,
                'average' => $avg,
                'passlabel' => $gradedcount
                    ? "Geçen: {$passed}/{$gradedcount} (%" . format_float($passpct, 1) . ')'
                    : 'Geçen: 0/0',
            ],
            'empty' => $gradedcount === 0,
        ];
    }

    /**
     * @param array $distribution make_distribution çıktısı
     * @return list<float>
     */
    public static function graded_percentages(array $distribution): array {
        return array_map('floatval', $distribution['scores'] ?? []);
    }

    private static function to_percent(float $raw, float $max): float {
        if ($max <= 0) {
            return 0.0;
        }
        return round(100.0 * $raw / $max, 2);
    }
}
