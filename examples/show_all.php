<?php

foreach (glob(__DIR__ . '/*.php') as $item) {
    if ($item === __FILE__) {
        continue;
    }
    echo 'Example: ' . basename($item) . PHP_EOL;
    echo '--------------------------------------' . PHP_EOL;

    require_once $item;
}
