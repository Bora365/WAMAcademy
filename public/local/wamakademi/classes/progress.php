<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — eğitim / video ilerleme hesabı (tek kural).

namespace local_wamakademi;

defined('MOODLE_INTERNAL') || die();

/**
 * Video ve kurs ilerleme yüzdelerini tek yerde hesaplar.
 *
 * Kurallar:
 * - Video progress: local_wamakademi_progress.progress (watchedseconds / duration).
 * - Video completed bayrağı: progress >= 95 (save_progress yazar).
 * - Satırı olmayan yayımlı video: progress 0, completed değil.
 * - Kurs yüzdesi: tüm yayımlı derslerin progress ortalaması (eksik satır = 0).
 * - Kurs tamamlandı: her yayımlı derste completed = 1.
 * - Başladı: herhangi bir derste watchedseconds, progress, currenttime veya completed.
 */
class progress {

    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';

    /**
     * Kullanıcının bir kurstaki yayımlı ders ilerlemelerini özetler.
     *
     * @param int $userid
     * @param \cm_info[] $publishedresources
     * @param array<int, \stdClass>|null $progressbycmid cmid => progress row (önceden yüklü)
     * @return array{
     *   lessoncount:int,
     *   completedlessons:int,
     *   courseprogress:int,
     *   iscompleted:bool,
     *   hasstarted:bool,
     *   latestactivity:int,
     *   timecompleted:int,
     *   status:string,
     *   lessons:array<int, array>
     * }
     */
    public static function for_published_lessons(
        int $userid,
        array $publishedresources,
        ?array $progressbycmid = null
    ): array {
        global $DB;

        $lessoncount = count($publishedresources);
        $totalprogress = 0;
        $completedlessons = 0;
        $hasstarted = false;
        $latestactivity = 0;
        $completiontimes = [];
        $lessons = [];

        if ($progressbycmid === null && $lessoncount > 0 && $userid > 0) {
            $cmids = array_map(static fn($r) => (int)$r->id, $publishedresources);
            $progressbycmid = [];
            if (!empty($cmids)) {
                list($insql, $params) = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED, 'cm');
                $params['userid'] = $userid;
                $rows = $DB->get_records_select(
                    'local_wamakademi_progress',
                    "userid = :userid AND cmid {$insql}",
                    $params
                );
                foreach ($rows as $row) {
                    $progressbycmid[(int)$row->cmid] = $row;
                }
            }
        }
        if ($progressbycmid === null) {
            $progressbycmid = [];
        }

        foreach ($publishedresources as $resource) {
            $cmid = (int)$resource->id;
            $record = $progressbycmid[$cmid] ?? null;
            $lessonprogress = $record ? (int)$record->progress : 0;
            $lessonprogress = max(0, min(100, $lessonprogress));
            $islessoncompleted = $record && !empty($record->completed);
            $watched = $record ? (float)$record->watchedseconds : 0.0;
            $current = $record ? (float)$record->currenttime : 0.0;
            $duration = $record ? (float)$record->duration : 0.0;
            $timemodified = $record ? (int)$record->timemodified : 0;
            $timecompleted = $record ? (int)$record->timecompleted : 0;

            if ($watched > 0 || $lessonprogress > 0 || $current > 0 || $islessoncompleted) {
                $hasstarted = true;
            }
            if ($islessoncompleted) {
                $completedlessons++;
                $completiontimes[] = $timecompleted > 0 ? $timecompleted : $timemodified;
            }
            if ($timemodified > $latestactivity) {
                $latestactivity = $timemodified;
            }

            $totalprogress += $lessonprogress;
            if ($islessoncompleted) {
                $lessonstatus = self::STATUS_COMPLETED;
            } else if ($watched > 0 || $lessonprogress > 0 || $current > 0) {
                $lessonstatus = self::STATUS_ACTIVE;
            } else {
                $lessonstatus = self::STATUS_ASSIGNED;
            }

            $lessons[$cmid] = [
                'cmid' => $cmid,
                'name' => format_string($resource->name),
                'progress' => $lessonprogress,
                'completed' => $islessoncompleted,
                'status' => $lessonstatus,
                'statuslabel' => self::status_label($lessonstatus),
                'watchedseconds' => $watched,
                'duration' => $duration,
                'currenttime' => $current,
                'timecompleted' => $timecompleted,
                'timemodified' => $timemodified,
                'hasrecord' => $record !== null,
            ];
        }

        $courseprogress = $lessoncount > 0
            ? (int)round($totalprogress / $lessoncount)
            : 0;
        $courseprogress = max(0, min(100, $courseprogress));
        $iscompleted = ($completedlessons === $lessoncount && $lessoncount > 0);

        if ($iscompleted) {
            $status = self::STATUS_COMPLETED;
        } else if ($hasstarted) {
            $status = self::STATUS_ACTIVE;
        } else {
            $status = self::STATUS_ASSIGNED;
        }

        $coursetimecompleted = 0;
        if ($iscompleted && !empty($completiontimes)) {
            $coursetimecompleted = max($completiontimes);
        }

        return [
            'lessoncount' => $lessoncount,
            'completedlessons' => $completedlessons,
            'courseprogress' => $courseprogress,
            'iscompleted' => $iscompleted,
            'hasstarted' => $hasstarted,
            'latestactivity' => $latestactivity,
            'timecompleted' => $coursetimecompleted,
            'status' => $status,
            'lessons' => $lessons,
        ];
    }

    /**
     * Durum etiketi (yerelleştirilmiş).
     *
     * @param string $status
     * @return string
     */
    public static function status_label(string $status): string {
        switch ($status) {
            case self::STATUS_COMPLETED:
                return get_string('reportstatuscompleted', 'local_wamakademi');
            case self::STATUS_ACTIVE:
                return get_string('reportstatusactive', 'local_wamakademi');
            default:
                return get_string('reportstatusassigned', 'local_wamakademi');
        }
    }
}
