<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — video yükleme / taslak / yayımlama yetkileri.

defined('MOODLE_INTERNAL') || die();

$capabilities = [
// Gizli taslak oluşturma, önizleme, yayımlama ve yayından kaldırma (kurs bağlamı).
// academymanager arketip olmadığı için bu yetkiyi almaz. İş rolü classes/roles.php içindedir.
    'local/wamakademi:managevideos' => [
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    // Video ilerleme ve ödev durum raporları (kurs bağlamı).
    // academymanager arketip değildir; izin classes/roles.php ile verilir.
    'local/wamakademi:viewreports' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
];
