<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — değerlendirme demosu kurulumu (idempotent).

namespace local_wamakademi;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/lib.php');
require_once($CFG->dirroot . '/grade/grading/lib.php');
require_once($CFG->libdir . '/gradelib.php');

/**
 * Excel kursuna demo bölüm, öğrenciler, sınav ve rubrikli ödev kurar.
 */
final class demo_setup {

    /**
     * @return array{course:\stdClass, messages:string[]}
     */
    public static function run(): array {
        global $DB, $CFG;

        $guard = demo_env::assert_safe();
        if ($guard !== true) {
            throw new \moodle_exception('error', 'error', '', null, 'Demo kilidi: ' . $guard);
        }

        global $CFG;
        $CFG->noemailever = true;

        $messages = [];
        $course = demo_env::find_demo_course();
        if (!$course) {
            throw new \moodle_exception('error', 'error', '', null,
                'Kurs bulunamadı: ' . demo_env::COURSE_SHORTNAME);
        }
        $messages[] = 'Kurs: ' . $course->fullname . ' (id=' . $course->id . ')';

        $admin = get_admin();
        \core\session\manager::set_user($admin);

        $sectionnum = self::ensure_section($course, $messages);
        self::ensure_teacher($course, $messages);
        $students = self::ensure_students($course, $messages);
        $quiz = self::ensure_quiz($course, $sectionnum, $messages);
        $assign = self::ensure_assign($course, $sectionnum, $messages);

        $messages[] = 'Quiz cmid=' . $quiz->cmid . ' id=' . $quiz->id;
        $messages[] = 'Assign cmid=' . $assign->cmid . ' id=' . $assign->id;
        $messages[] = 'Demo öğrenciler (notsuz): ' . implode(', ', array_keys($students));

        return ['course' => $course, 'messages' => $messages, 'quiz' => $quiz, 'assign' => $assign];
    }

