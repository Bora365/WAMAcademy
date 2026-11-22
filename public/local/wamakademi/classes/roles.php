<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — iş rolleri (akademi yöneticisi ve eğitmen kayıt sınırı).

namespace local_wamakademi;

defined('MOODLE_INTERNAL') || die();

/**
 * Akademi iş modeli için rol ve capability kurulumu.
 *
 * Site yöneticisi bir rol değildir; $CFG->siteadmins listesindedir.
 * Akademi yöneticisi, Moodle manager arketipinden kopyalanmaz.
 * Yalnızca burada sayılan izinler verilir.
 */
class roles {

    /** @var string Veritabanındaki kısa ad. Arayüz adı: Akademi yöneticisi. */
    public const ACADEMY_SHORTNAME = 'academymanager';

    /**
     * Akademi yöneticisinin sistem bağlamında ALLOW edilen yetkileri.
     *
     * Kurs silme, kullanıcı hesabı açma/silme/askıya alma, site yapılandırması
     * ve video yükleme bu listede yoktur.
     *
     * @return string[]
     */
    public static function academy_manager_capabilities(): array {
        return [
            'moodle/category:viewhiddencategories',
            'moodle/course:create',
            'moodle/course:update',
            'moodle/course:view',
            'moodle/course:viewhiddencourses',
            'moodle/course:visibility',
            'moodle/course:changefullname',
            'moodle/course:changeshortname',
            'moodle/course:changesummary',
            'moodle/course:changecategory',
            'moodle/course:viewparticipants',
            'moodle/course:enrolreview',
            'moodle/role:assign',
            'enrol/manual:enrol',
            'enrol/manual:unenrol',
            'enrol/manual:manage',
            // Kategori altındaki kurslarda ilerleme / ödev özeti.
            'local/wamakademi:viewreports',
        ];
    }

    /**
     * Eğitmenin kurs kaydı yönetmemesi için PREVENT yapılan yetkiler.
     *
     * İçerik, not ve video yetkilerine dokunulmaz.
     *
     * @return string[]
     */
    public static function editingteacher_enrol_blocks(): array {
        return [
            'enrol/manual:enrol',
            'enrol/manual:unenrol',
            'enrol/manual:manage',
            'enrol/manual:config',
            'moodle/course:enrolconfig',
            'moodle/role:assign',
        ];
    }

    /**
     * Rolü oluşturur veya tanımlı izinlere geri çeker. Tekrar çalıştırılabilir.
     *
     * @return int Akademi yöneticisi rol kimliği.
     */
    public static function sync(): int {
        $roleid = self::ensure_academy_manager();
        self::restrict_editingteacher_enrolment();
        return $roleid;
    }

    /**
     * Eğitmenin kayıt yetkilerini Moodle varsayılanına geri alır.
     *
     * Bir sonraki sync() bu geri almayı yeniden kısıtlar.
     *
     * @return void
     */
    public static function restore_editingteacher_enrolment(): void {
        global $DB;

        $teacher = $DB->get_record('role', ['shortname' => 'editingteacher'], 'id', MUST_EXIST);
        $nonediting = $DB->get_record('role', ['shortname' => 'teacher'], 'id', IGNORE_MISSING);
        $student = $DB->get_record('role', ['shortname' => 'student'], 'id', MUST_EXIST);
        $systemcontext = \context_system::instance();

        foreach (self::editingteacher_enrol_blocks() as $capability) {
            if (!get_capability_info($capability)) {
                continue;
            }
            assign_capability($capability, CAP_ALLOW, (int)$teacher->id, $systemcontext->id, true);
        }

        $targets = [(int)$student->id];
        if ($nonediting) {
            $targets[] = (int)$nonediting->id;
        }
        foreach ($targets as $targetid) {
            if (!$DB->record_exists('role_allow_assign', [
                'roleid' => (int)$teacher->id,
                'allowassign' => $targetid,
            ])) {
                core_role_set_assign_allowed((int)$teacher->id, $targetid);
            }
        }
    }

    /**
     * Akademi yöneticisi rolünü ve atamalarını siler.
     *
     * @return bool Rol vardı ve silindi.
     */
    public static function delete_academy_manager(): bool {
        global $DB;

        $role = $DB->get_record('role', ['shortname' => self::ACADEMY_SHORTNAME], 'id', IGNORE_MISSING);
        if (!$role) {
            return false;
        }
        delete_role((int)$role->id);
        return true;
    }

    /**
     * @return int
     */
    private static function ensure_academy_manager(): int {
        global $DB;

        $role = $DB->get_record('role', ['shortname' => self::ACADEMY_SHORTNAME], 'id, name', IGNORE_MISSING);
        if (!$role) {
            $roleid = (int)create_role(
                'Akademi yöneticisi',
                self::ACADEMY_SHORTNAME,
                'Kurs oluşturur, mevcut hesapları kursa kaydeder ve eğitmen atar. '
                    . 'Teknik site yönetimi, kullanıcı hesabı açma ve kurs silme bu rolde yoktur.',
                ''
            );
        } else {
            $roleid = (int)$role->id;
            if (trim((string)$role->name) === '') {
                $DB->set_field('role', 'name', 'Akademi yöneticisi', ['id' => $roleid]);
            }
        }

        set_role_contextlevels($roleid, [\CONTEXT_COURSECAT, \CONTEXT_COURSE]);

        $systemcontext = \context_system::instance();
        $DB->delete_records('role_capabilities', ['roleid' => $roleid]);
        foreach (self::academy_manager_capabilities() as $capability) {
            if (!get_capability_info($capability)) {
                continue;
            }
            assign_capability($capability, CAP_ALLOW, $roleid, $systemcontext->id, true);
        }

        $DB->delete_records('role_allow_assign', ['roleid' => $roleid]);
        $DB->delete_records('role_allow_override', ['roleid' => $roleid]);
        $DB->delete_records('role_allow_switch', ['roleid' => $roleid]);
        foreach (['editingteacher', 'student'] as $shortname) {
            $target = $DB->get_record('role', ['shortname' => $shortname], 'id', IGNORE_MISSING);
            if ($target) {
                core_role_set_assign_allowed($roleid, (int)$target->id);
            }
        }

        return $roleid;
    }

    /**
     * @return void
     */
    private static function restrict_editingteacher_enrolment(): void {
        global $DB;

        $teacher = $DB->get_record('role', ['shortname' => 'editingteacher'], 'id', IGNORE_MISSING);
        if (!$teacher) {
            return;
        }
        $systemcontext = \context_system::instance();
        foreach (self::editingteacher_enrol_blocks() as $capability) {
            if (!get_capability_info($capability)) {
                continue;
            }
            assign_capability($capability, CAP_PREVENT, (int)$teacher->id, $systemcontext->id, true);
        }
        $DB->delete_records('role_allow_assign', ['roleid' => (int)$teacher->id]);
    }
}
