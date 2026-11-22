<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi iş rollerini kurar veya eğitmen kayıt sınırını geri alır.
//
// Kullanım:
//   php local/wamakademi/cli/sync_roles.php
//   php local/wamakademi/cli/sync_roles.php --restore-editingteacher
//   php local/wamakademi/cli/sync_roles.php --delete-academy-role --confirm

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

list($options, $unrecognized) = cli_get_params(
    [
        'help' => false,
        'restore-editingteacher' => false,
        'delete-academy-role' => false,
        'confirm' => false,
    ],
    [
        'h' => 'help',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "WAM Akademi iş rollerini eşitler.

Seçenekler:
--restore-editingteacher   Eğitmenin kurs kaydı yetkilerini Moodle varsayılanına döndürür.
                           Sonraki eşitleme bu geri almayı yeniden kısıtlar.
--delete-academy-role      Akademi yöneticisi rolünü ve atamalarını siler. --confirm zorunludur.
--confirm                  Silmeyi onaylar.
-h, --help                 Bu yardımı gösterir.

Örnek:
php local/wamakademi/cli/sync_roles.php
";
    echo $help;
    exit(0);
}

if (!empty($options['delete-academy-role'])) {
    if (empty($options['confirm'])) {
        cli_error('Silme için --confirm gerekir. Rol atamaları da kalkar.');
    }
    $deleted = \local_wamakademi\roles::delete_academy_manager();
    cli_writeln($deleted ? 'Akademi yöneticisi rolü silindi.' : 'Rol zaten yoktu.');
    exit(0);
}

if (!empty($options['restore-editingteacher'])) {
    \local_wamakademi\roles::restore_editingteacher_enrolment();
    cli_writeln('Eğitmen kurs kayıt yetkileri Moodle varsayılanına döndü.');
    exit(0);
}

$roleid = \local_wamakademi\roles::sync();
cli_writeln('Akademi yöneticisi rolü eşitlendi. Rol kimliği: ' . $roleid);
cli_writeln('Atama bağlamı: kurs kategorisi (ve gerekirse kurs). Sistem bağlamına atamayın.');
