<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_local_wamakademi_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026082603) {

        $table = new xmldb_table('local_wamakademi_progress');

        $table->add_field(
            'id',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            XMLDB_NOTNULL,
            XMLDB_SEQUENCE,
            null
        );

        $table->add_field(
            'userid',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            XMLDB_NOTNULL,
            null,
            null
        );

        $table->add_field(
            'cmid',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            XMLDB_NOTNULL,
            null,
            null
        );

        $table->add_field(
            'currenttime',
            XMLDB_TYPE_NUMBER,
            '10,2',
            null,
            XMLDB_NOTNULL,
            null,
            '0'
        );

        $table->add_field(
            'duration',
            XMLDB_TYPE_NUMBER,
            '10,2',
            null,
            XMLDB_NOTNULL,
            null,
            '0'
        );

        $table->add_field(
            'progress',
            XMLDB_TYPE_INTEGER,
            '3',
            null,
            XMLDB_NOTNULL,
            null,
            '0'
        );

        $table->add_field(
            'completed',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '0'
        );

        $table->add_field(
            'timemodified',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            XMLDB_NOTNULL,
            null,
            '0'
        );

        $table->add_key(
            'primary',
            XMLDB_KEY_PRIMARY,
            ['id']
        );

        $table->add_index(
            'userid_cmid_uix',
            XMLDB_INDEX_UNIQUE,
            ['userid', 'cmid']
        );

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(
            true,
            2026082603,
            'local',
            'wamakademi'
        );
    }

if ($oldversion < 2026082604) {

    $table = new xmldb_table('local_wamakademi_progress');

    $field = new xmldb_field(
        'watchedseconds',
        XMLDB_TYPE_NUMBER,
        '10,2',
        null,
        XMLDB_NOTNULL,
        null,
        '0',
        'duration'
    );

    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    upgrade_plugin_savepoint(
        true,
        2026082604,
        'local',
        'wamakademi'
    );
}

if ($oldversion < 2026082605) {

    $table = new xmldb_table('local_wamakademi_progress');

    $field = new xmldb_field(
        'watchedranges',
        XMLDB_TYPE_TEXT,
        null,
        null,
        null,
        null,
        null,
        'watchedseconds'
    );

    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    upgrade_plugin_savepoint(
        true,
        2026082605,
        'local',
        'wamakademi'
    );
}

