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
     * Kullanıcı bu kategoride yeni kurs oluşturabilir mi?
     *
     * @param int $userid
     * @param int $categoryid
     * @return bool
     */
    public static function can_create_course(int $userid, int $categoryid = 0): bool {
        if ($userid <= 0) {
            return false;
        }
        $user = \core_user::get_user($userid, '*', IGNORE_MISSING);
        if (!$user) {
            return false;
        }
        // Site yöneticisi her kategoride kurs oluşturabilir.
        if (is_siteadmin($user)) {
            return true;
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
