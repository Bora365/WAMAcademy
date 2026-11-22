<?php
defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core_course\hook\after_form_definition::class,
        'callback' => \local_wamakademi\hook\course_form::class . '::after_definition',
    ],
    [
        'hook' => \core_course\hook\after_form_definition_after_data::class,
        'callback' => \local_wamakademi\hook\course_form::class . '::after_data',
    ],
    [
        'hook' => \core_course\hook\after_form_validation::class,
        'callback' => \local_wamakademi\hook\course_form::class . '::validate',
    ],
    [
        'hook' => \core\hook\navigation\secondary_extend::class,
        'callback' => \local_wamakademi\hook\secondary_nav::class . '::extend',
    ],
];
