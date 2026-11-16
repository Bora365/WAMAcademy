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
 * Statik eklenti dosyası için sürüm parametreli URL üret.
 *
 * @param string $relativepath /local/wamakademi/... yolu
 * @return moodle_url
 */
function local_wamakademi_asset_url(string $relativepath): moodle_url {
    global $CFG;
    $full = $CFG->dirroot . $relativepath;
    $params = [];
    if (is_readable($full)) {
        $params['v'] = (string)filemtime($full);
    }
    return new moodle_url($relativepath, $params);
}

/**
 * Profil düzenleme URL'sini Moodle kurallarıyla üret.
 *
 * @param stdClass $user
 * @param bool $iscurrentuser
 * @param int $courseid
 * @return moodle_url|null
 */
function local_wamakademi_edit_profile_url(stdClass $user, bool $iscurrentuser, int $courseid): ?moodle_url {
    global $USER;

    if (!isloggedin() || isguestuser($user) || is_mnet_remote_user($user)) {
        return null;
    }

    $systemcontext = context_system::instance();
    $usercontext = context_user::instance($user->id, IGNORE_MISSING);
    if (!$usercontext) {
        return null;
    }

    if (($iscurrentuser || is_siteadmin($USER) || !is_siteadmin($user))
            && has_capability('moodle/user:update', $systemcontext)) {
        return new moodle_url('/user/editadvanced.php', [
            'id' => $user->id,
            'course' => $courseid,
            'returnto' => 'profile',
        ]);
    }

    if ((has_capability('moodle/user:editprofile', $usercontext) && !is_siteadmin($user))
            || ($iscurrentuser && has_capability('moodle/user:editownprofile', $systemcontext))) {
        $userauthplugin = false;
        if (!empty($user->auth)) {
            $userauthplugin = get_auth_plugin($user->auth);
        }
        if ($userauthplugin && $userauthplugin->can_edit_profile()) {
            $url = $userauthplugin->edit_profile_url();
            if (empty($url)) {
                return new moodle_url('/user/edit.php', [
                    'id' => $user->id,
                    'returnto' => 'profile',
                ]);
            }
            return new moodle_url($url);
        }
    }

    return null;
}

/**
 * Yan paneldeki etkin bağlantının kimliği (yalnızca birebir rota eşleşmesi).
 *
 * @return string home|catalog|mycourses|calendar|dashboard veya eşleşme yoksa boş dize
 */
function local_wamakademi_menu_active_id(): string {
    global $CFG;

    $path = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $base = (string)parse_url($CFG->wwwroot, PHP_URL_PATH);
    if ($base !== '' && strpos($path, $base) === 0) {
        $path = substr($path, strlen($base));
    }
    $path = '/' . ltrim($path, '/');

    $routes = [
        'home' => ['/local/wamakademi/index.php', '/local/wamakademi/'],
        'catalog' => ['/local/wamakademi/catalog.php'],
        'mycourses' => ['/local/wamakademi/mycourses.php'],
        'calendar' => ['/local/wamakademi/calendar.php', '/local/wamakademi/js/calendar_view.php'],
        'dashboard' => ['/my/', '/my/index.php'],
    ];
    foreach ($routes as $id => $paths) {
        if (in_array($path, $paths, true)) {
            return $id;
        }
    }
    return '';
}

/**
 * Ortak menü / ayarlar yapılandırmasını JS'e aktar.
 *
 * @return array
 */
