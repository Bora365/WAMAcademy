<?php
// This file is part of Moodle - http://moodle.org/
//
// Eski konum uyumluluğu: Content Workflow R1'den itibaren asıl sayfa
// /local/wamakademi/upload.php. Bu dosya yalnızca GET isteklerini yeni yola
// yönlendirir; POST işlemleri burada çalışmaz (form action yeni adrestedir).

require_once(__DIR__ . '/../../../config.php');

require_login();

$params = [];
foreach (['courseid', 'sectionnum', 'previewcmid'] as $key) {
    $value = optional_param($key, null, PARAM_INT);
    if ($value !== null && $value > 0) {
        $params[$key] = $value;
    }
}

$target = new moodle_url('/local/wamakademi/upload.php', $params);

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'HEAD') {
    // POST/PUT vb.: işlem yapılmaz; kullanıcıya yeni adresi bildir.
    throw new moodle_exception(
        'invalidrequest',
        'error',
        $target,
        null,
        'Video yükleme sayfası taşındı. Lütfen yeni adresi kullanın: /local/wamakademi/upload.php'
    );
}

redirect($target);
