<?php
// WAM Akademi - Mesajlaşma kişi listesi.

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

require_login();

global $DB, $USER, $CFG;

header('Content-Type: application/json; charset=utf-8');

$currentuserid = (int)$USER->id;

/**
 * Birebir konuşmalarda, karşı kullanıcıya göre okunmamış mesaj sayıları.
 *
 * @param int $userid Oturumdaki kullanıcı.
 * @return array<int, int> otheruserid => unreadcount
 */
function wam_message_unread_counts_by_user(int $userid): array {
    global $DB;

    $sql = "
        SELECT mcm_other.userid AS otheruserid,
               COUNT(m.id) AS unreadcount
          FROM {messages} m
          JOIN {message_conversations} mc
            ON mc.id = m.conversationid
           AND mc.type = :individual
           AND mc.enabled = 1
          JOIN {message_conversation_members} mcm_me
            ON mcm_me.conversationid = m.conversationid
           AND mcm_me.userid = :me
          JOIN {message_conversation_members} mcm_other
            ON mcm_other.conversationid = m.conversationid
           AND mcm_other.userid <> :me2
     LEFT JOIN {message_user_actions} mua
            ON mua.messageid = m.id
           AND mua.userid = :me3
           AND (mua.action = :readaction OR mua.action = :deleteaction)
         WHERE m.useridfrom = mcm_other.userid
           AND mua.id IS NULL
      GROUP BY mcm_other.userid
    ";

    $rows = $DB->get_records_sql($sql, [
        'individual' => \core_message\api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
        'me' => $userid,
        'me2' => $userid,
        'me3' => $userid,
        'readaction' => \core_message\api::MESSAGE_ACTION_READ,
        'deleteaction' => \core_message\api::MESSAGE_ACTION_DELETED,
    ]);

    $map = [];
    foreach ($rows as $row) {
        $count = (int)$row->unreadcount;
        if ($count > 0) {
            $map[(int)$row->otheruserid] = $count;
        }
    }
    return $map;
}

/**
 * Birebir konuşmalarda son mesaj özeti (karşı kullanıcıya göre).
 *
 * @param int $userid
 * @return array<int, array{text:string, timecreated:int, fromme:bool}>
 */
function wam_message_last_by_user(int $userid): array {
    global $DB;

    $sql = "
        SELECT mcm_other.userid AS otheruserid,
               m.fullmessage AS fullmessage,
               m.smallmessage AS smallmessage,
               m.fullmessageformat AS fullmessageformat,
               m.timecreated AS timecreated,
               m.useridfrom AS useridfrom
          FROM {messages} m
          JOIN {message_conversations} mc
            ON mc.id = m.conversationid
           AND mc.type = :individual
           AND mc.enabled = 1
          JOIN {message_conversation_members} mcm_me
            ON mcm_me.conversationid = m.conversationid
           AND mcm_me.userid = :me
          JOIN {message_conversation_members} mcm_other
            ON mcm_other.conversationid = m.conversationid
           AND mcm_other.userid <> :me2
     LEFT JOIN {message_user_actions} mua_del
            ON mua_del.messageid = m.id
           AND mua_del.userid = :me3
           AND mua_del.action = :deleteaction
         WHERE mua_del.id IS NULL
           AND m.timecreated = (
                SELECT MAX(m2.timecreated)
                  FROM {messages} m2
                  JOIN {message_conversation_members} mcm2
                    ON mcm2.conversationid = m2.conversationid
                   AND mcm2.userid = mcm_other.userid
             LEFT JOIN {message_user_actions} mua2
                    ON mua2.messageid = m2.id
                   AND mua2.userid = :me4
                   AND mua2.action = :deleteaction2
                 WHERE m2.conversationid = m.conversationid
                   AND mua2.id IS NULL
           )
    ";

    $rows = $DB->get_records_sql($sql, [
        'individual' => \core_message\api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
        'me' => $userid,
        'me2' => $userid,
        'me3' => $userid,
        'me4' => $userid,
        'deleteaction' => \core_message\api::MESSAGE_ACTION_DELETED,
        'deleteaction2' => \core_message\api::MESSAGE_ACTION_DELETED,
    ]);

    $map = [];
    foreach ($rows as $row) {
        $raw = trim((string)($row->smallmessage !== '' ? $row->smallmessage : $row->fullmessage));
        $text = format_string($raw, true);
        if (\core_text::strlen($text) > 80) {
            $text = \core_text::substr($text, 0, 80) . '…';
        }
        $map[(int)$row->otheruserid] = [
            'text' => $text,
            'timecreated' => (int)$row->timecreated,
            'fromme' => ((int)$row->useridfrom === $userid),
        ];
    }
    return $map;
}

