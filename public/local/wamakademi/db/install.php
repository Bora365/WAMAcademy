<?php
// This file is part of Moodle - http://moodle.org/
//
// WAM Akademi — ilk kurulumda iş rollerini yerleştirir.

defined('MOODLE_INTERNAL') || die();

/**
 * Eklenti kurulunca akademi yöneticisi rolünü oluşturur ve eğitmen kayıt sınırını uygular.
 *
 * @return void
 */
function xmldb_local_wamakademi_install() {
    \local_wamakademi\roles::sync();
}
