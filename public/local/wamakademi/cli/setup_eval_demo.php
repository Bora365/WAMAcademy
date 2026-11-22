<?php
// This file is part of Moodle - http://moodle.org/
//
// CLI: değerlendirme demosu kurulumu + doğrulama.
// php public/local/wamakademi/cli/setup_eval_demo.php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

list($options, $unrecognized) = cli_get_params(
    ['help' => false, 'skip-verify' => false],
    ['h' => 'help']
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if (!empty($options['help'])) {
    echo "Kurulum: demo kilidi + Excel kursu değerlendirme demosu\n";
    echo "  --skip-verify  Doğrulama kullanıcılarını atla\n";
    exit(0);
}

$guard = \local_wamakademi\demo_env::assert_safe();
if ($guard !== true) {
    cli_error('Demo kilidi başarısız: ' . $guard);
}

raise_memory_limit(MEMORY_EXTRA);

try {
    $result = \local_wamakademi\demo_setup::run();
    foreach ($result['messages'] as $line) {
        cli_writeln($line);
    }

    if (empty($options['skip-verify'])) {
        cli_writeln('--- Doğrulama ---');
        $verify = \local_wamakademi\demo_setup::verify_and_cleanup(
            $result['course'],
            $result['quiz'],
            $result['assign']
        );
        foreach ($verify as $line) {
            cli_writeln($line);
        }
    }

    cli_writeln('Tamam.');
    exit(0);
} catch (Throwable $e) {
    cli_error($e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine());
}