function local_wamakademi_menu_config(): array {
    global $USER, $COURSE, $PAGE, $CFG, $OUTPUT, $SITE;

    require_once($CFG->dirroot . '/user/lib.php');

    // Sol panel: yalnızca site gezinmesi (Profil / Site yönetimi yok).
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
    ];
    // Kontrol paneli yalnızca /my/ sayfasına gerçekten ulaşabilen kullanıcılar için listelenir;
    // diğer roller local_wamakademi_after_config() ile Ana Sayfa'ya yönlendirildiğinden aynı hedefe
    // ikinci bir bağlantı eklenmez.
    if (is_siteadmin($USER)) {
        $links[] = [
            'id' => 'dashboard',
            'label' => get_string('myhome'),
            'url' => (new moodle_url('/my/'))->out(false),
        ];
    }

    $courseid = SITEID;
    if (!empty($COURSE) && !empty($COURSE->id) && (int)$COURSE->id > 1) {
        $courseid = (int)$COURSE->id;
    } else if ($PAGE->context && $PAGE->context->contextlevel >= CONTEXT_COURSE) {
        $coursectx = $PAGE->context->get_course_context(false);
        if ($coursectx) {
            $courseid = (int)$coursectx->instanceid;
        }
    }

    $profileurl = (new moodle_url('/user/profile.php', ['id' => $USER->id]))->out(false);
    $groups = [];

    // Hesabım.
    $accountitems = [
        [
            'id' => 'profile',
            'label' => get_string('profile'),
            'url' => $profileurl,
            'action' => false,
        ],
    ];

    $editurl = local_wamakademi_edit_profile_url($USER, true, $courseid > 1 ? $courseid : SITEID);
    if ($editurl) {
        $accountitems[] = [
            'id' => 'editprofile',
            'label' => get_string('editmyprofile'),
            'url' => $editurl->out(false),
            'action' => false,
        ];
    }

    $usercontext = context_user::instance($USER->id);
    if (has_capability('moodle/user:manageownfiles', $usercontext)) {
        $accountitems[] = [
            'id' => 'privatefiles',
            'label' => get_string('privatefiles'),
            'url' => (new moodle_url('/user/files.php'))->out(false),
            'action' => false,
        ];
    }

    $groups[] = [
        'id' => 'account',
        'label' => 'Hesabım',
        'items' => $accountitems,
    ];

    // Öğrenme — customusermenuitems hedeflerini kullan.
    $learningitems = [
        [
            'id' => 'grades',
            'label' => get_string('grades', 'grades'),
            'url' => user_mygrades_url()->out(false),
            'action' => false,
        ],
        [
            'id' => 'calendar',
            'label' => get_string('calendar', 'calendar'),
            'url' => (new moodle_url('/local/wamakademi/calendar.php'))->out(false),
            'action' => false,
        ],
    ];

    if (has_capability('moodle/reportbuilder:view', context_system::instance())
            || has_capability('moodle/reportbuilder:viewall', context_system::instance())) {
        $learningitems[] = [
            'id' => 'reports',
            'label' => get_string('reports'),
            'url' => (new moodle_url('/reportbuilder/index.php'))->out(false),
            'action' => false,
        ];
    }

    $groups[] = [
        'id' => 'learning',
        'label' => 'Öğrenme',
        'items' => $learningitems,
    ];

    // Tercihler + Dil.
    $prefitems = [
        [
            'id' => 'preferences',
            'label' => get_string('preferences'),
            'url' => (new moodle_url('/user/preferences.php'))->out(false),
            'action' => false,
        ],
    ];

    $langitems = [];
    try {
        $languagemenu = new \core\output\language_menu($PAGE);
        $langdata = $languagemenu->export_for_template($OUTPUT);
        if (!empty($langdata['items'])) {
            foreach ($langdata['items'] as $item) {
                $url = $item['url'];
                if ($url instanceof moodle_url) {
                    $url = $url->out(false);
                } else if (is_object($url) && method_exists($url, 'out')) {
                    $url = $url->out(false);
                } else {
                    $url = (string)$url;
                }
                $langitems[] = [
                    'id' => 'lang_' . preg_replace('/[^a-z0-9_-]/i', '', (string)($item['title'] ?? 'x')),
                    'label' => (string)($item['title'] ?? $item['text'] ?? ''),
                    'url' => $url,
                    'active' => !empty($item['isactive']),
                    'action' => false,
                ];
            }
        }
    } catch (Throwable $e) {
        $langitems = [];
    }

    $groups[] = [
        'id' => 'preferences',
        'label' => 'Tercihler',
        'items' => $prefitems,
        'languages' => $langitems,
        'languageLabel' => get_string('language'),
    ];

    // Yönetim.
    $adminitems = [];
    if ($courseid > 1) {
        $coursecontext = context_course::instance($courseid);
        if (has_capability('moodle/course:update', $coursecontext)) {
            $adminitems[] = [
                'id' => 'courseedit',
                'label' => 'Kurs ayarları',
                'url' => (new moodle_url('/course/edit.php', ['id' => $courseid]))->out(false),
                'action' => false,
            ];
        }
        // Content Workflow R1: eğitim bağlamında video yükle (yetki backend'de de denetlenir).
        if (\local_wamakademi\access::can_manage_videos((int)$USER->id, (int)$courseid)) {
            $adminitems[] = [
                'id' => 'uploadvideo',
                'label' => get_string('menuuploadvideo', 'local_wamakademi'),
                'url' => (new moodle_url('/local/wamakademi/upload.php', [
                    'courseid' => (int)$courseid,
                ]))->out(false),
                'action' => false,
            ];
        }
    }
    // Kurs oluştur: moodle/course:create olan kullanıcılara, doğru kategori bağlamıyla.
    $createurl = \local_wamakademi\access::course_create_url(
        ($courseid > 1 && !empty($PAGE->course->category)) ? (int)$PAGE->course->category : 0
    );
    if ($createurl !== null) {
        $adminitems[] = [
            'id' => 'createcourse',
            'label' => get_string('menucreatecourse', 'local_wamakademi'),
            'url' => $createurl->out(false),
            'action' => false,
        ];
    }
    // Yan panelden kaldırılan "Site yönetimi" erişimi (özellikle üst çubuk gezinmesinin görünmediği dar ekranlarda)
    // Ayarlar menüsünde kalır. Koşul, Moodle birincil gezinmesindeki "Site yönetimi" düğümüyle aynıdır.
    $hassiteadmin = has_capability('moodle/site:config', context_system::instance());
    if (!$hassiteadmin) {
        try {
            $hassiteadmin = (bool)$PAGE->settingsnav->find('siteadministration', \navigation_node::TYPE_SITE_ADMIN);
        } catch (Throwable $e) {
            $hassiteadmin = false;
        }
    }
    if ($hassiteadmin) {
        $adminitems[] = [
            'id' => 'admin',
            'label' => 'Site yönetimi',
            'url' => (new moodle_url('/admin/search.php'))->out(false),
            'action' => false,
        ];
    }
    if (!empty($adminitems)) {
        $groups[] = [
            'id' => 'management',
            'label' => 'Yönetim',
            'items' => $adminitems,
        ];
    }

    // Oturum: tarayıcı oturumları + işlemler.
    $sessionitems = [];
    $actionitems = [];

    if (!\core\session\manager::is_loggedinas()
            && has_capability('report/usersessions:manageownsessions', $usercontext)) {
        $sessionitems[] = [
            'id' => 'usersessions',
            'label' => get_string('navigationlink', 'report_usersessions'),
            'url' => (new moodle_url('/report/usersessions/user.php'))->out(false),
            'action' => false,
        ];
    }

    // Moodle kullanıcı menüsünden işlemleri ve bilinmeyen özel öğeleri al.
    $knownurls = [];
    foreach ($groups as $g) {
        foreach (($g['items'] ?? []) as $it) {
            $knownurls[] = preg_replace('/[?#].*$/', '', (string)$it['url']);
        }
    }
    $knownurls[] = preg_replace('/[?#].*$/', '', $profileurl);

    $info = user_get_user_navigation_info($USER, $PAGE);
    $otheritems = [];
    foreach ($info->navitems as $nav) {
        if (($nav->itemtype ?? '') === 'divider' || ($nav->itemtype ?? '') === 'invalid') {
            continue;
        }
        $url = isset($nav->url) ? (string)$nav->url : '';
        $pathonly = preg_replace('/[?#].*$/', '', $url);
        $title = (string)($nav->title ?? '');
        $identifier = (string)($nav->titleidentifier ?? '');

        $islogout = (strpos($identifier, 'logout') !== false)
            || (strpos($pathonly, '/login/logout.php') !== false)
            || (strpos($pathonly, '/course/loginas.php') !== false && empty(optional_param('user', 0, PARAM_INT)));
        $isswitch = (strpos($identifier, 'switchrole') !== false)
            || (strpos($pathonly, '/course/switchrole.php') !== false);

        if ($islogout || $isswitch) {
            $actionitems[] = [
                'id' => $isswitch ? 'switchrole' : 'logout',
                'label' => $title,
                'url' => $url,
                'action' => true,
            ];
            continue;
        }

        if (($nav->itemtype ?? '') !== 'link' || $url === '') {
            continue;
        }

        // Tercihler ve bilinen hesap bağlantılarını atla.
        $skip = false;
        foreach ($knownurls as $known) {
            if ($known !== '' && strpos($pathonly, $known) !== false) {
                $skip = true;
                break;
            }
        }
        if ($skip || strpos($pathonly, '/user/preferences.php') !== false) {
            continue;
        }

        // Tanınmayan özel menü / kanca öğeleri — Betik ekle dahil korunur.
        $otheritems[] = [
            'id' => 'custom_' . substr(sha1($pathonly . $title), 0, 8),
            'label' => $title !== '' ? $title : $pathonly,
            'url' => $url,
            'action' => false,
        ];
    }

    if (!empty($otheritems)) {
        $groups[] = [
            'id' => 'other',
            'label' => 'Diğer',
            'items' => $otheritems,
        ];
    }

    $groups[] = [
        'id' => 'session',
        'label' => 'Oturum',
        'items' => $sessionitems,
        'actions' => $actionitems,
    ];

    $banner = '';
    if (!empty($info->metadata['asotheruser'])) {
        $banner = get_string('loggedinas', 'moodle', $info->metadata['userfullname'] ?? '');
    } else if (!empty($info->metadata['asotherrole']) && !empty($info->metadata['rolename'])) {
        $banner = (string)$info->metadata['rolename'];
    }

    $unreadlabel = get_string('unreadconversations', 'core_message', '{$a}');

    // Yalnızca panelde temsil edilen ve yolu birebir eşleşen rota etkin sayılır; eşleşme yoksa hiçbiri seçilmez.
    $active = local_wamakademi_menu_active_id();

    // Geriye dönük uyumluluk: düz settings listesi (eski JS).
    $settings = [];
    foreach ($groups as $g) {
        foreach (($g['items'] ?? []) as $it) {
            $settings[] = $it;
        }
        foreach (($g['actions'] ?? []) as $it) {
            $settings[] = $it;
        }
    }

    // Sağ üstteki profil menüsü: Profil, Şu role geç (varsa), Çıkış yap.
    $usermenuitems = [[
        'id' => 'profile',
        'label' => get_string('profile'),
        'url' => $profileurl,
        'action' => false,
    ]];
    foreach (['switchrole', 'logout'] as $actionid) {
        foreach ($actionitems as $actionitem) {
            if ($actionitem['id'] === $actionid) {
                $usermenuitems[] = $actionitem;
                break;
            }
        }
    }

    return [
        'wwwroot' => (new moodle_url('/'))->out(false),
        'homeurl' => (new moodle_url('/local/wamakademi/index.php'))->out(false),
        'sitename' => format_string($SITE->fullname, true, ['context' => context_system::instance()]),
        'logourl' => (new moodle_url('/local/wamakademi/pix/wam-logo-navbar.png'))->out(false),
        'userfullname' => fullname($USER),
        'usermenu' => $usermenuitems,
        'profileurl' => $profileurl,
        'active' => $active,
        'links' => $links,
        'settings' => $settings,
        'groups' => $groups,
        'banner' => $banner,
        'unreadConversationsTemplate' => $unreadlabel,
    ];
}

