<?php
namespace local_wamakademi;

defined('MOODLE_INTERNAL') || die();

/**
 * Eğitim kimlik numarası.
 *
 * Her numara bu tabloda bir satırdır. Satır silinmez; eğitim silinse de numara tekrar verilmez.
 */
class coursenumber {
    /**
     * Var olan eğitimlerin numaralarını deftere yazar, boş olanlara sıradaki numarayı verir.
     */
    public static function backfill(): void {
        global $DB;

        $courses = $DB->get_records_select('course', 'id <> ?', [SITEID], 'id ASC', 'id, idnumber');
        foreach ($courses as $course) {
            $code = trim((string)$course->idnumber);
            if ($code === '') {
                continue;
            }
            self::reserve($code, (int)$course->id, false);
        }
        foreach ($courses as $course) {
            if (trim((string)$course->idnumber) === '') {
                self::issue_next((int)$course->id);
            }
        }
    }

    /**
     * Form açılınca numarayı ayırır. Kayıt olmazsa satır kursa bağlanmaz ve tekrar verilmez.
     *
     * @return string
     */
    public static function reserve_for_form(): string {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $row = (object) [
            'code' => 'tmp-' . bin2hex(random_bytes(8)),
            'courseid' => 0,
            'timecreated' => time(),
            'timedeleted' => 0,
        ];
        $id = $DB->insert_record('local_wamakademi_coursenumber', $row);
        $code = (string)$id;
        $row->id = $id;
        $row->code = $code;
        $DB->update_record('local_wamakademi_coursenumber', $row);
        $transaction->allow_commit();
        return $code;
    }

    /**
     * Formda gösterilen boştaki numarayı bu eğitime bağlar.
     *
     * @param int $courseid
     * @param string $code
     * @return bool
     */
    public static function claim(int $courseid, string $code): bool {
        global $DB;

        $code = trim($code);
        if ($code === '' || $courseid <= 0) {
            return false;
        }
        $row = $DB->get_record_select(
            'local_wamakademi_coursenumber',
            'code = ? AND courseid = 0 AND timedeleted = 0',
            [$code]
        );
        if (!$row) {
            return false;
        }
        $row->courseid = $courseid;
        $DB->update_record('local_wamakademi_coursenumber', $row);
        $DB->set_field('course', 'idnumber', $code, ['id' => $courseid]);
        return true;
    }

    /**
     * Formda ayrılmış ve henüz bir eğitime yazılmamış numara mı?
     *
     * @param string $code
     * @return bool
     */
    public static function is_pending(string $code): bool {
        global $DB;

        $code = trim($code);
        if ($code === '') {
            return false;
        }
        return $DB->record_exists_select(
            'local_wamakademi_coursenumber',
            'code = ? AND courseid = 0 AND timedeleted = 0',
            [$code]
        );
    }

    /**
     * Yeni eğitime sıradaki numarayı yazar. Formda ayrılmış numara varsa onu kullanır.
     *
     * @param int $courseid
     * @return string
     */
    public static function issue_next(int $courseid): string {
        global $DB;

        if ($courseid <= 0 || $courseid === (int)SITEID) {
            return '';
        }

        $current = trim((string)$DB->get_field('course', 'idnumber', ['id' => $courseid]));
        if ($current !== '' && self::claim($courseid, $current)) {
            return $current;
        }
        $existing = $DB->get_record('local_wamakademi_coursenumber', ['courseid' => $courseid, 'timedeleted' => 0]);
        if ($existing) {
            if ($current !== $existing->code) {
                $DB->set_field('course', 'idnumber', $existing->code, ['id' => $courseid]);
            }
            return $existing->code;
        }

        $transaction = $DB->start_delegated_transaction();
        $row = (object) [
            'code' => 'tmp-' . bin2hex(random_bytes(8)),
            'courseid' => $courseid,
            'timecreated' => time(),
            'timedeleted' => 0,
        ];
        $id = $DB->insert_record('local_wamakademi_coursenumber', $row);
        $code = (string)$id;
        $row->id = $id;
        $row->code = $code;
        $DB->update_record('local_wamakademi_coursenumber', $row);
        $DB->set_field('course', 'idnumber', $code, ['id' => $courseid]);
        $transaction->allow_commit();
        return $code;
    }

    /**
     * Bu kodu kalıcı olarak ayırır. Eğitim silinse de satır durur.
     *
     * @param string $code
     * @param int $courseid
     * @param bool $retired
     */
    public static function reserve(string $code, int $courseid, bool $retired): void {
        global $DB;

        $code = trim($code);
        if ($code === '') {
            return;
        }
        $row = $DB->get_record('local_wamakademi_coursenumber', ['code' => $code]);
        $now = time();
        if ($row) {
            if ($retired && (int)$row->timedeleted === 0) {
                $row->timedeleted = $now;
                $DB->update_record('local_wamakademi_coursenumber', $row);
            } else if (!$retired && (int)$row->courseid === 0) {
                $row->courseid = $courseid;
                $row->timedeleted = 0;
                $DB->update_record('local_wamakademi_coursenumber', $row);
            }
            return;
        }

        $record = (object) [
            'code' => $code,
            'courseid' => $retired ? 0 : $courseid,
            'timecreated' => $now,
            'timedeleted' => $retired ? $now : 0,
        ];
        if (ctype_digit($code)) {
            $record->id = (int)$code;
            if (!$DB->record_exists('local_wamakademi_coursenumber', ['id' => $record->id])) {
                $DB->insert_record_raw('local_wamakademi_coursenumber', $record, false, false, true);
                return;
            }
            unset($record->id);
        }
        $DB->insert_record('local_wamakademi_coursenumber', $record);
    }

    /**
     * Eğitim silindi. Numara defterde kalır.
     *
     * @param int $courseid
     * @param string $code
     */
    public static function retire(int $courseid, string $code): void {
        global $DB;

        $now = time();
        $row = $DB->get_record('local_wamakademi_coursenumber', ['courseid' => $courseid, 'timedeleted' => 0]);
        if ($row) {
            $row->timedeleted = $now;
            $DB->update_record('local_wamakademi_coursenumber', $row);
            return;
        }
        self::reserve($code, $courseid, true);
    }
}
