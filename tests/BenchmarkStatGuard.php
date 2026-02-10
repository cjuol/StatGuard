<?php

declare(strict_types=1);

use Cjuol\StatGuard\RobustStats;

require __DIR__ . '/../vendor/autoload.php';

$size = 10000;
$data = [];

for ($i = 0; $i < $size; $i++) {
    $data[] = sin($i / 10) * 100 + (($i % 23) - 11);
}

$stats = new RobustStats();

$start = microtime(true);
$mean = $stats->getHuberMean($data);
$elapsed = microtime(true) - $start;

echo 'Huber mean: ' . $mean . PHP_EOL;
echo 'Elapsed seconds: ' . $elapsed . PHP_EOL;
