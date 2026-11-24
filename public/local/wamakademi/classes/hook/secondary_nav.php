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

        // Ödev / sınav: "Gelişmiş puanlama" → WAM not yönetimi merkezi.
        self::rewire_advanced_grading($secondary, $PAGE);
    }

    /**
     * Modül ikincil menüsündeki Gelişmiş puanlama düğümünü WAM hub'a yönlendirir.
     *
     * @param \core\navigation\views\secondary $secondary
     * @param \moodle_page $page
     */
    private static function rewire_advanced_grading($secondary, $page): void {
        global $USER;

        $cm = $page->cm ?? null;
        if (!$cm || empty($cm->id) || empty($cm->modname)) {
            return;
        }
        if (!in_array($cm->modname, ['assign', 'quiz'], true)) {
            return;
        }

        $context = \context_module::instance((int)$cm->id);
        $can = false;
        if ($cm->modname === 'assign') {
            $can = has_capability('mod/assign:grade', $context)
                || has_capability('mod/assign:managegrades', $context)
                || has_capability('moodle/grade:managegradingforms', $context);
        } else {
            $can = has_capability('mod/quiz:grade', $context)
                || has_capability('mod/quiz:viewreports', $context);
        }
        if (!$can && !\local_wamakademi\access::can_view_reports((int)$USER->id, (int)$page->course->id)) {
            return;
        }

        $huburl = new \moodle_url('/local/wamakademi/grading_hub.php', ['id' => (int)$cm->id]);
        $label = get_string('gradinghubheading', 'local_wamakademi');

        $adv = $secondary->get('advgrading');
        if ($adv) {
            $adv->action = $huburl;
            $adv->text = $label;
            // Ödevde Moodle anahtarı advgrading kalır (active tab uyumu).
            return;
        }

        // Sınavda veya basit puanlamalı ödevde Moodle advgrading eklemeyebilir.
        if ($secondary->get('wamgradinghub')) {
            return;
        }

        $before = null;
        $prefer = $cm->modname === 'quiz'
            ? ['quiz_report', 'mod_quiz_useroverrides', 'roleoverride']
            : ['mod_assign_useroverrides', 'roleoverride', 'modulepage'];
        foreach ($secondary->get_children_key_list() as $key) {
            if (in_array($key, $prefer, true)) {
                $before = $key;
                break;
            }
        }

        // Her iki modülde de advgrading anahtarı (core secondary TYPE_CUSTOM sırası).
        $node = \navigation_node::create(
            $label,
            $huburl,
            \navigation_node::TYPE_CUSTOM,
            null,
            'advgrading'
        );
        $secondary->add_node($node, $before);
    }
}
