<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — ana sayfa / Eğitimlerim kart gezinmesi.

namespace local_wamakademi;

defined('MOODLE_INTERNAL') || die();

/**
 * Kart başlığı, kapak ve eylem URL'lerini tek kuralda üretir.
 */
class course_cards {

    /**
     * Kurs sayfası URL'si.
     *
     * @param int $courseid
     * @return \moodle_url
     */
    public static function course_url(int $courseid): \moodle_url {
        return new \moodle_url('/course/view.php', ['id' => $courseid]);
    }

    /**
     * Kart eylemleri: yönetici → eğitimi yönet (kurs); öğrenci → yayımlı derse devam veya eğitimi aç.
     *
     * @param int $userid
     * @param \stdClass $course
     * @param \cm_info[] $publishedresources
     * @param int|null $continuecmid
     * @return array{
     *   courseurl:string,
     *   actionurl:string,
     *   actionlabel:string,
     *   canmanage:bool,
     *   haspublishedlesson:bool,
     *   continuecmid:?int
     * }
     */
    public static function navigation(
        int $userid,
        \stdClass $course,
        array $publishedresources,
        ?int $continuecmid = null
    ): array {
        $courseurl = self::course_url((int)$course->id)->out(false);
        $canmanage = access::can_manage_videos($userid, (int)$course->id);
        $haspublished = !empty($publishedresources);

        if ($canmanage) {
            return [
                'courseurl' => $courseurl,
                'actionurl' => $courseurl,
                'actionlabel' => get_string('cardmanagecourse', 'local_wamakademi'),
                'canmanage' => true,
                'haspublishedlesson' => $haspublished,
                'continuecmid' => null,
            ];
        }

        if ($continuecmid === null && $haspublished) {
            $first = reset($publishedresources);
            $continuecmid = (int)$first->id;
        }

        if ($continuecmid) {
            return [
                'courseurl' => $courseurl,
                'actionurl' => (new \moodle_url('/mod/resource/view.php', [
                    'id' => $continuecmid,
                ]))->out(false),
                'actionlabel' => get_string('cardcontinue', 'local_wamakademi'),
                'canmanage' => false,
                'haspublishedlesson' => true,
                'continuecmid' => $continuecmid,
            ];
        }

        return [
            'courseurl' => $courseurl,
            'actionurl' => $courseurl,
            'actionlabel' => get_string('cardopencourse', 'local_wamakademi'),
            'canmanage' => false,
            'haspublishedlesson' => false,
            'continuecmid' => null,
        ];
    }

    /**
     * Yayımlı dersler arasında devam edilecek cmid.
     *
     * @param int $userid
     * @param \cm_info[] $publishedresources
     * @return array{cmid:?int, currenttime:float, lessonname:string, lessonindex:int}
     */
    public static function continue_target(int $userid, array $publishedresources): array {
        global $DB;

        $result = [
            'cmid' => null,
            'currenttime' => 0.0,
            'lessonname' => '',
            'lessonindex' => 0,
        ];

        if (empty($publishedresources)) {
            return $result;
        }

        $index = 0;
        foreach ($publishedresources as $resource) {
            $index++;
            $record = $DB->get_record(
                'local_wamakademi_progress',
                [
                    'userid' => $userid,
                    'cmid' => (int)$resource->id,
                ]
            );
            if (!$record || empty($record->completed)) {
                $result['cmid'] = (int)$resource->id;
                $result['lessonname'] = format_string($resource->name);
                $result['lessonindex'] = $index;
                if ($record) {
                    $result['currenttime'] = (float)$record->currenttime;
                }
                return $result;
            }
        }

        $last = end($publishedresources);
        $result['cmid'] = (int)$last->id;
        $result['lessonname'] = format_string($last->name);
        $result['lessonindex'] = count($publishedresources);
        return $result;
    }
}
