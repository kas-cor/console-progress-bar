<?php

use KasCor\ConsoleProgressBar;

require_once __DIR__ . '/../vendor/autoload.php';

$progressBar = new ConsoleProgressBar(5, [
    'showFinishReport' => false,
]);

foreach (range(1, 5) as $current_position) {

    // Output without message
    foreach (range(1, 5) as $without_message) {
        $progressBar->output($current_position);
        sleep(1);
    }

    // Output without position
    foreach (range(1, 3) as $without_position) {
        $progressBar->output(null, 'message test - random number ' . random_int(1000, 9999));
        sleep(1);
    }

    // Output progress only
    $progressBar->output();
}

$progressBar->finishReport();