/**
 * Hedef kullanıcı id'sini sayfa bağlamından çıkar (yoksa 0).
 *
 * @return int
 */
function local_wamakademi_account_target_userid(): int {
    global $PAGE, $USER;

    if (!empty($PAGE->context) && $PAGE->context->contextlevel === CONTEXT_USER) {
        return (int)$PAGE->context->instanceid;
    }

    $idparam = optional_param('id', 0, PARAM_INT);
    if ($idparam > 0) {
        return $idparam;
    }
    if ($idparam === -1) {
        // user/editadvanced.php?id=-1: yönetici yeni kullanıcı oluşturuyor; kendi hesabı değil.
        return -1;
    }

    $userid = optional_param('userid', 0, PARAM_INT);
    if ($userid > 0) {
        return $userid;
    }

    return (int)$USER->id;
}

/**
 * Hesap / ayarlar ağacındaki kişisel bir sayfada mıyız?
 * Kurs katılımcı listesi, site rozet yönetimi, admin raporları ve
 * başka kullanıcının not raporunu bilerek dışarıda bırakır.
 * Başka kullanıcının profili (user-profile) istisna: kabuk uygulanır.
 *
 * @return bool
 */
function local_wamakademi_is_account_page(): bool {
    global $PAGE, $SCRIPT, $USER;

    if (!isloggedin() || isguestuser()) {
        return false;
    }

    $pagetype = (string)$PAGE->pagetype;
    $path = '';
    if ($PAGE->url instanceof moodle_url) {
        $path = $PAGE->url->get_path();
    }
    if ($path === '' && !empty($SCRIPT)) {
        $path = (string)$SCRIPT;
    }
    $path = str_replace('\\', '/', $path);

    $selfpages = [
        'user-edit',
        'user-editadvanced',
        'user-preferences',
        'user-files',
        'user-language',
        'user-forum',
        'user-editor',
        'user-calendar',
        'user-managetoken',
        'user-contentbank',
        'user-defaulthomepage',
        'user-contactsitesupport',
        'user-policy',
        'user-portfolio',
        'user-portfoliologs',
        'login-change_password',
        'message-notificationpreferences',
        'message-edit',
        'grade-report-overview-index',
        'report-usersessions-user',
        'badges-mybadges',
        'badges-badge',
        'badges-preferences',
        'badges-mybackpack',
        'calendar-managesubscriptions',
        'calendar-export',
        'admin-tool-dataprivacy-mydatarequests',
        'admin-tool-dataprivacy-createdatarequest',
        'admin-tool-mfa-user_preferences',
        'auth-oauth2-linkedlogins',
    ];

    // Kendi profili veya başka kullanıcının profili (kabuk + işlem farkı JS'te).
    if ($pagetype === 'user-profile' || preg_match('#(?:^|/)user/profile\.php$#', $path)) {
        return true;
    }

    // Not genel görünümü yalnızca kullanıcı menüsünden (site kursu) açıldığında hesap ekranıdır;
    // bir kursun Notlar gezinmesinden açıldığında eğitim ekranıdır.
    if ($pagetype === 'grade-report-overview-index' && !empty($PAGE->course->id)
            && (int)$PAGE->course->id !== (int)SITEID) {
        return false;
    }

    if (in_array($pagetype, $selfpages, true)) {
        $target = local_wamakademi_account_target_userid();
        // Çoğu tercihler sayfasında id yok → current user.
        if ($target === (int)$USER->id || $target === 0) {
            return true;
        }
        // Oturumlar / rozet detayı kendi kullanıcısına ait olmalı.
        return $target === (int)$USER->id;
    }

    $selfpatterns = [
        '#(?:^|/)user/(?:edit|editadvanced|preferences|files|language|forum|editor|calendar|managetoken|contentbank|defaulthomepage|contactsitesupport|policy|portfolio|portfoliologs)\.php$#',
        '#(?:^|/)login/change_password\.php$#',
        '#(?:^|/)message/(?:notificationpreferences|edit)\.php$#',
        '#(?:^|/)grade/report/overview/#',
        '#(?:^|/)report/usersessions/user\.php$#',
        '#(?:^|/)badges/(?:mybadges|badge|preferences|mybackpack)\.php$#',
        '#(?:^|/)calendar/(?:managesubscriptions|export)\.php$#',
        '#(?:^|/)admin/tool/dataprivacy/(?:mydatarequests|createdatarequest)\.php$#',
        '#(?:^|/)admin/tool/mfa/user_preferences\.php$#',
        '#(?:^|/)auth/oauth2/linkedlogins\.php$#',
    ];
    foreach ($selfpatterns as $re) {
        if (preg_match($re, $path)) {
            $target = local_wamakademi_account_target_userid();
            return ($target === (int)$USER->id || $target === 0);
        }
    }

    return false;
}

