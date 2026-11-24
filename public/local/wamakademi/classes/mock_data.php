<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — yerel mock eğitim / ilerleme / değerlendirme verisi.

namespace local_wamakademi;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->libdir . '/resourcelib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/grade/grading/lib.php');

/**
 * Katalog, içerik, ilerleme, ödev ve sınav mock verisini kurar (idempotent).
 */
final class mock_data {

    /**
     * @return string[]
     */
    public static function run(): array {
        global $CFG;

        $guard = demo_env::assert_safe();
        if ($guard !== true) {
            throw new \moodle_exception('error', 'error', '', null, 'Demo kilidi: ' . $guard);
        }
        $CFG->noemailever = true;

        $admin = get_admin();
        \core\session\manager::set_user($admin);

        $messages = [];
        $excel = demo_env::find_demo_course();
        if (!$excel) {
            throw new \moodle_exception('error', 'error', '', null, 'Excel demo kursu yok; önce setup_eval_demo çalıştırın.');
        }

        $categoryid = (int)$excel->category ?: 1;
        $teacher = self::require_user(demo_env::TEACHER_USERNAME);
        $students = self::ensure_demo_students($messages);
        $staff = self::ensure_report_staff($excel, $messages);

        $word = self::ensure_course(
            demo_env::WORD_SHORTNAME,
            demo_env::WORD_FULLNAME,
            demo_env::WORD_SUMMARY,
            $categoryid,
            $messages
        );
        $exceladv = self::ensure_course(
            demo_env::EXCEL_ADV_SHORTNAME,
            demo_env::EXCEL_ADV_FULLNAME,
            demo_env::EXCEL_ADV_SUMMARY,
            $categoryid,
            $messages
        );

        foreach ([$excel, $word, $exceladv] as $course) {
            self::enrol_teacher($course, $teacher, $messages);
            foreach ($students as $student) {
                self::enrol_student($course, $student);
            }
        }
        $messages[] = 'Kayıtlar hazır (eğitmen + 3 demo öğrenci × 3 kurs).';

        $wordvideos = self::ensure_course_videos($word, 'word', [
            'Yazışma şablonları',
            'Resmi mektup düzeni',
            'Taslak: İmza bloğu (öğrenciye kapalı)',
        ], $messages);
        $advvideos = self::ensure_course_videos($exceladv, 'exceladv', [
            'Pivot tablo temelleri',
            'Özet rapor oluşturma',
            'Taslak: Power Query (öğrenciye kapalı)',
        ], $messages);

        $assign = self::ensure_assign_due($excel, $messages);
        $quiz = self::find_demo_quiz($excel);

        self::seed_progress($excel, $word, $exceladv, $wordvideos, $advvideos, $students, $messages);
        self::seed_assign_states($excel, $assign, $students, $staff, $messages);
        self::seed_quiz_attempts($excel, $quiz, $students, $staff, $messages);

        $messages[] = 'Mock veri kurulumu tamam.';
        return $messages;
    }

    private static function require_user(string $username): \stdClass {
        global $DB;
        $user = $DB->get_record('user', ['username' => $username, 'deleted' => 0], '*', IGNORE_MISSING);
        if (!$user) {
            throw new \moodle_exception('error', 'error', '', null, 'Kullanıcı yok: ' . $username);
        }
        return $user;
    }

