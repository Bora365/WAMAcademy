<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\course_created',
        'callback' => '\local_wamakademi\observer::course_created',
    ],
    [
        'eventname' => '\core\event\course_deleted',
        'callback' => '\local_wamakademi\observer::course_deleted',
    ],
];