/**
 * Hesap sayfaları (profil / düzenle / tercihler / alt sayfalar) için JS yapılandırması.
 *
 * @return array
 */
function local_wamakademi_account_config(): array {
    global $USER, $PAGE;

    $profileuserid = (int)$USER->id;
    if ($PAGE->context && $PAGE->context->contextlevel === CONTEXT_USER) {
        $profileuserid = (int)$PAGE->context->instanceid;
    }
    $idparam = optional_param('id', 0, PARAM_INT);
    if ($idparam > 0 && (strpos((string)$PAGE->pagetype, 'user-') === 0)) {
        $profileuserid = $idparam;
    }

    $mycoursesurl = (new moodle_url('/local/wamakademi/mycourses.php'))->out(false);
    $profileurl = (new moodle_url('/user/profile.php', ['id' => $USER->id]))->out(false);
    $preferencesurl = (new moodle_url('/user/preferences.php'))->out(false);

    $path = '';
    if ($PAGE->url instanceof moodle_url) {
        $path = $PAGE->url->get_path();
    }

    return [
        'currentUserId' => (int)$USER->id,
        'profileUserId' => $profileuserid,
        'pagetype' => (string)$PAGE->pagetype,
        'path' => $path,
        'mycoursesUrl' => $mycoursesurl,
        'profileUrl' => $profileurl,
        'preferencesUrl' => $preferencesurl,
        'strings' => [
            'editprofile' => get_string('editmyprofile'),
            'advanced' => 'Gelişmiş ayarlar',
            'account' => 'Kullanıcı hesabı',
            'notifications' => 'Bildirimler ve mesajlar',
            'basic' => 'Temel bilgiler',
            'location' => 'Konum',
            'picture' => get_string('pictureofuser'),
            'description' => get_string('userdescription'),
            'thissession' => get_string('thissession', 'report_usersessions'),
            'thisdevice' => 'Bu cihaz',
            'nogradeyet' => 'Henüz not girilmedi',
            'gradesempty' => 'Henüz notlandırılmış eğitimin yok. Notlar, eğitmen değerlendirme girdikçe burada görünür.',
            'mycourses' => 'Eğitimlerim',
            'logout' => get_string('logout'),
            'preferences' => get_string('preferences'),
            'profile' => get_string('profile'),
            'backtopreferences' => 'Tercihlere dön',
            'backtoprofile' => 'Profile dön',
            'savechanges' => get_string('savechanges'),
            'cancel' => get_string('cancel'),
        ],
    ];
}

/**
 * Profil ağacına WAM işaret düğümleri ekle (CSS sırası ve başlık verisi).
 *
 * @param \core_user\output\myprofile\tree $tree
 * @param stdClass $user
 * @param bool $iscurrentuser
 * @param stdClass|null $course
 */