    /**
     * Geçici kullanıcılarla rapor hesabını doğrular; ardından siler.
     *
     * @return string[]
     */
    public static function verify_and_cleanup(\stdClass $course, \stdClass $quiz, \stdClass $assign): array {
        global $DB, $CFG;
        $CFG->noemailever = true;

        $guard = demo_env::assert_safe();
        if ($guard !== true) {
            throw new \moodle_exception('error', 'error', '', null, 'Demo kilidi: ' . $guard);
        }

        $admin = get_admin();
        \core\session\manager::set_user($admin);

        $lines = [];
        $prefix = 'wam_verify_';
        $specs = [
            'ungraded' => ['label' => 'puan bekliyor', 'grade' => null],
            'zero' => ['label' => 'gerçek 0', 'grade' => 0.0],
            'full' => ['label' => 'tam puan', 'grade' => 100.0],
            'pass' => ['label' => 'tam 60', 'grade' => 60.0],
        ];

        $created = [];
        foreach ($specs as $key => $spec) {
            $username = $prefix . $key;
            if ($existing = $DB->get_record('user', ['username' => $username, 'deleted' => 0])) {
                delete_user($existing);
            }
            $user = (object) [
                'username' => $username,
                'password' => demo_env::DEMO_PASSWORD,
                'firstname' => 'Verify',
                'lastname' => $spec['label'],
                'email' => $username . '@example.local',
                'auth' => 'manual',
                'confirmed' => 1,
                'mnethostid' => $CFG->mnet_localhost_id ?? 1,
            ];
            $user->id = user_create_user($user, false, false);
            update_internal_user_password($user, demo_env::DEMO_PASSWORD);
            self::enrol_as_student($course->id, (int)$user->id);
            $created[$key] = $user;
            $lines[] = 'Doğrulama kullanıcısı oluşturuldu: ' . $username;
        }

        // Quiz: deneme durumları + gradebook.
        foreach ($created as $key => $user) {
            $grade = $specs[$key]['grade'];
            self::seed_quiz_attempt((int)$quiz->id, (int)$user->id, $grade);
            self::seed_assign_submission((int)$assign->id, (int)$assign->cmid, (int)$user->id, $grade);
        }

        $quizreport = eval_report::build_activity_reports($course, 'quiz', (int)$quiz->cmid);
        $assignreport = eval_report::build_activity_reports($course, 'assign', (int)$assign->cmid);

        $quizgrades = eval_report::graded_percentages($quizreport['distribution']);
        $assigngrades = eval_report::graded_percentages($assignreport['distribution']);

        $expected = [0.0, 100.0, 60.0]; // ungraded excluded
        sort($expected);
        $qg = $quizgrades;
        sort($qg);
        $ag = $assigngrades;
        sort($ag);

        if ($qg !== $expected) {
            throw new \moodle_exception('error', 'error', '', null,
                'Quiz dağılım uyuşmazlığı: ' . json_encode($qg) . ' vs ' . json_encode($expected));
        }
        if ($ag !== $expected) {
            throw new \moodle_exception('error', 'error', '', null,
                'Ödev dağılım uyuşmazlığı: ' . json_encode($ag) . ' vs ' . json_encode($expected));
        }

        // Gradebook çapraz kontrol.
        foreach (['quiz' => $quiz, 'assign' => $assign] as $mod => $modrec) {
            $item = \grade_item::fetch([
                'itemtype' => 'mod',
                'itemmodule' => $mod,
                'iteminstance' => $modrec->id,
                'courseid' => $course->id,
            ]);
            if (!$item) {
                throw new \moodle_exception('error', 'error', '', null, 'grade_item yok: ' . $mod);
            }
            foreach ($created as $key => $user) {
                $gg = \grade_grade::fetch(['itemid' => $item->id, 'userid' => $user->id]);
                $final = ($gg && $gg->finalgrade !== null) ? (float)$gg->finalgrade : null;
                $want = $specs[$key]['grade'];
                if ($want === null) {
                    if ($final !== null) {
                        // Puanlanmamış: finalgrade boş olmalı veya rawgrade yok sayılmalı.
                        // seed fonksiyonu grade yazmadıysa null bekleriz.
                    }
                } else if ($final === null || abs($final - $want) > 0.01) {
                    throw new \moodle_exception('error', 'error', '', null,
                        "grade_grades uyuşmazlığı {$mod}/{$key}: got={$final} want={$want}");
                }
            }
        }

        // Kovalar: 0 → 0-<40, 60 → 60-<80, 100 → 80-100
        $buckets = $quizreport['distribution']['buckets'];
        $bykey = [];
        foreach ($buckets as $b) {
            $bykey[$b['key']] = (int)$b['count'];
        }
        if (($bykey['0_40'] ?? 0) !== 1 || ($bykey['60_80'] ?? 0) !== 1 || ($bykey['80_100'] ?? 0) !== 1) {
            throw new \moodle_exception('error', 'error', '', null,
                'Kova sayıları hatalı: ' . json_encode($bykey));
        }

        $pass = $quizreport['distribution']['pass'];
        if ((int)$pass['passed'] !== 2 || (int)$pass['graded'] !== 3) {
            throw new \moodle_exception('error', 'error', '', null,
                'Geçme sayıları hatalı: ' . json_encode($pass));
        }

        $lines[] = 'Quiz dağılım OK: ' . json_encode($qg);
        $lines[] = 'Ödev dağılım OK: ' . json_encode($ag);
        $lines[] = 'Kovalar OK: ' . json_encode($bykey);
        $lines[] = 'Geçen: ' . $pass['passed'] . '/' . $pass['graded'];

        // Temizlik.
        foreach ($created as $user) {
            delete_user($user);
            $lines[] = 'Silindi: ' . $user->username;
        }

        // Demo öğrencilerin notsuz kaldığını doğrula.
        foreach (demo_env::student_usernames() as $uname) {
            $u = $DB->get_record('user', ['username' => $uname, 'deleted' => 0], '*', MUST_EXIST);
            foreach (['quiz' => $quiz, 'assign' => $assign] as $mod => $modrec) {
                $item = \grade_item::fetch([
                    'itemtype' => 'mod',
                    'itemmodule' => $mod,
                    'iteminstance' => $modrec->id,
                    'courseid' => $course->id,
                ]);
                $gg = \grade_grade::fetch(['itemid' => $item->id, 'userid' => $u->id]);
                if ($gg && $gg->finalgrade !== null) {
                    throw new \moodle_exception('error', 'error', '', null,
                        "Demo öğrenci {$uname} {$mod} notu yazılmış: {$gg->finalgrade}");
                }
            }
        }
        $lines[] = 'Demo öğrenciler notsuz kaldı.';

        return $lines;
    }

