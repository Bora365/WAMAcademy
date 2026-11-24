<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — yerel değerlendirme demosu ortam kilidi.

namespace local_wamakademi;

defined('MOODLE_INTERNAL') || die();

/**
 * Demo kurulum ve giriş yalnızca açıkça etkin ortamda çalışır.
 */
final class demo_env {

    public const COURSE_FULLNAME = 'WAM Test Egitimi Excel';
    public const COURSE_SHORTNAME = 'WAMTEST01';
    public const SECTION_NAME = 'Demo Değerlendirmeleri';
    public const QUIZ_IDNUMBER = 'wam-demo-quiz-excel';
    public const QUIZ_NAME = 'Excel Temelleri – Bilgi Kontrolü';
    public const ASSIGN_IDNUMBER = 'wam-demo-assign-sales';
    public const ASSIGN_NAME = 'Excel – Satış Analizi';
    public const TEACHER_USERNAME = 'wamtest_egitmen';
    public const DEMO_PASSWORD = 'WamLocal#2026x';

    /** @var list<array{username:string,firstname:string,lastname:string}> */
    public const STUDENTS = [
        ['username' => 'demo_ogrenci1', 'firstname' => 'Demo', 'lastname' => 'Öğrenci 1'],
        ['username' => 'demo_ogrenci2', 'firstname' => 'Demo', 'lastname' => 'Öğrenci 2'],
        ['username' => 'demo_ogrenci3', 'firstname' => 'Demo', 'lastname' => 'Öğrenci 3'],
    ];

    public const TOPIC_FORMULAS = 'Formüller';
    public const TOPIC_ANALYSIS = 'Veri analizi';
    public const TOPIC_CHARTS = 'Grafik yorumlama';

    public const ACADEMY_USERNAME = 'wamtest_akademi';

    public const WORD_SHORTNAME = 'WAMWORD01';
    public const WORD_FULLNAME = 'Word ile Kurumsal Yazışma';
    public const WORD_SUMMARY = 'Kurumsal yazışma şablonları, resmi mektup ve e-posta düzeni.';

    public const EXCEL_ADV_SHORTNAME = 'WAMEXCEL02';
    public const EXCEL_ADV_FULLNAME = 'Excel İleri Düzey — Pivot ve Raporlama';
    public const EXCEL_ADV_SUMMARY = 'Pivot tablolar, özet raporlama ve gelişmiş veri analizi.';

    public const CONTENT_SECTION = 'Ders içerikleri';

    /** Rapor mock kullanıcıları (giriş düğmesine eklenmez). */
    public const REPORT_STAFF = [
        ['username' => 'mock_ayse', 'firstname' => 'Ayşe', 'lastname' => 'Yılmaz'],
        ['username' => 'mock_kerem', 'firstname' => 'Kerem', 'lastname' => 'Demir'],
        ['username' => 'mock_selin', 'firstname' => 'Selin', 'lastname' => 'Kara'],
    ];


    /**
     * @return true|string true veya hata metni
     */
    public static function assert_safe(): true|string {
        global $CFG;

        if (empty($CFG->wam_demo_setup)) {
            return 'wam_demo_setup kapalı';
        }
        if (($CFG->dbname ?? '') !== 'wam_academy_local') {
            return 'dbname wam_academy_local değil';
        }
        $host = parse_url($CFG->wwwroot ?? '', PHP_URL_HOST);
        if (!in_array($host, ['localhost', '127.0.0.1'], true)) {
            return 'wwwroot localhost değil';
        }
        return true;
    }

    /**
     * @return string[]
     */
    public static function student_usernames(): array {
        return array_column(self::STUDENTS, 'username');
    }

    public static function find_demo_course(): ?\stdClass {
        global $DB;
        $course = $DB->get_record('course', ['shortname' => self::COURSE_SHORTNAME], '*', IGNORE_MISSING);
        if ($course) {
            return $course;
        }
        return $DB->get_record('course', ['fullname' => self::COURSE_FULLNAME], '*', IGNORE_MISSING) ?: null;
    }
}