function local_wamakademi_myprofile_navigation($tree, $user, $iscurrentuser, $course) {
    // sort_categories çağrılmadan önce düğümler tree->nodes üzerinde durur.
    $categories = $tree->categories;
    $occupied = [];
    foreach ($tree->nodes as $existing) {
        $occupied[$existing->parentcat] = true;
    }

    $markernames = [
        'contact',
        'coursedetails',
        'badges',
        'miscellaneous',
        'reports',
        'administration',
        'loginactivity',
        'privacyandpolicies',
    ];

    foreach ($markernames as $catname) {
        if (empty($categories[$catname]) || empty($occupied[$catname])) {
            continue;
        }
        $marker = new \core_user\output\myprofile\node(
            $catname,
            'wam_marker_' . $catname,
            '',
            null,
            null,
            html_writer::span('', 'wam-acc-marker', [
                'data-wam-marker' => '1',
                'data-wam-cat' => $catname,
                'aria-hidden' => 'true',
            ])
        );
        try {
            $tree->add_node($marker);
        } catch (Throwable $e) {
            // Zaten eklenmiş olabilir.
        }
    }

    // Bölüm bilgisi — kendi profilinde başlık için.
    if ($iscurrentuser && !empty($user->department)) {
        try {
            $deptnode = new \core_user\output\myprofile\node(
                'contact',
                'wam_header_department',
                '',
                null,
                null,
                html_writer::span(format_string($user->department), 'wam-acc-dept-source', [
                    'data-wam-department' => '1',
                    'hidden' => 'hidden',
                ])
            );
            $tree->add_node($deptnode);
        } catch (Throwable $e) {
            // Yok say.
        }
    }
}

/**
 * Site yönetimi (sistem bağlamında "admin" veya "report" yerleşimi) sayfasında mıyız?
 *
 * Kapsam /admin/ dizini ile sınırlı değildir: yönetim ağacındaki rapor, kimlik
 * doğrulama, kayıt yöntemi, blok, yerel eklenti vb. sayfalar admin_externalpage_setup()
 * ile aynı "admin" (bazı raporlar için "report") sayfa yerleşimini kullanır. Erişim denetimi Moodle'a aittir; bu
 * fonksiyon yalnızca görünümü seçer, yetki vermez.
 *
 * @return bool
 */
function local_wamakademi_is_admin_page(): bool {
    global $PAGE;
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }
    if (!isloggedin() || isguestuser() || !in_array((string)$PAGE->pagelayout, ['admin', 'report'], true)) {
        // Yerleşim sonradan değişebileceği için olumsuz sonucu saklamıyoruz.
        return false;
    }

    // Bazı yönetim sayfaları (ör. Ön sayfa ayarları) bağlamı sistem değil site kursu olarak ayarlar.
    $issitecontext = (int)$PAGE->context->contextlevel === CONTEXT_COURSE
        && (int)$PAGE->context->instanceid === (int)SITEID
        && strpos($PAGE->url->get_path(), '/admin/') === 0;

    $cache = ((int)$PAGE->context->contextlevel === CONTEXT_SYSTEM || $issitecontext)
        && !local_wamakademi_is_account_page();

    return $cache;
}

/**
 * Eğitim (kurs) bağlamındaki sayfa türü.
 *
 * Karar URL'den değil Moodle'ın sayfa bağlamından verilir: site kursu dışındaki bir kursun
 * bağlamında (kurs, etkinlik veya blok) olan ve standart kurs yerleşimlerinden birini kullanan
 * sayfalar. Hesap sayfaları ve video ders yerleşimi (mod-resource-view) kendi stillerini kullanır.
 * Yalnızca görünümü seçer; yetki kontrolü Moodle'a aittir.
 *
 * Kategori bağlamındaki yönetim sayfaları (kategori ayarları, kategoride kurs ekleme vb.) aynı kabuğu
 * 'category' türüyle kullanır.
 *
 * @return string|null 'course' (kurs bağlamı), 'mod' (etkinlik bağlamı), 'category' (kategori bağlamı) veya null.
 */
function local_wamakademi_course_page_kind(): ?string {
    global $PAGE;

    // Not: moodle_page __isset() tanımlamaz; empty($PAGE->context) her zaman true döner. Doğrudan okunur.
    if (!isloggedin() || isguestuser() || !($PAGE->context instanceof context)) {
        return null;
    }
    $layouts = ['course', 'incourse', 'report', 'admin', 'standard', 'base', 'coursecategory'];
    if (!in_array((string)$PAGE->pagelayout, $layouts, true)) {
        return null;
    }
    $level = (int)$PAGE->context->contextlevel;
    if ($level === CONTEXT_COURSECAT) {
        return local_wamakademi_is_account_page() ? null : 'category';
    }
    if (empty($PAGE->course->id)) {
        return null;
    }
    if ((int)$PAGE->course->id === (int)SITEID) {
        // Site ana sayfası: yalnızca katılımcı listesi eğitim kabuğunu kullanır ('site'); diğer ön sayfa ekranları değişmez.
        return ((string)$PAGE->pagetype === 'course-view-participants' && $level === CONTEXT_COURSE) ? 'site' : null;
    }
    if (!in_array($level, [CONTEXT_COURSE, CONTEXT_MODULE, CONTEXT_BLOCK], true)) {
        return null;
    }
    if ((string)$PAGE->pagetype === 'mod-resource-view' || local_wamakademi_is_account_page()) {
        return null;
    }
    return $level === CONTEXT_MODULE ? 'mod' : 'course';
}

/**
 * Eğitim sayfalarına eklenecek gövde sınıfları.
 *
 * @return string[]
 */