if ($oldversion < 2026082606) {

    $table = new xmldb_table('local_wamakademi_progress');

    $timecreated = new xmldb_field(
        'timecreated',
        XMLDB_TYPE_INTEGER,
        '10',
        null,
        XMLDB_NOTNULL,
        null,
        '0',
        'completed'
    );

    if (!$dbman->field_exists($table, $timecreated)) {
        $dbman->add_field($table, $timecreated);
    }

    $timecompleted = new xmldb_field(
        'timecompleted',
        XMLDB_TYPE_INTEGER,
        '10',
        null,
        XMLDB_NOTNULL,
        null,
        '0',
        'timecreated'
    );

    if (!$dbman->field_exists($table, $timecompleted)) {
        $dbman->add_field($table, $timecompleted);
    }

    /*
     * Eski kayıtlar için yaklaşık başlangıç zamanı.
     * Gerçek ilk başlangıç geçmişte tutulmadığı için
     * mevcut timemodified değerini kullanıyoruz.
     */
    $DB->execute("
        UPDATE {local_wamakademi_progress}
           SET timecreated = timemodified
         WHERE timecreated = 0
           AND watchedseconds > 0
    ");

    /*
     * Daha önce tamamlanmış videolar için yaklaşık
     * tamamlama zamanı.
     */
    $DB->execute("
        UPDATE {local_wamakademi_progress}
           SET timecompleted = timemodified
         WHERE timecompleted = 0
           AND completed = 1
    ");

    upgrade_plugin_savepoint(
        true,
        2026082606,
        'local',
        'wamakademi'
    );
}

    // Content Workflow R1: eksik ilerleme sütunlarını garanti et + managevideos capability.
    if ($oldversion < 2026082607) {

        $table = new xmldb_table('local_wamakademi_progress');

        $watchedseconds = new xmldb_field(
            'watchedseconds',
            XMLDB_TYPE_NUMBER,
            '10,2',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'duration'
        );
        if (!$dbman->field_exists($table, $watchedseconds)) {
            $dbman->add_field($table, $watchedseconds);
        }

        $watchedranges = new xmldb_field(
            'watchedranges',
            XMLDB_TYPE_TEXT,
            null,
            null,
            null,
            null,
            null,
            'watchedseconds'
        );
        if (!$dbman->field_exists($table, $watchedranges)) {
            $dbman->add_field($table, $watchedranges);
        }

        // Yeni capability tanımlarını yükle (db/access.php).
        update_capabilities('local_wamakademi');

        upgrade_plugin_savepoint(
            true,
            2026082607,
            'local',
            'wamakademi'
        );
    }

    if ($oldversion < 2026082608) {
        $table = new xmldb_table('local_wamakademi_coursenumber');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('code', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timedeleted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('code_uix', XMLDB_INDEX_UNIQUE, ['code']);
            $table->add_index('courseid_ix', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
            $dbman->create_table($table);
        }

        \local_wamakademi\coursenumber::backfill();

        upgrade_plugin_savepoint(true, 2026082608, 'local', 'wamakademi');
    }

    if ($oldversion < 2026082609) {
        update_capabilities('local_wamakademi');
        $teacher = $DB->get_record('role', ['shortname' => 'teacher']);
        if ($teacher) {
            $systemcontext = context_system::instance();
            $caps = [
                'local/wamakademi:managevideos',
                'moodle/course:manageactivities',
                'mod/resource:addinstance',
                'moodle/course:activityvisibility',
            ];
            foreach ($caps as $cap) {
                assign_capability($cap, CAP_ALLOW, $teacher->id, $systemcontext->id, true);
            }
        }
        upgrade_plugin_savepoint(true, 2026082609, 'local', 'wamakademi');
    }

    if ($oldversion < 2026082610) {
        // Yeni eğitimlerde boş "Yeni Bölüm" kabukları oluşmasın.
        set_config('numsections', 0, 'moodlecourse');

        // Yükseltme sırasında get_fast_modinfo kullanılamaz; boş bölümleri doğrudan sil.
        // Genel (section 0) ve içinde etkinlik olan bölümler kalır.
        $courses = $DB->get_records_select('course', 'id <> ?', [SITEID], '', 'id');
        foreach ($courses as $course) {
            $courseid = (int)$course->id;
            $DB->delete_records_select(
                'course_sections',
                'course = ? AND section > 0 AND (sequence IS NULL OR sequence = ?)',
                [$courseid, '']
            );
            // Kalan bölüm numaralarını 0,1,2… sırala.
            $keep = $DB->get_records('course_sections', ['course' => $courseid], 'section ASC', 'id, section');
            $n = 0;
            foreach ($keep as $section) {
                if ((int)$section->section !== $n) {
                    $DB->set_field('course_sections', 'section', $n, ['id' => (int)$section->id]);
                }
                $n++;
            }
        }
        // Modinfo bir sonraki sayfa yüklemesinde yeniden üretilir.

        upgrade_plugin_savepoint(true, 2026082610, 'local', 'wamakademi');
    }

    if ($oldversion < 2026082611) {
        // Akademi yöneticisi rolü ve eğitmen kayıt sınırı. Çekirdek dosya değişmez.
        \local_wamakademi\roles::sync();
        upgrade_plugin_savepoint(true, 2026082611, 'local', 'wamakademi');
    }

    if ($oldversion < 2026082612) {
        // Kurs Videolar sekmesi (secondary_extend) ve yönetim sayfası.
        upgrade_plugin_savepoint(true, 2026082612, 'local', 'wamakademi');
    }

    if ($oldversion < 2026082613) {
        // İlerleme raporu yetkisi; akademi yöneticisine viewreports.
        update_capabilities('local_wamakademi');
        \local_wamakademi\roles::sync();
        upgrade_plugin_savepoint(true, 2026082613, 'local', 'wamakademi');
    }

    return true;
}
