<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — etkinlik görünürlük / kısıtlama durumu (Content Workflow R1).

namespace local_wamakademi;

defined('MOODLE_INTERNAL') || die();

/**
 * Taslak / yayında / kısıtlı durumunu Moodle görünürlüğünden üretir.
 * Yalnızca görsel etiket değildir; course_modules.visible ve section/course/availability okunur.
 */
class video_status {

    public const DRAFT = 'draft';
    public const PUBLISHED = 'published';
    public const RESTRICTED = 'restricted';

    /**
     * @param \cm_info|\stdClass $cm Kurs modülü (modinfo veya get_coursemodule_from_id).
     * @param \stdClass $course Kurs kaydı.
     * @return array{code:string,label:string,detail:string,visible:int}
     */
    public static function for_cm($cm, \stdClass $course): array {
        $visible = (int)($cm->visible ?? 0);
        if ($visible === 0) {
            return [
                'code' => self::DRAFT,
                'label' => get_string('previewstatusdraft', 'local_wamakademi'),
                'detail' => '',
                'visible' => 0,
            ];
        }

        $reasons = [];
        if (empty($course->visible)) {
            $reasons[] = get_string('restrictioncourse', 'local_wamakademi');
        }

        $sectionnum = isset($cm->sectionnum) ? (int)$cm->sectionnum : null;
        if ($sectionnum === null && !empty($cm->section)) {
            // course_modules.section aslında section.id'dir; num için modinfo gerekir.
            try {
                $modinfo = get_fast_modinfo($course);
                if (isset($modinfo->cms[$cm->id])) {
                    $sectionnum = (int)$modinfo->cms[$cm->id]->sectionnum;
                }
            } catch (\Throwable $e) {
                $sectionnum = null;
            }
        }
        if ($sectionnum !== null) {
            try {
                $modinfo = get_fast_modinfo($course);
                $sec = $modinfo->get_section_info($sectionnum);
                if ($sec && !(int)$sec->visible) {
                    $reasons[] = get_string('restrictionsection', 'local_wamakademi');
                }
            } catch (\Throwable $e) {
                // Bölüm bilgisi yoksa atla.
            }
        }

        // Availability kısıtı (modinfo cm_info üzerinde).
        try {
            $modinfo = get_fast_modinfo($course);
            if (isset($modinfo->cms[$cm->id])) {
                $cminfo = $modinfo->cms[$cm->id];
                if (!empty($cminfo->availableinfo)) {
                    $reasons[] = get_string(
                        'restrictionavailability',
                        'local_wamakademi',
                        trim(strip_tags((string)$cminfo->availableinfo))
                    );
                } else if (!$cminfo->uservisible && $cminfo->available) {
                    // Kullanıcıya özel görünmezlik (grup vb.).
                    $reasons[] = get_string('restrictionavailability', 'local_wamakademi', '');
                }
            }
        } catch (\Throwable $e) {
            // Yok say.
        }

        if (!empty($reasons)) {
            return [
                'code' => self::RESTRICTED,
                'label' => get_string('previewstatusrestricted', 'local_wamakademi'),
                'detail' => implode(' · ', array_filter($reasons)),
                'visible' => 1,
            ];
        }

        return [
            'code' => self::PUBLISHED,
            'label' => get_string('previewstatuspublished', 'local_wamakademi'),
            'detail' => '',
            'visible' => 1,
        ];
    }
}
