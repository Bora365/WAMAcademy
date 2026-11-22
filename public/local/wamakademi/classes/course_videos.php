<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — kurs video listesi ve bölüm içi sıra.

namespace local_wamakademi;

defined('MOODLE_INTERNAL') || die();

/**
 * Video resource tespiti (MP4 / MOV), yönetim listesi ve Moodle move_before ile sıra.
 */
class course_videos {

    /**
     * Yükleme ve listede kabul edilen uzantılar.
     *
     * @return string[]
     */
    public static function allowed_video_extensions(): array {
        return ['mp4', 'mov'];
    }

    /**
     * Yükleme ve listede kabul edilen MIME türleri.
     *
     * @return string[]
     */
    public static function allowed_video_mimetypes(): array {
        return ['video/mp4', 'video/quicktime'];
    }

    /**
     * Filepicker accepted_types listesi.
     *
     * @return string[]
     */
    public static function filepicker_accepted_types(): array {
        return ['.mp4', '.mov', 'video/mp4', 'video/quicktime'];
    }

    /**
     * Saklanmış dosya kabul edilen video mu?
     *
     * @param \stored_file $file
     * @return bool
     */
    public static function is_allowed_video_file(\stored_file $file): bool {
        $ext = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
        $mime = (string)$file->get_mimetype();
        if (!in_array($ext, self::allowed_video_extensions(), true)) {
            return false;
        }
        if (in_array($mime, self::allowed_video_mimetypes(), true)) {
            return true;
        }
        // Bazı ortamlarda .mov video/mp4 olarak etiketlenir.
        return $ext === 'mov' && str_starts_with($mime, 'video/');
    }

    /**
     * Ana dosyası kabul edilen video (MP4/MOV) olan resource mi?
     *
     * @param \cm_info|\stdClass $cm
     * @return bool
     */
    public static function is_mp4_resource($cm): bool {
        if (($cm->modname ?? '') !== 'resource' || !empty($cm->deletioninprogress)) {
            return false;
        }
        $cmid = (int)($cm->id ?? 0);
        if ($cmid <= 0) {
            return false;
        }
        $modcontext = \context_module::instance($cmid, IGNORE_MISSING);
        if (!$modcontext) {
            return false;
        }
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $modcontext->id,
            'mod_resource',
            'content',
            0,
            'sortorder DESC, id ASC',
            false
        );
        if (empty($files)) {
            return false;
        }
        $mainfile = reset($files);
        return self::is_allowed_video_file($mainfile);
    }

    /**
     * Bölüm sırasıyla kursun tüm videoları (taslak + yayında).
     *
     * @param \stdClass $course
     * @return array<int, array>
     */
    public static function list_for_management(\stdClass $course): array {
        $modinfo = get_fast_modinfo($course);
        $rows = [];
        $bysection = [];

        foreach ($modinfo->get_cms() as $cm) {
            if (!self::is_mp4_resource($cm)) {
                continue;
            }
            $sectionnum = (int)$cm->sectionnum;
            if (!isset($bysection[$sectionnum])) {
                $bysection[$sectionnum] = [];
            }
            $bysection[$sectionnum][] = $cm;
        }

        foreach ($bysection as $sectionnum => $cms) {
            $count = count($cms);
            foreach ($cms as $index => $cm) {
                $status = video_status::for_cm($cm, $course);
                $rows[] = [
                    'cmid' => (int)$cm->id,
                    'name' => format_string($cm->name),
                    'sectionnum' => $sectionnum,
                    'sectionname' => format_string(get_section_name($course, $sectionnum)),
                    'visible' => (int)$cm->visible,
                    'statuscode' => $status['code'],
                    'statuslabel' => $status['label'],
                    'isdraft' => ((int)$cm->visible === 0),
                    'ispublished' => ((int)$cm->visible === 1),
                    'position' => $index + 1,
                    'canmoveup' => ($index > 0),
                    'canmovedown' => ($index < $count - 1),
                    'previewurl' => (new \moodle_url('/local/wamakademi/upload.php', [
                        'courseid' => (int)$course->id,
                        'sectionnum' => $sectionnum,
                        'previewcmid' => (int)$cm->id,
                    ]))->out(false),
                ];
            }
        }

        return $rows;
    }

    /**
     * Öğrenciye görünür yayımlı resource'lar (devam URL'si için).
     * PDF dahil tüm görünür resource'lar; taslak seçilmez.
     *
     * @param \stdClass $course
     * @param int $userid
     * @return \cm_info[]
     */
    public static function published_resources_for_user(\stdClass $course, int $userid): array {
        $modinfo = get_fast_modinfo($course, $userid);
        $resources = [];
        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->modname !== 'resource' || !empty($cm->deletioninprogress)) {
                continue;
            }
            if ((int)$cm->visible !== 1) {
                continue;
            }
            if (!$cm->uservisible) {
                continue;
            }
            $resources[] = $cm;
        }
        return $resources;
    }

    /**
     * Bölüm içinde komşu video ile yer değiştir (Yukarı / Aşağı).
     *
     * @param \stdClass $course
     * @param int $cmid
     * @param string $direction up|down
     * @return bool
     */
    public static function move_in_section(\stdClass $course, int $cmid, string $direction): bool {
        if (!in_array($direction, ['up', 'down'], true) || $cmid <= 0) {
            return false;
        }

        $modinfo = get_fast_modinfo($course);
        if (!isset($modinfo->cms[$cmid])) {
            return false;
        }
        $cm = $modinfo->cms[$cmid];
        if (!self::is_mp4_resource($cm)) {
            return false;
        }

        $sectionvideos = [];
        foreach ($modinfo->get_cms() as $candidate) {
            if ((int)$candidate->sectionnum !== (int)$cm->sectionnum) {
                continue;
            }
            if (!self::is_mp4_resource($candidate)) {
                continue;
            }
            $sectionvideos[] = (int)$candidate->id;
        }

        $pos = array_search($cmid, $sectionvideos, true);
        if ($pos === false) {
            return false;
        }

        if ($direction === 'up') {
            if ($pos < 1) {
                return false;
            }
            $beforecmid = $sectionvideos[$pos - 1];
            return \core_courseformat\formatactions::cm($course)->move_before($cmid, $beforecmid);
        }

        // down: sonraki videoyu bunun önüne al → bu video bir alta iner.
        if ($pos >= count($sectionvideos) - 1) {
            return false;
        }
        $nextcmid = $sectionvideos[$pos + 1];
        return \core_courseformat\formatactions::cm($course)->move_before($nextcmid, $cmid);
    }
}
