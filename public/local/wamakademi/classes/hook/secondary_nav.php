<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — kurs ikincil menüsüne Videolar sekmesi.

namespace local_wamakademi\hook;

defined('MOODLE_INTERNAL') || die();

/**
 * Eğitim sekmesinden hemen sonra Videolar düğümünü yerleştirir.
 */
class secondary_nav {

    /**
     * @param \core\hook\navigation\secondary_extend $hook
     */
    public static function extend(\core\hook\navigation\secondary_extend $hook): void {
        global $PAGE, $USER;

        // empty($PAGE->course) Moodle sihirli özelliğinde güvenilir değil; id kontrol et.
        $courseid = (int)($PAGE->course->id ?? 0);
        if ($courseid <= 1) {
            return;
        }
        $secondary = $hook->get_secondaryview();

        // coursehome'un hemen ardına koy: sonraki kardeş beforekey olur.
        $beforekey = null;
        $afterhome = false;
        foreach ($secondary->get_children_key_list() as $key) {
            if ($afterhome) {
                $beforekey = $key;
                break;
            }
            if ($key === 'coursehome') {
                $afterhome = true;
            }
        }

        if (\local_wamakademi\access::can_manage_videos((int)$USER->id, $courseid)
                && !$secondary->get('wamvideos')) {
            $videosnode = \navigation_node::create(
                get_string('videostab', 'local_wamakademi'),
                new \moodle_url('/local/wamakademi/videos.php', ['courseid' => $courseid]),
                \navigation_node::TYPE_CUSTOM,
                null,
                'wamvideos'
            );
            $secondary->add_node($videosnode, $beforekey);
        }

        if (\local_wamakademi\access::can_view_reports((int)$USER->id, $courseid)
                && !$secondary->get('wamreports')) {
            // Videolar varsa hemen ardından; yoksa coursehome sonrası aynı noktaya.
            $reportbefore = $beforekey;
            if ($secondary->get('wamvideos')) {
                $reportbefore = null;
                $foundvideos = false;
                foreach ($secondary->get_children_key_list() as $key) {
                    if ($foundvideos) {
                        $reportbefore = $key;
                        break;
                    }
                    if ($key === 'wamvideos') {
                        $foundvideos = true;
                    }
                }
            }
            $reportsnode = \navigation_node::create(
                get_string('reporttab', 'local_wamakademi'),
                new \moodle_url('/local/wamakademi/report_course.php', ['courseid' => $courseid]),
                \navigation_node::TYPE_CUSTOM,
                null,
                'wamreports'
            );
            $secondary->add_node($reportsnode, $reportbefore);
        }
    }
}