function local_wamakademi_course_body_classes(): array {
    global $PAGE;

    $kind = local_wamakademi_course_page_kind();
    if ($kind === null) {
        return [];
    }
    $pagetype = (string)$PAGE->pagetype;
    // .wam-course-category katalog kartı rozetinde kullanılıyor (styles.css); kategori kabuğu ayrı ad alır.
    $classes = ['wam-course', $kind === 'category' ? 'wam-coursecat' : 'wam-course-' . $kind];
    if ($pagetype === 'course-view-participants') {
        $classes[] = 'wam-course-participants';
        if ($kind === 'site') {
            $classes[] = 'wam-site-participants';
        }
    }
    if ($pagetype === 'course-edit') {
        $classes[] = 'wam-course-settings';
    }
    if (strpos($pagetype, 'grade-') === 0) {
        $classes[] = 'wam-course-grades';
    }
    // Moodle'ın yapışkan başlık/sütun özelliği sayfa düzeyinde kaydırmaya dayanır; bu raporlarda içerik genişliği açılır.
    if (in_array($pagetype, ['grade-report-grader-index', 'grade-report-singleview-index'], true)) {
        $classes[] = 'wam-course-wide';
    }
    return $classes;
}

/**
 * Ortak form katmanı için head içeriği: yerelleştirilmiş "Zorunlu" etiketi CSS değişkeni olarak.
 *
 * @return string
 */
function local_wamakademi_forms_head_html(): string {
    // Zorunlu alan etiketi CSS'te gösterilir (Moodle işareti aria-hidden; alanlar aria-required taşır).
    $label = str_replace(['\\', '"', '<', '>'], ['\\\\', '\\"', '', ''], get_string('formrequired', 'local_wamakademi'));
    return '<style>:root{--wam-req-text:"' . $label . '";}</style>';
}

/**
 * Eğitim ekranı betiği için yerelleştirilmiş metinler.
 *
 * @return array
 */
function local_wamakademi_course_config(): array {
    global $USER, $PAGE;

    $keys = ['courseaddparticipants', 'coursesearchlabel', 'coursesearchplaceholder', 'coursesearchsubmit',
        'coursesearchclear', 'courseadvancedfilters', 'courseinitials', 'courseindexshort', 'menuuploadvideo'];
    $strings = [];
    foreach ($keys as $key) {
        $strings[$key] = get_string($key, 'local_wamakademi');
    }
    $config = ['strings' => $strings, 'uploadurl' => ''];
    $courseid = !empty($PAGE->course->id) ? (int)$PAGE->course->id : 0;
    if ($courseid > 1 && \local_wamakademi\access::can_manage_videos((int)$USER->id, $courseid)) {
        $config['uploadurl'] = (new moodle_url('/local/wamakademi/upload.php', [
            'courseid' => $courseid,
        ]))->out(false);
    }
    return $config;
}

/**
 * Kullanıcı ve katılımcı ekranlarının türü (User R1).
 *
 * Karar sayfa türünden ve Moodle bağlamından verilir:
 * - 'participants': /user/index.php (eğitim veya site ana sayfası bağlamı),
 * - 'list': site yönetimi kullanıcı hesapları listesi (/admin/user.php),
 * - 'new': yeni sistem kullanıcısı (yönetim yerleşimi),
 * - 'edit': başka bir kullanıcının hesabını düzenleme (kendi hesabı hesap ekranı kabuğunu kullanır).
 * Yalnızca görünümü seçer; yetki kontrolü Moodle'a aittir.
 *
 * @return string|null
 */
function local_wamakademi_users_page_kind(): ?string {
    global $PAGE;

    if (!isloggedin() || isguestuser()) {
        return null;
    }
    switch ((string)$PAGE->pagetype) {
        case 'course-view-participants':
            return 'participants';
        case 'admin-user':
            return 'list';
        case 'admin-user-editadvanced':
            return 'new';
        case 'user-editadvanced':
            if (local_wamakademi_is_account_page()) {
                return null;
            }
            return optional_param('id', 0, PARAM_INT) === -1 ? 'new' : 'edit';
    }
    return null;
}

/**
 * Kullanıcı ekranının başlığı (H1). Boşsa Moodle'ın varsayılanı kalır.
 *
 * @param string $kind local_wamakademi_users_page_kind() sonucu.
 * @return string Biçimlendirilmiş (HTML güvenli) başlık veya ''.
 */
function local_wamakademi_users_heading(string $kind): string {
    global $PAGE;

    switch ($kind) {
        case 'participants':
            if ((int)$PAGE->course->id === (int)SITEID) {
                return get_string('participantstitlesite', 'local_wamakademi');
            }
            return get_string('participantstitlecourse', 'local_wamakademi', format_string($PAGE->course->fullname));
        case 'list':
            return get_string('userslisttitle', 'local_wamakademi');
        case 'edit':
            // Yetki hatası sayfasında (başlık "Hata") "Kullanıcıyı düzenle — Hata" üretme.
            if (trim((string)$PAGE->heading) === '' || trim((string)$PAGE->heading) === get_string('error')) {
                return '';
            }
            return get_string('userseditheading', 'local_wamakademi', (string)$PAGE->heading);
    }
    return '';
}

/**
 * Ekranın kapsamını anlatan tek paragraf (HTML).
 *
 * Çekirdek ekranlar (user/index.php, admin/user.php, user/editadvanced.php) başlığın altında çağırır;
 * işlev yoksa (eklenti kapalıysa) çekirdek çıktısı değişmez.
 *
 * @param string $kind 'participants', 'list', 'new' veya 'edit'.
 * @return string
 */
function local_wamakademi_users_scope_html(string $kind): string {
    global $PAGE;

    $text = '';
    switch ($kind) {
        case 'participants':
            if ((int)$PAGE->course->id === (int)SITEID) {
                $text = s(get_string('participantsscopesite', 'local_wamakademi'));
                if (has_capability('moodle/user:create', context_system::instance())) {
                    $link = html_writer::link(new moodle_url('/user/editadvanced.php', ['id' => -1]),
                        s(get_string('addnewuser', 'moodle')));
                    $text .= ' ' . str_replace('{link}', $link,
                        s(get_string('participantsscopesitenew', 'local_wamakademi', '{link}')));
                }
            } else {
                $text = s(get_string('participantsscopecourse', 'local_wamakademi'));
            }
            break;
        case 'list':
            $text = s(get_string('userslistscope', 'local_wamakademi'));
            break;
        case 'new':
            $text = s(get_string('usersnewscope', 'local_wamakademi'));
            break;
        case 'edit':
            $text = s(get_string('userseditscope', 'local_wamakademi'));
            break;
    }
    if ($text === '') {
        return '';
    }
    return html_writer::tag('p', $text, ['class' => 'wam-scope', 'data-region' => 'wam-scope']);
}

