<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — yetki yardımcıları (Content Workflow R1).

namespace local_wamakademi;

defined('MOODLE_INTERNAL') || die();

/**
 * Video ve kurs oluşturma yetkileri için tek giriş noktası.
 *
 * Belirsiz class_exists fallback'leri yerine bu sınıf kullanılır.
 * Moodle çekirdek yetkileri (manageactivities, addinstance, activityvisibility)
 * her zaman ayrıca zorunludur.
 */
class access {

    /**
     * Kullanıcı bu kursa video taslağı oluşturabilir / önizleyebilir / yayımlayabilir mi?
     *
     * @param int $userid
     * @param int $courseid
     * @return bool
     */
    public static function can_manage_videos(int $userid, int $courseid): bool {
        if ($courseid <= 0 || $courseid === (int)SITEID || $userid <= 0) {
            return false;
        }
        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return false;
        }
        return has_capability('local/wamakademi:managevideos', $context, $userid)
            && has_capability('moodle/course:manageactivities', $context, $userid)
            && has_capability('mod/resource:addinstance', $context, $userid)
            && has_capability('moodle/course:activityvisibility', $context, $userid);
    }

    /**
     * Bu kullanıcının video yükleyebildiği eğitimler.
     * Site yöneticisi hepsini görür. Diğerleri yalnızca eğitmen olarak atandığı eğitimleri.
     *
     * @param int $userid
     * @return array<int, string> courseid => tam ad
     */
    public static function video_course_options(int $userid): array {
        global $DB;

        if ($userid <= 0) {
            return [];
        }
        if (is_siteadmin($userid) && !local_wamakademi_switched_roleid()) {
            $courses = $DB->get_records_select('course', 'id <> ?', [SITEID], 'fullname ASC', 'id, fullname');
        } else {
            $courses = $DB->get_records_sql(
                "SELECT DISTINCT c.id, c.fullname
                   FROM {role_assignments} ra
                   JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :level
                   JOIN {role} r ON r.id = ra.roleid
                   JOIN {course} c ON c.id = ctx.instanceid
                  WHERE ra.userid = :userid
                    AND c.id <> :siteid
                    AND r.archetype IN ('editingteacher', 'teacher', 'manager')
               ORDER BY c.fullname",
                [
                    'level' => CONTEXT_COURSE,
                    'userid' => $userid,
                    'siteid' => SITEID,
                ]
            );
        }
        $options = [];
        foreach ($courses as $course) {
            if (self::can_manage_videos($userid, (int)$course->id)) {
                $options[(int)$course->id] = format_string($course->fullname);
            }
        }
        return $options;
    }

    /**
     * Geriye uyumluluk: eski can_create_draft çağrıları.
     *
     * @param int $userid
     * @param int $courseid
     * @return bool
     */
    public static function can_create_draft(int $userid, int $courseid): bool {
        return self::can_manage_videos($userid, $courseid);
    }

    /**
     * Geriye uyumluluk: eski can_publish çağrıları.
     *
     * @param int $userid
     * @param int $courseid
     * @return bool
     */
    public static function can_publish(int $userid, int $courseid): bool {
        return self::can_manage_videos($userid, $courseid);
    }

    /**
     * Kullanıcı bu kursta WAM ilerleme / ödev raporunu görebilir mi?
     *
     * @param int $userid
     * @param int $courseid
     * @return bool
     */
    public static function can_view_reports(int $userid, int $courseid): bool {
        if ($courseid <= 0 || $courseid === (int)SITEID || $userid <= 0) {
            return false;
        }
        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return false;
        }
        if (is_siteadmin($userid) && !local_wamakademi_switched_roleid()) {
            return true;
        }
        return has_capability('local/wamakademi:viewreports', $context, $userid);
    }

    /**
     * Kullanıcının raporlayabileceği kurslar (id => fullname).
     *
     * @param int $userid
     * @return array<int, string>
     */
    public static function report_course_options(int $userid): array {
        global $DB;

        if ($userid <= 0) {
            return [];
        }

        if (is_siteadmin($userid) && !local_wamakademi_switched_roleid()) {
            $courses = $DB->get_records_select(
                'course',
                'id <> ?',
                [SITEID],
                'fullname ASC',
                'id, fullname'
            );
            $options = [];
            foreach ($courses as $course) {
                $options[(int)$course->id] = format_string($course->fullname);
            }
            return $options;
        }

        $courses = enrol_get_users_courses($userid, true, 'id, fullname');
        $options = [];
        foreach ($courses as $course) {
            $cid = (int)$course->id;
            if ($cid === (int)SITEID) {
                continue;
            }
            if (self::can_view_reports($userid, $cid)) {
                $options[$cid] = format_string($course->fullname);
            }
        }

        // Kategori atamalı akademi yöneticisi: enrol olmadan da alt kurslar.
        $categories = $DB->get_records_sql(
            "SELECT DISTINCT cc.id, cc.path
               FROM {role_assignments} ra
               JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :catlevel
               JOIN {role} r ON r.id = ra.roleid
               JOIN {course_categories} cc ON cc.id = ctx.instanceid
              WHERE ra.userid = :userid
                AND r.shortname = :shortname",
            [
                'catlevel' => CONTEXT_COURSECAT,
                'userid' => $userid,
                'shortname' => roles::ACADEMY_SHORTNAME,
            ]
        );
        foreach ($categories as $category) {
            $like = $DB->sql_like('cc.path', ':path', false);
            $catcourses = $DB->get_records_sql(
                "SELECT c.id, c.fullname
                   FROM {course} c
                   JOIN {course_categories} cc ON cc.id = c.category
                  WHERE c.id <> :siteid
                    AND (cc.id = :catid OR {$like})
               ORDER BY c.fullname ASC",
                [
                    'siteid' => SITEID,
                    'catid' => (int)$category->id,
                    'path' => (string)$category->path . '/%',
                ]
            );
            foreach ($catcourses as $course) {
                $cid = (int)$course->id;
                if (self::can_view_reports($userid, $cid)) {
                    $options[$cid] = format_string($course->fullname);
                }
            }
        }

        asort($options, SORT_NATURAL | SORT_FLAG_CASE);
        return $options;
    }

    /**
     * Kategori özet raporuna erişim (en az bir raporlanabilir kurs).
     *
     * @param int $userid
     * @return bool
     */
    public static function can_view_overview(int $userid): bool {
        return count(self::report_course_options($userid)) > 0;
    }

    /**
     * Kullanıcı bu kategoride yeni kurs oluşturabilir mi?
     *
     * @param int $userid
     * @param int $categoryid
     * @return bool
     */
    public static function can_create_course(int $userid, int $categoryid = 0): bool {
        global $USER, $DB;

        if ($userid <= 0) {
            return false;
        }
        $user = \core_user::get_user($userid, '*', IGNORE_MISSING);
        if (!$user) {
            return false;
        }
        // Site yöneticisi her kategoride kurs oluşturabilir.
        // Rol önizlemesinde (ör. öğrenci) bu kısa yol kullanılmaz.
        if (is_siteadmin($user) && !local_wamakademi_switched_roleid()) {
            return true;
        }
        if (local_wamakademi_switched_roleid() && (int)$user->id === (int)$USER->id) {
            if ($categoryid > 0) {
                $ctx = \context_coursecat::instance($categoryid, IGNORE_MISSING);
                return $ctx && local_wamakademi_switched_role_allows('moodle/course:create', $ctx);
            }
            if (local_wamakademi_switched_role_allows('moodle/course:create', \context_system::instance())) {
                return true;
            }
            $categories = $DB->get_records('course_categories', null, 'sortorder ASC', 'id');
            foreach ($categories as $category) {
                $ctx = \context_coursecat::instance((int)$category->id, IGNORE_MISSING);
                if ($ctx && local_wamakademi_switched_role_allows('moodle/course:create', $ctx)) {
                    return true;
                }
            }
            return false;
        }
        if ($categoryid > 0) {
            $context = \context_coursecat::instance($categoryid, IGNORE_MISSING);
            return $context && has_capability('moodle/course:create', $context, $userid);
        }
        $categories = \core_course_category::make_categories_list('moodle/course:create', 0, '', $user);
        return !empty($categories);
    }

    /**
     * Kurs oluşturma URL'si (doğru kategori bağlamı ile).
     *
     * @param int $categoryid 0 ise kullanıcının oluşturabileceği ilk kategori.
     * @return \moodle_url|null
     */
    public static function course_create_url(int $categoryid = 0): ?\moodle_url {
        global $USER, $DB;
        if (!self::can_create_course((int)$USER->id, $categoryid > 0 ? $categoryid : 0)) {
            return null;
        }
        if ($categoryid <= 0) {
            $categories = \core_course_category::make_categories_list('moodle/course:create', 0, '', $USER);
            if (!empty($categories)) {
                $categoryid = (int)array_key_first($categories);
            } else if (is_siteadmin($USER)) {
                // Varsayılan: ilk gerçek kategori (veya 1).
                $categoryid = (int)$DB->get_field_sql(
                    "SELECT id FROM {course_categories} WHERE id > 0 ORDER BY sortorder ASC, id ASC",
                    null,
                    IGNORE_MISSING
                );
                if ($categoryid <= 0) {
                    $categoryid = 1;
                }
            } else {
                return null;
            }
        }
        return new \moodle_url('/course/edit.php', ['category' => $categoryid]);
    }
}