    private static function ensure_section(\stdClass $course, array &$messages): int {
        global $DB;

        $modinfo = get_fast_modinfo($course);
        foreach ($modinfo->get_section_info_all() as $section) {
            if ((int)$section->section === 0) {
                continue;
            }
            $name = $section->name ?? '';
            if ($name === demo_env::SECTION_NAME) {
                $messages[] = 'Bölüm mevcut: ' . demo_env::SECTION_NAME . ' (#' . $section->section . ')';
                return (int)$section->section;
            }
        }

        $last = 0;
        foreach ($modinfo->get_section_info_all() as $section) {
            $last = max($last, (int)$section->section);
        }
        $newnum = $last + 1;
        course_create_sections_if_missing($course, [$newnum]);
        rebuild_course_cache($course->id, true);
        $modinfo = get_fast_modinfo($course);
        $created = $modinfo->get_section_info($newnum);
        course_update_section($course, $created, ['name' => demo_env::SECTION_NAME]);
        rebuild_course_cache($course->id, true);
        $messages[] = 'Bölüm oluşturuldu: ' . demo_env::SECTION_NAME . ' (#' . $newnum . ')';
        return $newnum;
    }

    private static function ensure_teacher(\stdClass $course, array &$messages): void {
        global $DB;

        $teacher = $DB->get_record('user', ['username' => demo_env::TEACHER_USERNAME, 'deleted' => 0]);
        if (!$teacher) {
            $messages[] = 'Uyarı: ' . demo_env::TEACHER_USERNAME . ' yok; atlandı.';
            return;
        }

        $context = \context_course::instance($course->id);
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        if (!user_has_role_assignment($teacher->id, $roleid, $context->id)) {
            self::enrol_with_role($course->id, (int)$teacher->id, (int)$roleid);
            $messages[] = 'Eğitmen kaydı eklendi: ' . demo_env::TEACHER_USERNAME;
        } else {
            $messages[] = 'Eğitmen zaten kayıtlı: ' . demo_env::TEACHER_USERNAME;
        }

        if (!has_capability('mod/assign:grade', $context, $teacher->id)) {
            $messages[] = 'Uyarı: egitmenin mod/assign:grade yetkisi yok (rol tanımı değiştirilmedi).';
        }
        if (!has_capability('mod/quiz:viewreports', $context, $teacher->id)) {
            $messages[] = 'Uyarı: egitmenin mod/quiz:viewreports yetkisi yok (rol tanımı değiştirilmedi).';
        }
    }

    /**
     * @return array<string,\stdClass> username => user
     */
    private static function ensure_students(\stdClass $course, array &$messages): array {
        global $DB, $CFG;

        $roleid = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
        $out = [];
        foreach (demo_env::STUDENTS as $spec) {
            $user = $DB->get_record('user', ['username' => $spec['username'], 'deleted' => 0]);
            if (!$user) {
                $user = (object) [
                    'username' => $spec['username'],
                    'password' => demo_env::DEMO_PASSWORD,
                    'firstname' => $spec['firstname'],
                    'lastname' => $spec['lastname'],
                    'email' => $spec['username'] . '@example.local',
                    'auth' => 'manual',
                    'confirmed' => 1,
                    'mnethostid' => $CFG->mnet_localhost_id ?? 1,
                ];
                $user->id = user_create_user($user, false, false);
                update_internal_user_password($user, demo_env::DEMO_PASSWORD);
                $messages[] = 'Öğrenci oluşturuldu: ' . $spec['username'];
            } else {
                $messages[] = 'Öğrenci mevcut: ' . $spec['username'];
            }
            self::enrol_with_role($course->id, (int)$user->id, (int)$roleid);
            $out[$spec['username']] = $user;
        }
        return $out;
    }

    private static function enrol_as_student(int $courseid, int $userid): void {
        global $DB;
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
        self::enrol_with_role($courseid, $userid, (int)$roleid);
    }

    private static function enrol_with_role(int $courseid, int $userid, int $roleid): void {
        global $DB;

        $instance = $DB->get_record('enrol', ['enrol' => 'manual', 'courseid' => $courseid], '*', IGNORE_MISSING);
        if (!$instance) {
            $plugin = enrol_get_plugin('manual');
            $course = get_course($courseid);
            $instanceid = $plugin->add_instance($course);
            $instance = $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
        }

        $context = \context_course::instance($courseid);
        $already = $DB->record_exists('user_enrolments', [
            'enrolid' => $instance->id,
            'userid' => $userid,
        ]);
        if ($already) {
            // Moodle 5: enrol_user her çağrıda after_user_enrolled → hoş geldin bildirimi üretir.
            // Tekrar kayıtta yalnızca rolü güvenceye al.
            if (!user_has_role_assignment($userid, $roleid, $context->id)) {
                role_assign($roleid, $userid, $context->id, 'enrol_manual', $instance->id);
            }
            return;
        }

        $plugin = enrol_get_plugin('manual');
        $plugin->enrol_user($instance, $userid, $roleid);
    }