    /**
     * @return array<string,\stdClass>
     */
    private static function ensure_demo_students(array &$messages): array {
        global $DB, $CFG;
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
            }
            $out[$spec['username']] = $user;
        }
        return $out;
    }

    /**
     * @return array<string,\stdClass>
     */
    private static function ensure_report_staff(\stdClass $excel, array &$messages): array {
        global $DB, $CFG;
        $out = [];
        foreach (demo_env::REPORT_STAFF as $spec) {
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
                $messages[] = 'Rapor kullanıcısı: ' . $spec['username'];
            }
            self::enrol_student($excel, $user);
            $out[$spec['username']] = $user;
        }
        return $out;
    }

    private static function ensure_course(
        string $shortname,
        string $fullname,
        string $summary,
        int $categoryid,
        array &$messages
    ): \stdClass {
        global $DB;

        $course = $DB->get_record('course', ['shortname' => $shortname], '*', IGNORE_MISSING);
        if ($course) {
            $course->fullname = $fullname;
            $course->summary = $summary;
            $course->summaryformat = FORMAT_HTML;
            $course->visible = 1;
            update_course($course);
            $messages[] = 'Kurs güncellendi: ' . $shortname;
            return $DB->get_record('course', ['id' => $course->id], '*', MUST_EXIST);
        }

        $data = (object) [
            'fullname' => $fullname,
            'shortname' => $shortname,
            'category' => $categoryid,
            'visible' => 1,
            'summary' => $summary,
            'summaryformat' => FORMAT_HTML,
            'format' => 'topics',
            'numsections' => 1,
            'startdate' => time() - (DAYSECS * 14),
            'enddate' => 0,
            'enablecompletion' => 0,
        ];
        $course = create_course($data);
        $messages[] = 'Kurs oluşturuldu: ' . $shortname . ' (id=' . $course->id . ')';
        return $course;
    }

    private static function enrol_teacher(\stdClass $course, \stdClass $teacher, array &$messages): void {
        global $DB;
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        self::enrol_with_role((int)$course->id, (int)$teacher->id, (int)$roleid);
    }

    private static function enrol_student(\stdClass $course, \stdClass $user): void {
        global $DB;
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
        self::enrol_with_role((int)$course->id, (int)$user->id, (int)$roleid);
    }

    private static function enrol_with_role(int $courseid, int $userid, int $roleid): void {
        global $DB;

        $instance = $DB->get_record('enrol', ['enrol' => 'manual', 'courseid' => $courseid], '*', IGNORE_MISSING);
        if (!$instance) {
            $plugin = enrol_get_plugin('manual');
            $instanceid = $plugin->add_instance(get_course($courseid));
            $instance = $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
        }

        $context = \context_course::instance($courseid);
        $already = $DB->record_exists('user_enrolments', [
            'enrolid' => $instance->id,
            'userid' => $userid,
        ]);
        if ($already) {
            if (!user_has_role_assignment($userid, $roleid, $context->id)) {
                role_assign($roleid, $userid, $context->id, 'enrol_manual', $instance->id);
            }
            return;
        }
        enrol_get_plugin('manual')->enrol_user($instance, $userid, $roleid);
    }

    /**
     * @param list<string> $names [published1, published2, draft]
     * @return array{published:list<\stdClass>, draft:\stdClass}
     */
    private static function ensure_course_videos(
        \stdClass $course,
        string $idprefix,
        array $names,
        array &$messages
    ): array {
        course_create_sections_if_missing($course, [1]);
        rebuild_course_cache($course->id, true);
        $modinfo = get_fast_modinfo($course);
        $section = $modinfo->get_section_info(1);
        course_update_section($course, $section, ['name' => demo_env::CONTENT_SECTION]);

        $published = [];
        $published[] = self::ensure_video_resource(
            $course,
            1,
            $names[0],
            'wam-mock-' . $idprefix . '-v1',
            true,
            $messages
        );
        $published[] = self::ensure_video_resource(
            $course,
            1,
            $names[1],
            'wam-mock-' . $idprefix . '-v2',
            true,
            $messages
        );
        $draft = self::ensure_video_resource(
            $course,
            1,
            $names[2],
            'wam-mock-' . $idprefix . '-draft',
            false,
            $messages
        );

        return ['published' => $published, 'draft' => $draft];
    }

    /**
     * Başlığı belli dersler kendi videosunu kullanır; diğerleri örnek dosyada kalır.
     */
    private static function sample_video_path(string $idnumber): string {
        global $CFG;

        $named = [
            'wam-mock-word-v1' => '/local/wamakademi/pix/videos/yazisma-sablonlari.mp4',
            'wam-mock-word-v2' => '/local/wamakademi/pix/videos/resmi-mektup.mp4',
        ];
        if (isset($named[$idnumber])) {
            $path = $CFG->dirroot . $named[$idnumber];
            if (is_readable($path)) {
                return $path;
            }
        }
        return $CFG->dirroot . '/local/wamakademi/tests_accept_sample.mp4';
    }

    /**
     * Ders dosyası kaynak videodan farklıysa değiştirir ve tarayıcı önbelleğini kırar.
     */
    private static function sync_resource_video(int $cmid, int $instanceid, string $filename, string $path): void {
        global $DB;

        if (!is_readable($path)) {
            return;
        }
        $fs = get_file_storage();
        $context = \context_module::instance($cmid);
        $existing = $fs->get_file($context->id, 'mod_resource', 'content', 0, '/', $filename);
        $size = filesize($path);
        if ($existing && (int)$existing->get_filesize() === (int)$size) {
            return;
        }
        if ($existing) {
            $existing->delete();
        }
        $fs->create_file_from_pathname([
            'contextid' => $context->id,
            'component' => 'mod_resource',
            'filearea' => 'content',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
        ], $path);
        $DB->execute('UPDATE {resource} SET revision = revision + 1 WHERE id = ?', [$instanceid]);
        $courseid = $DB->get_field('course_modules', 'course', ['id' => $cmid], MUST_EXIST);
        rebuild_course_cache((int)$courseid, true);
    }

    private static function ensure_video_resource(
        \stdClass $course,
        int $sectionnum,
        string $name,
        string $idnumber,
        bool $visible,
        array &$messages
    ): \stdClass {
        global $DB, $USER, $CFG;

        require_once($CFG->libdir . '/filelib.php');

        $cm = $DB->get_record_sql(
            "SELECT cm.*
               FROM {course_modules} cm
               JOIN {modules} m ON m.id = cm.module
              WHERE cm.course = ? AND m.name = 'resource' AND cm.idnumber = ?",
            [$course->id, $idnumber]
        );
        if ($cm) {
            $DB->set_field('resource', 'name', $name, ['id' => $cm->instance]);
            set_coursemodule_visible((int)$cm->id, $visible ? 1 : 0, 1, true);
            self::sync_resource_video((int)$cm->id, (int)$cm->instance, $idnumber . '.mp4', self::sample_video_path($idnumber));
            $messages[] = 'Video mevcut: ' . $idnumber;
            $cm = get_coursemodule_from_id('resource', $cm->id, 0, false, MUST_EXIST);
            return $cm;
        }

        $sample = self::sample_video_path($idnumber);
        if (!is_readable($sample)) {
            throw new \moodle_exception('error', 'error', '', null, 'Örnek video yok: ' . $sample);
        }

        $fs = get_file_storage();
        $usercontext = \context_user::instance($USER->id);
        $draftid = file_get_unused_draft_itemid();
        $fs->create_file_from_pathname([
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftid,
            'filepath' => '/',
            'filename' => $idnumber . '.mp4',
        ], $sample);

        $moduleinfo = new \stdClass();
        $moduleinfo->modulename = 'resource';
        $moduleinfo->module = $DB->get_field('modules', 'id', ['name' => 'resource'], MUST_EXIST);
        $moduleinfo->name = $name;
        $moduleinfo->intro = '<p>' . s($name) . '</p>';
        $moduleinfo->introformat = FORMAT_HTML;
        $moduleinfo->section = $sectionnum;
        $moduleinfo->visible = $visible ? 1 : 0;
        $moduleinfo->course = $course->id;
        $moduleinfo->cmidnumber = $idnumber;
        $moduleinfo->display = RESOURCELIB_DISPLAY_EMBED;
        $moduleinfo->showdescription = 0;
        $moduleinfo->printintro = 0;
        $moduleinfo->showsize = 1;
        $moduleinfo->showtype = 1;
        $moduleinfo->showdate = 0;
        $moduleinfo->filterfiles = 0;
        $moduleinfo->files = $draftid;
        $moduleinfo->completion = 0;
        $moduleinfo->availabilityconditionsjson = '';

        $created = add_moduleinfo($moduleinfo, $course);
        set_coursemodule_visible((int)$created->coursemodule, $visible ? 1 : 0, 1, true);
        $messages[] = 'Video eklendi: ' . $name . ' (cmid=' . $created->coursemodule . ', visible=' . ($visible ? '1' : '0') . ')';
        return get_coursemodule_from_id('resource', (int)$created->coursemodule, 0, false, MUST_EXIST);
    }

    /**
     * @return array{id:int,cmid:int}
     */
    private static function ensure_assign_due(\stdClass $excel, array &$messages): array {
        global $DB;

        $cm = $DB->get_record_sql(
            "SELECT cm.id, cm.instance
               FROM {course_modules} cm
               JOIN {modules} m ON m.id = cm.module
              WHERE cm.course = ? AND m.name = 'assign' AND cm.idnumber = ?",
            [$excel->id, demo_env::ASSIGN_IDNUMBER]
        );
        if (!$cm) {
            throw new \moodle_exception('error', 'error', '', null, 'Demo ödev yok');
        }
        $duedate = time() - (DAYSECS * 3);
        $DB->set_field('assign', 'duedate', $duedate, ['id' => $cm->instance]);
        $messages[] = 'Ödev son tarihi ayarlandı (3 gün önce).';
        return ['id' => (int)$cm->instance, 'cmid' => (int)$cm->id];
    }

    /**
     * @return array{id:int,cmid:int}
     */
    private static function find_demo_quiz(\stdClass $excel): array {
        global $DB;
        $cm = $DB->get_record_sql(
            "SELECT cm.id, cm.instance
               FROM {course_modules} cm
               JOIN {modules} m ON m.id = cm.module
              WHERE cm.course = ? AND m.name = 'quiz' AND cm.idnumber = ?",
            [$excel->id, demo_env::QUIZ_IDNUMBER]
        );
        if (!$cm) {
            throw new \moodle_exception('error', 'error', '', null, 'Demo sınav yok');
        }
        \mod_quiz\quiz_settings::create((int)$cm->instance)
            ->get_grade_calculator()
            ->recompute_quiz_sumgrades();
        return ['id' => (int)$cm->instance, 'cmid' => (int)$cm->id];
    }

    /**
     * @param array{published:list<\stdClass>, draft:\stdClass} $wordvideos
     * @param array{published:list<\stdClass>, draft:\stdClass} $advvideos
     * @param array<string,\stdClass> $students
     */
    private static function seed_progress(
        \stdClass $excel,
        \stdClass $word,
        \stdClass $exceladv,
        array $wordvideos,
        array $advvideos,
        array $students,
        array &$messages
    ): void {
        global $DB;

        $now = time();
        $monthstart = mktime(10, 0, 0, (int)date('n'), 5, (int)date('Y'));
        $duration = 120.0;

        // Excel temel: yayımlı video (cmid=8 WAM Yayimli Video) — PDF'i de sayar; yalnız video ilerler.
        $excelvideo = $DB->get_record_sql(
            "SELECT cm.id
               FROM {course_modules} cm
               JOIN {modules} m ON m.id = cm.module
               JOIN {resource} r ON r.id = cm.instance
              WHERE cm.course = ? AND m.name = 'resource' AND cm.visible = 1
                AND r.name = ?
           ORDER BY cm.id ASC",
            [$excel->id, 'WAM Yayimli Video']
        );
        if (!$excelvideo) {
            $excelvideo = $DB->get_record_sql(
                "SELECT cm.id
                   FROM {course_modules} cm
                   JOIN {modules} m ON m.id = cm.module
                  WHERE cm.course = ? AND m.name = 'resource' AND cm.visible = 1
               ORDER BY cm.id ASC",
                [$excel->id]
            );
        }
        if (!$excelvideo) {
            throw new \moodle_exception('error', 'error', '', null, 'Excel yayımlı video yok');
        }

        $s1 = $students['demo_ogrenci1'];
        $s2 = $students['demo_ogrenci2'];
        // s3: hiç ilerleme yok.

        // Öğrenci 1: Excel temelde %50 (devam), Word hiç yok.
        self::upsert_progress((int)$s1->id, (int)$excelvideo->id, [
            'currenttime' => 60,
            'duration' => $duration,
            'watchedseconds' => 60,
            'progress' => 50,
            'completed' => 0,
            'timecreated' => $monthstart,
            'timecompleted' => 0,
            'timemodified' => $monthstart + 3600,
        ]);

        // Öğrenci 2: Word iki ders tamam; Excel ileri yarım.
        $wordstart = $monthstart + DAYSECS;
        $wordend = $wordstart + DAYSECS * 2;
        foreach ($wordvideos['published'] as $i => $cm) {
            self::upsert_progress((int)$s2->id, (int)$cm->id, [
                'currenttime' => $duration,
                'duration' => $duration,
                'watchedseconds' => $duration,
                'progress' => 100,
                'completed' => 1,
                'timecreated' => $wordstart + ($i * 3600),
                'timecompleted' => $wordend,
                'timemodified' => $wordend,
            ]);
        }
        $advcm = $advvideos['published'][0];
        self::upsert_progress((int)$s2->id, (int)$advcm->id, [
            'currenttime' => 40,
            'duration' => $duration,
            'watchedseconds' => 40,
            'progress' => 33,
            'completed' => 0,
            'timecreated' => $monthstart + DAYSECS * 4,
            'timecompleted' => 0,
            'timemodified' => $monthstart + DAYSECS * 4 + 1800,
        ]);

        // Taslaklara ilerleme yazılmaz.
        $messages[] = 'İlerleme: ogrenci1 Excel devam; ogrenci2 Word tamam + Excel İleri devam; ogrenci3 boş.';
    }

    /**
     * @param array<string,mixed> $data
     */
    private static function upsert_progress(int $userid, int $cmid, array $data): void {
        global $DB;
        $existing = $DB->get_record('local_wamakademi_progress', [
            'userid' => $userid,
            'cmid' => $cmid,
        ]);
        $row = (object) array_merge([
            'userid' => $userid,
            'cmid' => $cmid,
            'watchedranges' => '[]',
        ], $data);
        if ($existing) {
            $row->id = $existing->id;
            $DB->update_record('local_wamakademi_progress', $row);
        } else {
            $DB->insert_record('local_wamakademi_progress', $row);
        }
    }

    /**
     * @param array{id:int,cmid:int} $assign
     * @param array<string,\stdClass> $students
     * @param array<string,\stdClass> $staff
     */
    private static function seed_assign_states(
        \stdClass $excel,
        array $assign,
        array $students,
        array $staff,
        array &$messages
    ): void {
        global $DB;

        $assignid = $assign['id'];
        $cmid = $assign['cmid'];
        $duedate = (int)$DB->get_field('assign', 'duedate', ['id' => $assignid]);

        // Temizle: bu kullanıcıların önceki mock teslimleri.
        $userids = array_map(static fn($u) => (int)$u->id, array_merge($students, $staff));
        foreach ($userids as $uid) {
            self::clear_assign_user($assignid, $cmid, $uid);
        }

        // demo_ogrenci1: teslim yok
        // demo_ogrenci2: taslak
        self::insert_submission($assignid, (int)$students['demo_ogrenci2']->id, 'draft', $duedate - DAYSECS);
        // demo_ogrenci3: zamanında teslim, not yok
        self::insert_submission($assignid, (int)$students['demo_ogrenci3']->id, 'submitted', $duedate - HOURSECS);
        // mock_ayse: geç teslim, not yok
        self::insert_submission($assignid, (int)$staff['mock_ayse']->id, 'submitted', $duedate + DAYSECS);
        // mock_kerem: not 0
        self::insert_submission($assignid, (int)$staff['mock_kerem']->id, 'submitted', $duedate - DAYSECS);
        self::grade_assign($assignid, $cmid, (int)$staff['mock_kerem']->id, 0.0);
        // mock_selin: not 90
        self::insert_submission($assignid, (int)$staff['mock_selin']->id, 'submitted', $duedate - DAYSECS * 2);
        self::grade_assign($assignid, $cmid, (int)$staff['mock_selin']->id, 90.0);

        $messages[] = 'Ödev durumları: yok / taslak / bekleyen / geç / 0 / 90.';
    }

    private static function clear_assign_user(int $assignid, int $cmid, int $userid): void {
        global $DB;

        $grades = $DB->get_records('assign_grades', ['assignment' => $assignid, 'userid' => $userid]);
        $ctx = \context_module::instance($cmid);
        $manager = get_grading_manager($ctx, 'mod_assign', 'submissions');
        $controller = $manager->get_active_controller();
        foreach ($grades as $grade) {
            if ($controller && $controller->is_form_available()) {
                $instances = $DB->get_records('grading_instances', [
                    'definitionid' => $controller->get_definition()->id,
                    'itemid' => $grade->id,
                ]);
                foreach ($instances as $inst) {
                    $DB->delete_records('gradingform_rubric_fillings', ['instanceid' => $inst->id]);
                    $DB->delete_records('grading_instances', ['id' => $inst->id]);
                }
            }
            $DB->delete_records('assign_grades', ['id' => $grade->id]);
        }
        $DB->delete_records('assign_submission', ['assignment' => $assignid, 'userid' => $userid]);

        $item = \grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => 'assign',
            'iteminstance' => $assignid,
            'courseid' => get_coursemodule_from_id('assign', $cmid)->course,
        ]);
        if ($item) {
            $gg = \grade_grade::fetch(['itemid' => $item->id, 'userid' => $userid]);
            if ($gg) {
                $gg->delete();
            }
        }
    }

    private static function insert_submission(int $assignid, int $userid, string $status, int $timemodified): void {
        global $DB;
        $DB->insert_record('assign_submission', (object) [
            'assignment' => $assignid,
            'userid' => $userid,
            'timecreated' => $timemodified - 60,
            'timemodified' => $timemodified,
            'status' => $status,
            'groupid' => 0,
            'attemptnumber' => 0,
            'latest' => 1,
        ]);
    }

    private static function grade_assign(int $assignid, int $cmid, int $userid, float $grade): void {
        global $DB;

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

        $ctx = \context_module::instance($cmid);
        $manager = get_grading_manager($ctx, 'mod_assign', 'submissions');
        /** @var \gradingform_rubric_controller|null $controller */
        $controller = $manager->get_active_controller();
        if (!$controller || !($controller instanceof \gradingform_rubric_controller) || !$controller->is_form_available()) {
            return;
        }
        $definition = $controller->get_definition();
        $instance = $controller->create_instance($userid, $agrade->id);
        $filling = ['criteria' => []];
        foreach ($definition->rubric_criteria as $critid => $crit) {
            $max = 0.0;
            foreach ($crit['levels'] as $level) {
                $max = max($max, (float)$level['score']);
            }
            $target = ($grade / 100.0) * $max;
            $chosen = null;
            $bestdiff = PHP_FLOAT_MAX;
            foreach ($crit['levels'] as $levelid => $level) {
                $diff = abs((float)$level['score'] - $target);
                if ($diff < $bestdiff) {
                    $bestdiff = $diff;
                    $chosen = $levelid;
                }
            }
            $filling['criteria'][$critid] = ['levelid' => $chosen, 'remark' => ''];
        }
        $instance->update($filling);
    }

    /**
     * @param array{id:int,cmid:int} $quiz
     * @param array<string,\stdClass> $students
     * @param array<string,\stdClass> $staff
     */
    private static function seed_quiz_attempts(
        \stdClass $excel,
        array $quiz,
        array $students,
        array $staff,
        array &$messages
    ): void {
        $quizid = $quiz['id'];

        // 9 soru: Formüller 1-3, Veri analizi 4-6, Grafik 7-9. fraction listesi slot sırasıyla.
        $specs = [
            // demo_ogrenci1: bitmemiş deneme (dağılıma girmez)
            ['user' => $students['demo_ogrenci1'], 'finished' => false, 'fractions' => null, 'grade' => null],
            // demo_ogrenci2: 0 puan
            ['user' => $students['demo_ogrenci2'], 'finished' => true,
                'fractions' => [0, 0, 0, 0, 0, 0, 0, 0, 0], 'grade' => 0.0],
            // demo_ogrenci3: ~44 (0-<40 değil, 40-<60) — 4/9
            ['user' => $students['demo_ogrenci3'], 'finished' => true,
                'fractions' => [1, 1, 0, 1, 0, 0, 1, 0, 0], 'grade' => 44.44],
            // mock_ayse: tam 60 — 5.4/9 yaklaşık; 6/9=66.67 → 60 için gradebook 60, konular: F 2/3, V 2/3, G 1/3 ≈
            ['user' => $staff['mock_ayse'], 'finished' => true,
                'fractions' => [1, 1, 0, 1, 1, 0, 1, 0, 0], 'grade' => 60.0],
            // mock_kerem: 80-100 — 8/9
            ['user' => $staff['mock_kerem'], 'finished' => true,
                'fractions' => [1, 1, 1, 1, 1, 1, 1, 1, 0], 'grade' => 88.89],
            // mock_selin: 100
            ['user' => $staff['mock_selin'], 'finished' => true,
                'fractions' => [1, 1, 1, 1, 1, 1, 1, 1, 1], 'grade' => 100.0],
        ];

        foreach ($specs as $spec) {
            self::replace_quiz_attempt(
                $quizid,
                (int)$spec['user']->id,
                $spec['finished'],
                $spec['fractions'],
                $spec['grade']
            );
        }
        $messages[] = 'Sınav denemeleri: bitmemiş, 0, ~44, 60, ~89, 100.';
    }

    /**
     * @param list<float>|null $fractions
     */
    private static function replace_quiz_attempt(
        int $quizid,
        int $userid,
        bool $finished,
        ?array $fractions,
        ?float $grade
    ): void {
        global $DB;

        foreach ($DB->get_records('quiz_attempts', ['quiz' => $quizid, 'userid' => $userid]) as $old) {
            $DB->delete_records('quiz_attempts', ['id' => $old->id]);
        }
        $cm = get_coursemodule_from_instance('quiz', $quizid, 0, false, MUST_EXIST);
        $item = \grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => 'quiz',
            'iteminstance' => $quizid,
            'courseid' => $cm->course,
        ]);
        if ($item) {
            $gg = \grade_grade::fetch(['itemid' => $item->id, 'userid' => $userid]);
            if ($gg) {
                $gg->delete();
            }
        }

        $quizobj = \mod_quiz\quiz_settings::create($quizid, $userid);
        $quba = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);
        $timenow = time() - HOURSECS;
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow, false, $userid);
        $attempt = quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        $attempt = quiz_attempt_save_started($quizobj, $quba, $attempt);

        if (!$finished || $fractions === null) {
            return;
        }

        $qas = $DB->get_records('question_attempts', ['questionusageid' => $attempt->uniqueid], 'slot ASC');
        $sum = 0.0;
        $i = 0;
        foreach ($qas as $qa) {
            $fraction = (float)($fractions[$i] ?? 0);
            $i++;
            $maxseq = (int)$DB->get_field_sql(
                "SELECT MAX(sequencenumber) FROM {question_attempt_steps} WHERE questionattemptid = ?",
                [$qa->id]
            );
            $DB->insert_record('question_attempt_steps', (object) [
                'questionattemptid' => $qa->id,
                'sequencenumber' => $maxseq + 1,
                'state' => $fraction >= 1.0 ? 'gradedright' : ($fraction > 0 ? 'gradedpartial' : 'gradedwrong'),
                'fraction' => $fraction,
                'timecreated' => $timenow + 30,
                'userid' => $userid,
            ]);
            $sum += $fraction * (float)$qa->maxmark;
        }

        $DB->update_record('quiz_attempts', (object) [
            'id' => $attempt->id,
            'state' => \mod_quiz\quiz_attempt::FINISHED,
            'timefinish' => $timenow + 60,
            'timemodified' => $timenow + 60,
            'sumgrades' => $sum,
        ]);

        if ($grade !== null) {
            grade_update('mod/quiz', $cm->course, 'mod', 'quiz', $quizid, 0, (object) [
                'userid' => $userid,
                'rawgrade' => $grade,
            ]);
        }
    }
}
