<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — video yükleme / taslak / yayımlama yetkileri.

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    // Gizli taslak oluşturma, önizleme, yayımlama ve yayından kaldırma (kurs bağlamı).
    'local/wamakademi:managevideos' => [
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
];