    /**
     * @return \stdClass quiz record + cmid
     */
    private static function ensure_quiz(\stdClass $course, int $sectionnum, array &$messages): \stdClass {
        global $DB;

        $cm = self::find_cm_by_idnumber($course->id, 'quiz', demo_env::QUIZ_IDNUMBER);
        if ($cm) {
            $quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
            $quiz->cmid = (int)$cm->id;
            $messages[] = 'Sınav mevcut: ' . demo_env::QUIZ_NAME;
            self::ensure_quiz_questions($course, $quiz, $messages);
            return $quiz;
        }

        $moduleinfo = self::base_moduleinfo($course, $sectionnum, 'quiz');
        $moduleinfo->name = demo_env::QUIZ_NAME;
        $moduleinfo->intro = '<p>Excel temelleri bilgi kontrolü. Her soru eşit ağırlıklıdır.</p>';
        $moduleinfo->introformat = FORMAT_HTML;
        $moduleinfo->cmidnumber = demo_env::QUIZ_IDNUMBER;
        $moduleinfo->grade = 100;
        $moduleinfo->gradepass = 60;
        $moduleinfo->attempts = 1;
        $moduleinfo->preferredbehaviour = 'deferredfeedback';
        $moduleinfo->quizpassword = '';
        $moduleinfo->subnet = '';
        $moduleinfo->timeopen = 0;
        $moduleinfo->timeclose = 0;
        $moduleinfo->timelimit = 0;
        $moduleinfo->overduehandling = 'autosubmit';
        $moduleinfo->graceperiod = 0;
        $moduleinfo->grademethod = QUIZ_GRADEHIGHEST;
        $moduleinfo->decimalpoints = 2;
        $moduleinfo->questiondecimalpoints = -1;
        $moduleinfo->reviewattempt = 69888;
        $moduleinfo->reviewcorrectness = 4352;
        $moduleinfo->reviewmaxmarks = 4352;
        $moduleinfo->reviewmarks = 4352;
        $moduleinfo->reviewspecificfeedback = 4352;
        $moduleinfo->reviewgeneralfeedback = 4352;
        $moduleinfo->reviewrightanswer = 4352;
        $moduleinfo->reviewoverallfeedback = 4352;
        $moduleinfo->questionsperpage = 1;
        $moduleinfo->navmethod = QUIZ_NAVMETHOD_FREE;
        $moduleinfo->shuffleanswers = 0;
        $moduleinfo->sumgrades = 0;
        $moduleinfo->browsersecurity = '-';
        $moduleinfo->delay1 = 0;
        $moduleinfo->delay2 = 0;
        $moduleinfo->showuserpicture = 0;
        $moduleinfo->showblocks = 0;

        $created = add_moduleinfo($moduleinfo, $course);
        $quiz = $DB->get_record('quiz', ['id' => $created->instance], '*', MUST_EXIST);
        $quiz->cmid = (int)$created->coursemodule;
        $messages[] = 'Sınav oluşturuldu: ' . demo_env::QUIZ_NAME;
        self::ensure_quiz_questions($course, $quiz, $messages);
        return $quiz;
    }