/**
 * Kullanıcı için temel JSON verisi.
 *
 * @param stdClass $user
 * @param array<int, int> $unreadmap
 * @param array<int, array{text:string, timecreated:int, fromme:bool}> $lastmap
 */
function wam_message_user_data(stdClass $user, array $unreadmap = [], array $lastmap = []): array {

    // Moodle'ın kendi online/offline durumunu kullan.
    $isonline = \core_message\helper::show_online_status($user)
        ? \core_message\helper::is_online($user->lastaccess)
        : null;

    $showonlinestatus = !is_null($isonline);
    $userid = (int)$user->id;
    $unread = $unreadmap[$userid] ?? 0;
    $last = $lastmap[$userid] ?? null;
    $lastts = $last['timecreated'] ?? null;

    return [
        'id' => $userid,
        'userid' => $userid,
        'name' => fullname($user),
        'fullname' => fullname($user),
        'username' => $user->username,

        'imageurl' => (new moodle_url(
            '/user/pix.php',
            ['user' => $user->id]
        ))->out(false),

        'profileimageurl' => (new moodle_url(
            '/user/pix.php',
            ['user' => $user->id]
        ))->out(false),

        'subname' => '',
        'lastmessage' => $last['text'] ?? '',
        'lastmessagedate' => $lastts ?: null,
        'unreadcount' => $unread > 0 ? $unread : null,
        'sentfromcurrentuser' => !empty($last['fromme']),
        'ismuted' => false,
        'isblocked' => false,

        // Gerçek Moodle online durumu.
        'showonlinestatus' => $showonlinestatus,
        'isonline' => (bool)$isonline,
    ];
}

/**
 * Okunmamışlar önce, sonra ada göre.
 *
 * @param array $users
 * @return array
 */
function wam_message_sort_users(array $users): array {
    usort($users, static function (array $a, array $b): int {
        $ua = (int)($a['unreadcount'] ?? 0);
        $ub = (int)($b['unreadcount'] ?? 0);
        if ($ua !== $ub) {
            return $ub <=> $ua;
        }
        return strcasecmp((string)$a['name'], (string)$b['name']);
    });
    return $users;
}

/**
 * Kullanıcının herhangi bir course context içerisinde
 * verilen rollerden birine sahip olup olmadığını kontrol eder.
 */
function wam_user_has_course_role(int $userid, array $roles): bool {
    global $DB;

    if (empty($roles)) {
        return false;
    }

    list($insql, $params) = $DB->get_in_or_equal(
        $roles,
        SQL_PARAMS_NAMED,
        'role'
    );

    $params['userid'] = $userid;
    $params['contextlevel'] = CONTEXT_COURSE;

    $sql = "
        SELECT 1
          FROM {role_assignments} ra
          JOIN {role} r
            ON r.id = ra.roleid
          JOIN {context} ctx
            ON ctx.id = ra.contextid
         WHERE ra.userid = :userid
           AND ctx.contextlevel = :contextlevel
           AND r.shortname {$insql}
    ";

    return $DB->record_exists_sql($sql, $params);
}

/**
 * Kullanıcının WAM rolünü belirler.
 *
 * Öncelik:
 * 1. Site admin
 * 2. Öğretmen
 * 3. Öğrenci
 */
function wam_get_message_role(int $userid): string {

    if (is_siteadmin($userid)) {
        return 'admin';
    }

    if (wam_user_has_course_role(
        $userid,
        ['editingteacher', 'teacher']
    )) {
        return 'teacher';
    }

    if (wam_user_has_course_role(
        $userid,
        ['student']
    )) {
        return 'student';
    }

    return 'other';
}

/**
 * Kabul edilmiş Moodle kişilerini döndürür.
 *
 * message_contacts tablosunu kullanır.
 */