/**
 * Kullanıcı ekranları betiği için yerelleştirilmiş metinler (User R1).
 *
 * @param string $kind local_wamakademi_users_page_kind() sonucu.
 * @return array
 */
function local_wamakademi_users_config(string $kind): array {
    $keys = ['participantsactions', 'usersdeleteaccount'];
    $strings = [];
    foreach ($keys as $key) {
        $strings[$key] = get_string($key, 'local_wamakademi');
    }
    return ['kind' => $kind, 'strings' => $strings];
}

/**
 * Site yönetimi sol gezinmesi için şablon verisi.
 *
 * Veri Moodle'ın ikincil (secondary) gezinme düğümlerinden gelir; yani bağlantılar,
 * görünürlük ve yetki kontrolü sabit bir listeden değil yönetim ağacından üretilir.
 * Yönetim ana ekranında (/admin/search.php) sekme olarak, diğer sayfalarda ise
 * bağlantı olarak çalışır.
 *
 * @return array|null Boşsa bu sayfada yönetim gezinmesi gösterilmez.
 */
function local_wamakademi_admin_nav_context(): ?array {
    global $PAGE;
    static $context = false;

    if ($context !== false) {
        return $context;
    }
    $context = null;

    if (!local_wamakademi_is_admin_page() || !$PAGE->has_secondary_navigation()) {
        return null;
    }

    $secondary = $PAGE->secondarynav;
    $ishome = $PAGE->url->compare(new moodle_url('/admin/search.php', ['query' => '']), URL_MATCH_PARAMS);

    $items = [];
    $hasactive = false;
    foreach ($secondary->children as $node) {
        if (!$node->display) {
            continue;
        }
        $tab = !empty($node->tab) ? (string)$node->tab : '';
        $href = '';
        if ($node->action instanceof moodle_url) {
            $href = $node->action->out(false);
        } else if (is_string($node->action) && $node->action !== '') {
            $href = $node->action;
        }
        $paneid = $tab !== '' ? ltrim($tab, '#') : '';
        $active = !empty($node->isactive);
        $hasactive = $hasactive || $active;
        $items[] = [
            'text' => (string)$node->text,
            'key' => (string)$node->key,
            'href' => $tab !== '' ? $tab : $href,
            'istab' => ($ishome && $tab !== ''),
            'paneid' => $paneid,
            'active' => $active,
            'ariacurrent' => ($active && !$ishome),
        ];
    }
    if (empty($items)) {
        return null;
    }
    if ($ishome && !$hasactive) {
        // Yönetim ana ekranı: Moodle ilk sekmeyi (Genel) varsayılan açar.
        $items[0]['active'] = true;
    }
    foreach ($items as &$item) {
        $item['tabindex'] = ($item['active'] || !$item['istab']) ? 0 : -1;
    }
    unset($item);

    return $context = [
        'items' => $items,
        'istabs' => $ishome,
        'homeurl' => (new moodle_url('/admin/search.php'))->out(false),
        'title' => get_string('adminnavtitle', 'local_wamakademi'),
        'label' => get_string('adminnav', 'local_wamakademi'),
        'opentext' => get_string('adminnavopen', 'local_wamakademi'),
        'closetext' => get_string('adminnavclose', 'local_wamakademi'),
    ];
}

/**
 * Body sınıfını HTML çıktısı başlamadan ekle.
 */
function local_wamakademi_before_http_headers() {
    global $PAGE;

    if (!isloggedin() || isguestuser()) {
        return;
    }

    $PAGE->add_body_class('wam-ui');

    if (local_wamakademi_is_admin_page()) {
        $PAGE->add_body_class('wam-admin');
        if ($PAGE->url->compare(new moodle_url('/admin/search.php'), URL_MATCH_BASE)) {
            $PAGE->add_body_class('wam-admin-home');
        } else {
            // Moodle admin pages use the site name as H1 and put the real page name in the title
            // ("Page | Parent | ..."). Show the page name as H1 so every admin screen has a meaningful title.
            global $SITE;
            $sitename = trim(strip_tags(format_string($SITE->fullname, true, ['escape' => false])));
            $heading = trim(strip_tags((string)$PAGE->heading));
            if ($heading === $sitename) {
                $parts = explode(moodle_page::TITLE_SEPARATOR, (string)$PAGE->title);
                $leaf = trim($parts[0] ?? '');
                if ($leaf !== '' && $leaf !== $sitename) {
                    $PAGE->set_heading($leaf, false);
                    $PAGE->add_body_class('wam-admin-titled');
                }
            }
        }
    }

    if (local_wamakademi_is_account_page()) {
        $PAGE->add_body_class('wam-account');
        $pagetype = (string)$PAGE->pagetype;
        if ($pagetype === 'user-edit' || $pagetype === 'user-editadvanced') {
            $PAGE->add_body_class('wam-acc-edit');
        }
    }

    foreach (local_wamakademi_course_body_classes() as $class) {
        $PAGE->add_body_class($class);
    }

    // Kullanıcı ve katılımcı ekranları: kapsamı belirten başlık ve sayfa türü sınıfları.
    $userskind = local_wamakademi_users_page_kind();
    if ($userskind !== null) {
        $PAGE->add_body_class('wam-users');
        $PAGE->add_body_class('wam-users-' . $userskind);
        if ($userskind === 'new' || $userskind === 'edit') {
            $PAGE->add_body_class('wam-users-form');
        }
        $usersheading = local_wamakademi_users_heading($userskind);
        if ($usersheading !== '') {
            $PAGE->set_heading($usersheading, false);
        }
    }
}

