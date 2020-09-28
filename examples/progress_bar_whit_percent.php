<?php

use KasCor\ConsoleProgressBar;

require_once __DIR__ . '/../vendor/autoload.php';

$progressBar = new ConsoleProgressBar(5, [
    'showCurrentPosition' => false,
    'showSpinner' => false,
    'showPassedTime' => false,
    'showEstimatedTime' => false,
]);

foreach (range(1, 5) as $current_position) {
    $progressBar->output($current_position, 'message test - random number ' . random_int(1000, 9999));
    sleep(1);
}