    private static function ensure_quiz_questions(\stdClass $course, \stdClass $quiz, array &$messages): void {
        global $DB;

        $cmid = (int)$quiz->cmid;
        $ctx = \context_module::instance($cmid);
        $top = question_get_top_category($ctx->id, true);

        $topics = [
            demo_env::TOPIC_FORMULAS => self::formula_questions(),
            demo_env::TOPIC_ANALYSIS => self::analysis_questions(),
            demo_env::TOPIC_CHARTS => self::chart_questions(),
        ];

        $slotcount = $DB->count_records('quiz_slots', ['quizid' => $quiz->id]);
        if ($slotcount >= 9) {
            $messages[] = 'Sınav soruları mevcut (' . $slotcount . ' slot).';
            return;
        }

        $qtype = \question_bank::get_qtype('multichoice');
        $page = 0;
        foreach ($topics as $topicname => $questions) {
            $cat = $DB->get_record('question_categories', [
                'contextid' => $ctx->id,
                'name' => $topicname,
                'parent' => $top->id,
            ]);
            if (!$cat) {
                $cat = (object) [
                    'name' => $topicname,
                    'info' => 'Demo konu bankası: ' . $topicname,
                    'infoformat' => FORMAT_HTML,
                    'contextid' => $ctx->id,
                    'parent' => $top->id,
                    'sortorder' => 1,
                    'stamp' => make_unique_id_code(),
                ];
                $cat->id = $DB->insert_record('question_categories', $cat);
            }

            foreach ($questions as $qdata) {
                $existing = $DB->get_record_sql(
                    "SELECT q.id
                       FROM {question} q
                       JOIN {question_versions} qv ON qv.questionid = q.id
                       JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                      WHERE qbe.questioncategoryid = ? AND q.name = ?",
                    [$cat->id, $qdata['name']]
                );
                if ($existing) {
                    $qid = (int)$existing->id;
                } else {
                    $fromform = self::multichoice_form($cat->id, $qdata);
                    $question = new \stdClass();
                    $question->qtype = 'multichoice';
                    $question->id = 0;
                    $saved = $qtype->save_question($question, $fromform);
                    $qid = (int)$saved->id;
                }

                if (!$DB->record_exists_sql(
                    "SELECT 1 FROM {quiz_slots} qs
                       JOIN {question_references} qr ON qr.itemid = qs.id
                            AND qr.component = 'mod_quiz' AND qr.questionarea = 'slot'
                       JOIN {question_bank_entries} qbe ON qbe.id = qr.questionbankentryid
                       JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                      WHERE qs.quizid = ? AND qv.questionid = ?",
                    [$quiz->id, $qid]
                )) {
                    quiz_add_quiz_question($qid, $quiz, $page);
                    $page++;
                }
            }
        }

        \mod_quiz\quiz_settings::create((int)$quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
        $quiz->sumgrades = $DB->get_field('quiz', 'sumgrades', ['id' => $quiz->id]);
        // Eşit ağırlık: her soru 1 mark; not 100.
        $DB->set_field('quiz', 'grade', 100, ['id' => $quiz->id]);
        grade_update('mod/quiz', $course->id, 'mod', 'quiz', $quiz->id, 0, null, [
            'gradetype' => GRADE_TYPE_VALUE,
            'grademax' => 100,
            'grademin' => 0,
            'gradepass' => 60,
        ]);
        $messages[] = 'Sınav soruları hazır (9 çoktan seçmeli, 3 konu).';
    }

    /**
     * @return \stdClass assign + cmid
     */
    private static function ensure_assign(\stdClass $course, int $sectionnum, array &$messages): \stdClass {
        global $DB;

        $cm = self::find_cm_by_idnumber($course->id, 'assign', demo_env::ASSIGN_IDNUMBER);
        if ($cm) {
            $assign = $DB->get_record('assign', ['id' => $cm->instance], '*', MUST_EXIST);
            $assign->cmid = (int)$cm->id;
            $messages[] = 'Ödev mevcut: ' . demo_env::ASSIGN_NAME;
            self::ensure_rubric($assign, $messages);
            return $assign;
        }

        $intro = '<p>Aşağıdaki satış tablosunu Excel’de analiz edin. Formül, özet ve bir grafik ekleyin; dosyayı teslim edin.</p>'
            . '<table border="1" cellpadding="4"><thead><tr><th>Ürün</th><th>Q1</th><th>Q2</th><th>Q3</th></tr></thead>'
            . '<tbody>'
            . '<tr><td>Kalem</td><td>120</td><td>135</td><td>150</td></tr>'
            . '<tr><td>Defter</td><td>80</td><td>95</td><td>110</td></tr>'
            . '<tr><td>Silgi</td><td>40</td><td>42</td><td>55</td></tr>'
            . '</tbody></table>';

        $moduleinfo = self::base_moduleinfo($course, $sectionnum, 'assign');
        $moduleinfo->name = demo_env::ASSIGN_NAME;
        $moduleinfo->intro = $intro;
        $moduleinfo->introformat = FORMAT_HTML;
        $moduleinfo->cmidnumber = demo_env::ASSIGN_IDNUMBER;
        $moduleinfo->grade = 100;
        $moduleinfo->gradepass = 60;
        $moduleinfo->gradingduedate = 0;
        $moduleinfo->duedate = 0;
        $moduleinfo->allowsubmissionsfromdate = 0;
        $moduleinfo->cutoffdate = 0;
        $moduleinfo->alwaysshowdescription = 1;
        $moduleinfo->submissiondrafts = 0;
        $moduleinfo->requiresubmissionstatement = 0;
        $moduleinfo->sendnotifications = 0;
        $moduleinfo->sendlatenotifications = 0;
        $moduleinfo->sendstudentnotifications = 0;
        $moduleinfo->teamsubmission = 0;
        $moduleinfo->requireallteammemberssubmit = 0;
        $moduleinfo->teamsubmissiongroupingid = 0;
        $moduleinfo->blindmarking = 0;
        $moduleinfo->hidegrader = 0;
        $moduleinfo->markingworkflow = 0;
        $moduleinfo->markingallocation = 0;
        $moduleinfo->assignsubmission_file_enabled = 1;
        $moduleinfo->assignsubmission_file_maxfiles = 2;
        $moduleinfo->assignsubmission_file_maxsizebytes = 10485760;
        $moduleinfo->assignsubmission_onlinetext_enabled = 0;
        $moduleinfo->assignfeedback_comments_enabled = 1;
        $moduleinfo->assignfeedback_file_enabled = 0;

        $created = add_moduleinfo($moduleinfo, $course);
        $assign = $DB->get_record('assign', ['id' => $created->instance], '*', MUST_EXIST);
        $assign->cmid = (int)$created->coursemodule;

        grade_update('mod/assign', $course->id, 'mod', 'assign', $assign->id, 0, null, [
            'gradetype' => GRADE_TYPE_VALUE,
            'grademax' => 100,
            'grademin' => 0,
            'gradepass' => 60,
        ]);

        $messages[] = 'Ödev oluşturuldu: ' . demo_env::ASSIGN_NAME;
        self::ensure_rubric($assign, $messages);
        return $assign;
    }

    private static function ensure_rubric(\stdClass $assign, array &$messages): void {
        global $USER;

        $ctx = \context_module::instance((int)$assign->cmid);
        $manager = get_grading_manager($ctx, 'mod_assign', 'submissions');
        $manager->set_active_method('rubric');
        /** @var \gradingform_rubric_controller $controller */
        $controller = $manager->get_controller('rubric');

        if ($controller->is_form_available()) {
            $messages[] = 'Rubrik mevcut.';
            return;
        }

        $ne = 0;
        $criteria = [];
        $add = function (string $desc, array $levels) use (&$ne, &$criteria) {
            $ne++;
            $leveldata = [];
            $li = 0;
            foreach ($levels as $definition => $score) {
                $li++;
                $leveldata['NEWID' . $li] = [
                    'score' => $score,
                    'definition' => $definition,
                ];
            }
            $criteria['NEWID' . $ne] = [
                'sortorder' => $ne,
                'description' => $desc,
                'levels' => $leveldata,
            ];
        };

        $add('Formül doğruluğu', [
            'Yanlış veya eksik formüller' => 0,
            'Kısmen doğru formüller' => 20,
            'Doğru ve tutarlı formüller' => 40,
        ]);
        $add('Veri analizi', [
            'Analiz yok' => 0,
            'Temel özet var' => 20,
            'Doğru ve anlamlı analiz' => 40,
        ]);
        $add('Grafik ve yorum', [
            'Grafik yok' => 0,
            'Grafik var, yorum zayıf' => 10,
            'Uygun grafik ve net yorum' => 20,
        ]);

        $definition = (object) [
            'name' => 'Satış analizi rubriği',
            'description_editor' => [
                'text' => 'Demo ödev rubriği (40 / 40 / 20).',
                'format' => FORMAT_HTML,
                'itemid' => file_get_unused_draft_itemid(),
            ],
            'rubric' => [
                'criteria' => $criteria,
                'options' => \gradingform_rubric_controller::get_default_options(),
            ],
            'status' => \gradingform_controller::DEFINITION_STATUS_READY,
        ];

        $controller->update_definition($definition, $USER->id ?: get_admin()->id);
        $messages[] = 'Rubrik oluşturuldu (40/40/20).';
    }

    private static function find_cm_by_idnumber(int $courseid, string $modname, string $idnumber): ?\stdClass {
        global $DB;
        return $DB->get_record_sql(
            "SELECT cm.*
               FROM {course_modules} cm
               JOIN {modules} m ON m.id = cm.module
              WHERE cm.course = ? AND m.name = ? AND cm.idnumber = ?",
            [$courseid, $modname, $idnumber]
        ) ?: null;
    }

    private static function base_moduleinfo(\stdClass $course, int $sectionnum, string $modname): \stdClass {
        global $DB;
        $moduleinfo = new \stdClass();
        $moduleinfo->modulename = $modname;
        $moduleinfo->module = $DB->get_field('modules', 'id', ['name' => $modname], MUST_EXIST);
        $moduleinfo->section = $sectionnum;
        $moduleinfo->visible = 1;
        $moduleinfo->course = $course->id;
        $moduleinfo->completion = 0;
        $moduleinfo->completionexpected = 0;
        $moduleinfo->availabilityconditionsjson = '';
        return $moduleinfo;
    }

    /**
     * @param array{name:string,text:string,answers:list<string>,correct:int} $qdata
     */
    private static function multichoice_form(int $catid, array $qdata): \stdClass {
        $answers = [];
        $fractions = [];
        $feedback = [];
        foreach ($qdata['answers'] as $i => $atext) {
            $answers[$i] = ['text' => $atext, 'format' => FORMAT_HTML];
            $fractions[$i] = ($i === $qdata['correct']) ? '1.0' : '0.0';
            $feedback[$i] = ['text' => '', 'format' => FORMAT_HTML];
        }
        return (object) [
            'category' => $catid,
            'name' => $qdata['name'],
            'questiontext' => ['text' => $qdata['text'], 'format' => FORMAT_HTML],
            'generalfeedback' => ['text' => '', 'format' => FORMAT_HTML],
            'defaultmark' => 1.0,
            'penalty' => 0,
            'qtype' => 'multichoice',
            'single' => 1,
            'shuffleanswers' => 0,
            'answernumbering' => 'abc',
            'showstandardinstruction' => 0,
            'correctfeedback' => ['text' => 'Doğru', 'format' => FORMAT_HTML],
            'partiallycorrectfeedback' => ['text' => '', 'format' => FORMAT_HTML],
            'incorrectfeedback' => ['text' => 'Yanlış', 'format' => FORMAT_HTML],
            'answer' => $answers,
            'fraction' => $fractions,
            'feedback' => $feedback,
            'status' => \core_question\local\bank\question_version_status::QUESTION_STATUS_READY,
        ];
    }

    /** @return list<array{name:string,text:string,answers:list<string>,correct:int}> */
    private static function formula_questions(): array {
        return [
            [
                'name' => 'SUM işlevi',
                'text' => 'A1:A3 aralığının toplamı için doğru formül hangisidir?',
                'answers' => ['=TOPLA(A1:A3)', '=SUM(A1:A3)', '=ADD(A1:A3)', '=TOTAL(A1:A3)'],
                'correct' => 1,
            ],
            [
                'name' => 'Mutlak başvuru',
                'text' => 'Bir hücreyi kopyalarken satır ve sütunu sabitlemek için hangi yazım kullanılır?',
                'answers' => ['A1', '$A$1', 'A$1$', '#A#1'],
                'correct' => 1,
            ],
            [
                'name' => 'IF mantığı',
                'text' => 'B2≥60 ise "Geçti", aksi halde "Kaldı" yazan formül hangisidir?',
                'answers' => [
                    '=IF(B2>=60;"Geçti";"Kaldı")',
                    '=WHEN(B2>=60;"Geçti";"Kaldı")',
                    '=IFTHEN(B2;60;"Geçti")',
                    '=CHECK(B2>=60)',
                ],
                'correct' => 0,
            ],
        ];
    }

    /** @return list<array{name:string,text:string,answers:list<string>,correct:int}> */
    private static function analysis_questions(): array {
        return [
            [
                'name' => 'Sıralama',
                'text' => 'Satış tutarını büyükten küçüğe dizmek için hangi işlem kullanılır?',
                'answers' => ['Filtrele', 'Sırala (azalan)', 'Birleştir', 'Dondur'],
                'correct' => 1,
            ],
            [
                'name' => 'Ortalama',
                'text' => 'Sayısal bir sütunun ortalaması için uygun işlev hangisidir?',
                'answers' => ['COUNT', 'AVERAGE', 'MAX', 'LEN'],
                'correct' => 1,
            ],
            [
                'name' => 'Pivot özeti',
                'text' => 'Kategoriye göre toplam satışı hızlı özetlemek için hangisi uygundur?',
                'answers' => ['Özet tablo (Pivot)', 'Yalnızca yazı tipi', 'Hücre birleştir', 'Yorum ekle'],
                'correct' => 0,
            ],
        ];
    }

    /** @return list<array{name:string,text:string,answers:list<string>,correct:int}> */
    private static function chart_questions(): array {
        return [
            [
                'name' => 'Çubuk grafik',
                'text' => 'Kategoriler arası büyüklük karşılaştırması için en uygun grafik türü hangisidir?',
                'answers' => ['Pasta', 'Çubuk / sütun', 'Radar', 'Kabarcık'],
                'correct' => 1,
            ],
            [
                'name' => 'Pasta payı',
                'text' => 'Bir bütünün parçalarını göstermek için hangisi tercih edilir?',
                'answers' => ['Çizgi', 'Pasta', 'Dağılım', 'Alan'],
                'correct' => 1,
            ],
            [
                'name' => 'Eksen etiketi',
                'text' => 'Grafikte okunabilirlik için hangisi önemlidir?',
                'answers' => [
                    'Eksen ve veri etiketleri',
                    'Rastgele renk',
                    'Başlık silmek',
                    'Veriyi gizlemek',
                ],
                'correct' => 0,
            ],
        ];
    }

    private static function seed_quiz_attempt(int $quizid, int $userid, ?float $grade): void {
        global $DB;

        $quiz = $DB->get_record('quiz', ['id' => $quizid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('quiz', $quizid, 0, false, MUST_EXIST);
        $quba = \question_engine::make_questions_usage_by_activity('mod_quiz', \context_module::instance($cm->id));
        $quba->set_preferred_behaviour($quiz->preferredbehaviour);
        \question_engine::save_questions_usage_by_activity($quba);

        $attempt = (object) [
            'quiz' => $quizid,
            'userid' => $userid,
            'attempt' => 1,
            'uniqueid' => $quba->get_id(),
            'layout' => '',
            'currentpage' => 0,
            'preview' => 0,
            'state' => $grade === null ? \mod_quiz\quiz_attempt::IN_PROGRESS : \mod_quiz\quiz_attempt::FINISHED,
            'timestart' => time() - 60,
            'timefinish' => $grade === null ? 0 : time(),
            'timemodified' => time(),
            'timemodifiedoffline' => 0,
            'timecheckstate' => null,
            'sumgrades' => $grade === null ? null : ($grade / 100) * (float)$quiz->sumgrades,
        ];
        $attempt->id = $DB->insert_record('quiz_attempts', $attempt);

        if ($grade !== null) {
            grade_update('mod/quiz', $cm->course, 'mod', 'quiz', $quizid, 0, (object) [
                'userid' => $userid,
                'rawgrade' => $grade,
            ]);
        }
    }

    private static function seed_assign_submission(int $assignid, int $cmid, int $userid, ?float $grade): void {
        global $DB;

        $submission = (object) [
            'assignment' => $assignid,
            'userid' => $userid,
            'timecreated' => time(),
            'timemodified' => time(),
            'status' => 'submitted',
            'groupid' => 0,
            'attemptnumber' => 0,
            'latest' => 1,
        ];
        $submission->id = $DB->insert_record('assign_submission', $submission);

        if ($grade === null) {
            return;
        }

        $agrade = (object) [
            'assignment' => $assignid,
            'userid' => $userid,
            'timecreated' => time(),
            'timemodified' => time(),
            'grader' => get_admin()->id,
            'grade' => $grade,
            'attemptnumber' => 0,
        ];
        $agrade->id = $DB->insert_record('assign_grades', $agrade);

        $cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
        grade_update('mod/assign', $cm->course, 'mod', 'assign', $assignid, 0, (object) [
            'userid' => $userid,
            'rawgrade' => $grade,
        ]);

        // Rubrik filling: oranla seviye seç.
        $ctx = \context_module::instance($cmid);
        $manager = get_grading_manager($ctx, 'mod_assign', 'submissions');
        /** @var \gradingform_rubric_controller $controller */
        $controller = $manager->get_controller('rubric');
        if (!$controller->is_form_available()) {
            return;
        }
        $definition = $controller->get_definition();
        $instance = $controller->create_instance($userid, $agrade->id);
        $filling = ['criteria' => []];
        foreach ($definition->rubric_criteria as $critid => $crit) {
            $max = 0;
            $bestlevel = null;
            $zeroLevel = null;
            foreach ($crit['levels'] as $levelid => $level) {
                $score = (float)$level['score'];
                if ($score > $max) {
                    $max = $score;
                    $bestlevel = $levelid;
                }
                if ($score == 0.0) {
                    $zeroLevel = $levelid;
                }
            }
            $target = ($grade / 100.0) * $max;
            $chosen = $zeroLevel ?? $bestlevel;
            $bestdiff = PHP_FLOAT_MAX;
            foreach ($crit['levels'] as $levelid => $level) {
                $diff = abs((float)$level['score'] - $target);
                if ($diff < $bestdiff) {
                    $bestdiff = $diff;
                    $chosen = $levelid;
                }
            }
            $filling['criteria'][$critid] = [
                'levelid' => $chosen,
                'remark' => '',
            ];
        }
        $instance->update($filling);
    }
}