/**
 * WAM Akademi ortak head kancası.
 */
function local_wamakademi_before_standard_html_head() {
    global $PAGE, $USER;

    $pagetype = (string)$PAGE->pagetype;
    $headhtml = '';

    // Giriş sayfası stili — yalnızca login-index.
    $logincss = __DIR__ . '/login.css';
    $csspath = '/local/wamakademi/login.css';
    if (!is_readable($logincss)) {
        $logincss = __DIR__ . '/js/login.css';
        $csspath = '/local/wamakademi/js/login.css';
    }
    if ($pagetype === 'login-index' && is_readable($logincss)) {
        $PAGE->requires->css(new moodle_url($csspath, [
            'v' => filemtime($logincss),
        ]));
    }

    if (isloggedin() && !isguestuser()) {
        // Ortak tasarım token'ları + sayfa düzeni (tek yükleme).
        $PAGE->requires->css(
            local_wamakademi_asset_url('/local/wamakademi/css/wam-base.css')
        );

        $PAGE->requires->js_call_amd(
            'local_wamakademi/message_people',
            'init'
        );

        // Hesap ekranları ortak CSS (çark + rozet + sayfa stilleri).
        $PAGE->requires->css(
            local_wamakademi_asset_url('/local/wamakademi/js/account.css')
        );

        // Site yönetimi yerleşimi (yalnız admin sayfalarında).
        if (local_wamakademi_is_admin_page()) {
            $PAGE->requires->css(
                local_wamakademi_asset_url('/local/wamakademi/css/wam-admin.css')
            );
            $PAGE->requires->js(
                local_wamakademi_asset_url('/local/wamakademi/js/wam_admin.js')
            );
        }

        // Ortak üst çubuk: aşağı kaydırınca gizle, yukarı kaydırınca göster.
        $PAGE->requires->js(
            local_wamakademi_asset_url('/local/wamakademi/js/wam_header.js')
        );

        $PAGE->requires->data_for_js('wamMenuConfig', local_wamakademi_menu_config(), true);
        $PAGE->requires->js(
            local_wamakademi_asset_url('/local/wamakademi/js/wam_menu.js')
        );

        if (local_wamakademi_is_account_page()) {
            $PAGE->requires->data_for_js('wamAccountConfig', local_wamakademi_account_config(), true);
            $PAGE->requires->js(
                local_wamakademi_asset_url('/local/wamakademi/js/account_ui.js')
            );
        }

        // Eğitim detay ve yönetim ekranları (kurs/etkinlik bağlamı).
        if (local_wamakademi_course_page_kind() !== null) {
            $PAGE->requires->css(
                local_wamakademi_asset_url('/local/wamakademi/css/wam-course.css')
            );
            $PAGE->requires->data_for_js('wamCourseConfig', local_wamakademi_course_config(), true);
            $PAGE->requires->js(
                local_wamakademi_asset_url('/local/wamakademi/js/course_ui.js')
            );
            // Katılımcılar tablosu: satır sonundaki "İşlemler" menüsü.
            if ($pagetype === 'course-view-participants') {
                $PAGE->requires->js(
                    local_wamakademi_asset_url('/local/wamakademi/js/participants_table.js')
                );
            }
        }

        // Ortak form bileşenleri (alan, seçim, otomatik tamamlama, dosya, editör); sayfa katmanlarından sonra yüklenir.
        $PAGE->requires->css(
            local_wamakademi_asset_url('/local/wamakademi/css/wam-forms.css')
        );
        $headhtml .= local_wamakademi_forms_head_html();

        // Kullanıcı hesabı listesi ve kullanıcı formları (User R1): ortak form katmanından sonra yüklenir (aynı özgüllükte kullanıcı ekranları kazanır).
        $userskind = local_wamakademi_users_page_kind();
        if ($userskind !== null) {
            $PAGE->requires->css(
                local_wamakademi_asset_url('/local/wamakademi/css/wam-users.css')
            );
        }
        if ($userskind === 'list' || $userskind === 'new' || $userskind === 'edit') {
            $PAGE->requires->data_for_js('wamUsersConfig', local_wamakademi_users_config($userskind), true);
            $PAGE->requires->js(
                local_wamakademi_asset_url('/local/wamakademi/js/users_ui.js')
            );
        }

        // İçerik iş akışı (video yükleme / önizleme).
        if (strpos($pagetype, 'local-wamakademi-upload') === 0
                || strpos($pagetype, 'local-wamakademi-publish') === 0) {
            $PAGE->requires->css(
                local_wamakademi_asset_url('/local/wamakademi/css/wam-content.css')
            );
        }

        // Ortak kontrol ailesi (select, açılır menü, autocomplete, accordion, liste); forms/users sonrası.
        $PAGE->requires->css(
            local_wamakademi_asset_url('/local/wamakademi/css/wam-controls.css')
        );
    }

    if (strpos($pagetype, 'course-view-') === 0) {
        $PAGE->requires->js(
            local_wamakademi_asset_url('/local/wamakademi/js/course_view.js')
        );
    }

    /*
     * Aşağıdaki bölüm sadece video/resource ders sayfalarında çalışır.
     * Sıralı kilit yönlendirmesi kaldırıldı; kayıt ve görünürlük Moodle'da kalır.
     */
    if ($pagetype !== 'mod-resource-view') {
        return $headhtml;
    }

    $currentcmid = optional_param('id', 0, PARAM_INT);
    if ($currentcmid <= 0) {
        return $headhtml;
    }

    $PAGE->requires->js(
        local_wamakademi_asset_url('/local/wamakademi/js/video_tracker.js')
    );

    $PAGE->requires->css(
        local_wamakademi_asset_url('/local/wamakademi/lesson.css')
    );

    $PAGE->requires->js(
        local_wamakademi_asset_url('/local/wamakademi/js/lesson_layout.js')
    );

    return $headhtml;
}
