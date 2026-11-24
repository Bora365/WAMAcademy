<?php
// This file is part of Moodle - http://moodle.org/
//
// CLI: yerel mock eğitim verisi.
// php public/local/wamakademi/cli/setup_mock_data.php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

list($options, $unrecognized) = cli_get_params(['help' => false], ['h' => 'help']);

if ($unrecognized) {
    cli_error(get_string('cliunknowoption', 'admin', implode("\n  ", $unrecognized)));
}

if (!empty($options['help'])) {
    echo "Yerel mock eğitim / ilerleme / değerlendirme verisi kurar.\n";
    exit(0);
}

$guard = \local_wamakademi\demo_env::assert_safe();
if ($guard !== true) {
    cli_error('Demo kilidi başarısız: ' . $guard);
}

raise_memory_limit(MEMORY_EXTRA);

try {
    $lines = \local_wamakademi\mock_data::run();
    foreach ($lines as $line) {
        cli_writeln($line);
    }
    cli_writeln('Tamam.');
    exit(0);
} catch (Throwable $e) {
    cli_error($e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine());
}
