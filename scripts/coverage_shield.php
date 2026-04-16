<?php

declare(strict_types=1);

// scripts/coverage_shield.php <clover.xml> <output.json>
// Parses a PHPUnit clover report and writes a shields.io endpoint JSON
// describing the overall statement coverage percentage.

if ($argc < 3) {
    fwrite(STDERR, "Usage: coverage_shield.php <clover.xml> <output.json>\n");
    exit(1);
}

[$_, $cloverPath, $outPath] = $argv;

if (!is_file($cloverPath)) {
    fwrite(STDERR, "clover file not found: {$cloverPath}\n");
    exit(1);
}

$xml = simplexml_load_file($cloverPath);
if ($xml === false || !isset($xml->project->metrics)) {
    fwrite(STDERR, "invalid clover xml: missing project/metrics\n");
    exit(1);
}

$metrics = $xml->project->metrics;
$statements = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];
$percent = $statements > 0 ? ($covered / $statements) * 100.0 : 0.0;

$color = match (true) {
    $percent >= 90 => 'brightgreen',
    $percent >= 80 => 'green',
    $percent >= 70 => 'yellowgreen',
    $percent >= 60 => 'yellow',
    $percent >= 50 => 'orange',
    default        => 'red',
};

$payload = [
    'schemaVersion' => 1,
    'label'         => 'coverage',
    'message'       => number_format($percent, 1) . '%',
    'color'         => $color,
];

$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($json === false) {
    fwrite(STDERR, "json encode failed\n");
    exit(1);
}

if (file_put_contents($outPath, $json . "\n") === false) {
    fwrite(STDERR, "failed to write {$outPath}\n");
    exit(1);
}

fwrite(STDOUT, "coverage: {$percent}% ({$covered}/{$statements}) -> {$outPath}\n");
