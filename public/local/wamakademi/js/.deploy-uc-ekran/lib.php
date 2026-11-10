<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Öğrenci ve normal kullanıcıları WAM Akademi paneline yönlendirir.
 * Site yöneticileri standart Moodle panelinde kalır.
 */
function local_wamakademi_after_config() {
    global $USER;

    if (!isloggedin() || isguestuser()) {
        return;
    }

    if (is_siteadmin($USER)) {
        return;
    }

    $currentpath = $_SERVER['PHP_SELF'] ?? '';

    if ($currentpath === '/my/index.php' || $currentpath === '/my/') {
        redirect(new moodle_url('/local/wamakademi/index.php'));
    }
}

/**
 * Ortak menü / ayarlar yapılandırmasını JS'e aktar.
 *
 * @return array
 */
function local_wamakademi_menu_config(): array {
    global $USER, $COURSE, $PAGE;

    $links = [
        [
            'id' => 'home',
            'label' => 'Ana Sayfa',
            'url' => (new moodle_url('/local/wamakademi/index.php'))->out(false),
        ],
        [
            'id' => 'catalog',
            'label' => 'Eğitimler',
            'url' => (new moodle_url('/local/wamakademi/catalog.php'))->out(false),
        ],
        [
            'id' => 'mycourses',
            'label' => 'Eğitimlerim',
            'url' => (new moodle_url('/local/wamakademi/mycourses.php'))->out(false),
        ],
        [
            'id' => 'calendar',
            'label' => 'Takvim',
            'url' => (new moodle_url('/local/wamakademi/calendar.php'))->out(false),
        ],
        [
            'id' => 'profile',
            'label' => 'Profil',
            'url' => (new moodle_url('/user/profile.php'))->out(false),
        ],
    ];

    if (is_siteadmin($USER) || has_capability('moodle/site:config', context_system::instance())) {
        $links[] = [
            'id' => 'siteadmin',
            'label' => 'Site yönetimi',
            'url' => (new moodle_url('/admin/search.php'))->out(false),
        ];
    }

    $settings = [
        [
            'id' => 'preferences',
            'label' => 'Tercihler',
            'url' => (new moodle_url('/user/preferences.php'))->out(false),
        ],
    ];

    $courseid = 0;
    if (!empty($COURSE) && !empty($COURSE->id) && (int)$COURSE->id > 1) {
        $courseid = (int)$COURSE->id;
    } else if ($PAGE->context && $PAGE->context->contextlevel >= CONTEXT_COURSE) {
        $coursectx = $PAGE->context->get_course_context(false);
        if ($coursectx) {
            $courseid = (int)$coursectx->instanceid;
        }
    }

    if ($courseid > 1) {
        $coursecontext = context_course::instance($courseid);
        if (has_capability('moodle/course:update', $coursecontext)) {
            $settings[] = [
                'id' => 'courseedit',
                'label' => 'Kurs ayarları',
                'url' => (new moodle_url('/course/edit.php', ['id' => $courseid]))->out(false),
            ];
        }
    }

    if (has_capability('moodle/site:config', context_system::instance())) {
        $settings[] = [
            'id' => 'admin',
            'label' => 'Site yönetimi',
            'url' => (new moodle_url('/admin/search.php'))->out(false),
        ];
    }

    $path = $_SERVER['REQUEST_URI'] ?? '';
    $active = 'home';
    if (strpos($path, '/local/wamakademi/mycourses.php') !== false) {
        $active = 'mycourses';
    } else if (strpos($path, '/local/wamakademi/catalog.php') !== false) {
        $active = 'catalog';
    } else if (strpos($path, '/local/wamakademi/calendar.php') !== false) {
        $active = 'calendar';
    } else if (strpos($path, '/user/profile.php') !== false) {
        $active = 'profile';
    } else if (strpos($path, '/admin/') !== false) {
        $active = 'siteadmin';
    } else if (strpos($path, '/local/wamakademi/index.php') !== false) {
        $active = 'home';
    }

    return [
        'wwwroot' => (new moodle_url('/'))->out(false),
        'homeurl' => (new moodle_url('/local/wamakademi/index.php'))->out(false),
        'active' => $active,
        'links' => $links,
        'settings' => $settings,
    ];
}

/**
 * WAM Akademi ortak head kancası.
 */
function local_wamakademi_before_standard_html_head() {
    global $PAGE, $USER;

    if (isloggedin() && !isguestuser()) {
        $PAGE->requires->js_call_amd(
            'local_wamakademi/message_people',
            'init'
        );

        $PAGE->requires->data_for_js('wamMenuConfig', local_wamakademi_menu_config(), true);
        $PAGE->requires->js(
            new moodle_url('/local/wamakademi/js/wam_menu.js')
        );
    }

    $pagetype = (string)$PAGE->pagetype;

    if (strpos($pagetype, 'course-view-') === 0) {
        $PAGE->requires->js(
            new moodle_url('/local/wamakademi/js/course_view.js')
        );
    }

    /*
     * Aşağıdaki bölüm sadece video/resource ders sayfalarında çalışır.
     * Sıralı kilit yönlendirmesi kaldırıldı; kayıt ve görünürlük Moodle'da kalır.
     */
    if ($pagetype !== 'mod-resource-view') {
        return;
    }

    $currentcmid = optional_param('id', 0, PARAM_INT);
    if ($currentcmid <= 0) {
        return;
    }

    // Video ilerleme sistemi.
    $PAGE->requires->js(
        new moodle_url('/local/wamakademi/js/video_tracker.js')
    );

    // Yeni ders ekranı tasarımı.
    $PAGE->requires->css(
        new moodle_url('/local/wamakademi/lesson.css')
    );

    $PAGE->requires->js(
        new moodle_url('/local/wamakademi/js/lesson_layout.js')
    );
}