function wam_get_contact_ids(int $userid): array {
    global $DB;

    $contactids = [];

    if (!$DB->get_manager()->table_exists('message_contacts')) {
        return [];
    }

    $records = $DB->get_records(
        'message_contacts',
        ['userid' => $userid]
    );

    foreach ($records as $record) {

        if (isset($record->blocked) && (int)$record->blocked === 1) {
            continue;
        }

        if (!empty($record->contactid)) {
            $contactids[(int)$record->contactid] = true;
        }
    }

    /*
     * Bazı Moodle sürümlerinde ilişki ters yönde de tutulabilir.
     * O yüzden contactid = mevcut kullanıcı olan kayıtları da kontrol ediyoruz.
     */
    $reverse = $DB->get_records(
        'message_contacts',
        ['contactid' => $userid]
    );

    foreach ($reverse as $record) {

        if (isset($record->blocked) && (int)$record->blocked === 1) {
            continue;
        }

        if (!empty($record->userid)) {
            $contactids[(int)$record->userid] = true;
        }
    }

    return array_map(
        'intval',
        array_keys($contactids)
    );
}

$currentrole = wam_get_message_role($currentuserid);
$contactids = wam_get_contact_ids($currentuserid);

$users = $DB->get_records_select(
    'user',
    'deleted = 0
     AND suspended = 0
     AND confirmed = 1
     AND id <> :guestid
     AND id <> :currentuserid',
    [
        'guestid' => 1,
        'currentuserid' => $currentuserid,
    ],
    'firstname ASC, lastname ASC',
    'id,username,firstname,lastname,email,picture,imagealt,lastaccess'
);

$admins = [];
$teachers = [];
$friends = [];

$unreadmap = wam_message_unread_counts_by_user($currentuserid);
$lastmap = wam_message_last_by_user($currentuserid);

foreach ($users as $user) {

    $userid = (int)$user->id;
    $role = wam_get_message_role($userid);

    if ($role === 'other') {
        continue;
    }

    $data = wam_message_user_data($user, $unreadmap, $lastmap);
    $data['role'] = $role;

    /*
     * -----------------------------------------------------
     * YÖNETİCİ GİRİŞİ
     * -----------------------------------------------------
     *
     * Herkesi görür.
     */
    if ($currentrole === 'admin') {

        if ($role === 'admin') {
            $admins[] = $data;
        } elseif ($role === 'teacher') {
            $teachers[] = $data;
        } elseif ($role === 'student') {
            $friends[] = $data;
        }

        continue;
    }

    /*
     * -----------------------------------------------------
     * ÖĞRETMEN GİRİŞİ
     * -----------------------------------------------------
     *
     * Yöneticileri, diğer öğretmenleri ve öğrencileri görür.
     * Öğrenciler burada arkadaşlık şartı olmadan listelenir.
     */
    if ($currentrole === 'teacher') {

        if ($role === 'admin') {
            $admins[] = $data;
        } elseif ($role === 'teacher') {
            $teachers[] = $data;
        } elseif ($role === 'student') {
            $friends[] = $data;
        }

        continue;
    }

    /*
     * -----------------------------------------------------
     * ÖĞRENCİ GİRİŞİ
     * -----------------------------------------------------
     *
     * - Öğretmenleri doğrudan görür.
     * - Diğer öğrencileri sadece kabul edilmiş kişiyse görür.
     * - Yöneticileri normalde görmez.
     * - Yönetici kabul edilmiş kişi olmuşsa gösterilebilir.
     */
    if ($currentrole === 'student') {

        if ($role === 'teacher') {
            $teachers[] = $data;
            continue;
        }

        if (
            $role === 'student' &&
            in_array($userid, $contactids, true)
        ) {
            $friends[] = $data;
            continue;
        }

        if (
            $role === 'admin' &&
            in_array($userid, $contactids, true)
        ) {
            $admins[] = $data;
        }
    }
}

$self = $DB->get_record(
    'user',
    ['id' => $currentuserid],
    'id,username,firstname,lastname,email,picture,imagealt',
    MUST_EXIST
);

$admins = wam_message_sort_users($admins);
$teachers = wam_message_sort_users($teachers);
$friends = wam_message_sort_users($friends);

$response = [
    'success' => true,

    'currentuser' => [
        'id' => (int)$self->id,
        'fullname' => fullname($self),
        'username' => $self->username,
        'role' => $currentrole,
    ],

    'counts' => [
        'admins' => count($admins),
        'teachers' => count($teachers),
        'friends' => count($friends),
    ],

    'admins' => $admins,
    'teachers' => $teachers,
    'friends' => $friends,
];

echo json_encode(
    $response,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES |
    JSON_PRETTY_PRINT
);

exit;
