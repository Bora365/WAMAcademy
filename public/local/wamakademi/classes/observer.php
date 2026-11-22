<?php
namespace local_wamakademi;

defined('MOODLE_INTERNAL') || die();

/**
 * Eğitim numarası: oluşturunca ver, silince numarayı emekliye ayır.
 */
class observer {
    /**
     * @param \core\event\course_created $event
     */
    public static function course_created(\core\event\course_created $event): void {
        $courseid = (int)$event->objectid;
        if ($courseid <= 0 || $courseid === (int)SITEID) {
            return;
        }
        coursenumber::issue_next($courseid);
    }

    /**
     * @param \core\event\course_deleted $event
     */
    public static function course_deleted(\core\event\course_deleted $event): void {
        $courseid = (int)$event->objectid;
        $code = '';
        $snapshot = $event->get_record_snapshot('course', $courseid);
        if ($snapshot && isset($snapshot->idnumber)) {
            $code = (string)$snapshot->idnumber;
        }
        coursenumber::retire($courseid, $code);
    }
}
