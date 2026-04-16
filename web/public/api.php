<?php

declare(strict_types=1);

use Cjuol\StatGuard\CentralTendencyEngine;
use Cjuol\StatGuard\ClassicStats;
use Cjuol\StatGuard\Exceptions\InvalidDataSetException;
use Cjuol\StatGuard\RobustStats;
use Cjuol\StatGuard\StatsComparator;

require __DIR__ . '/../../vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!is_array($payload) || !isset($payload['data']) || !is_array($payload['data'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Body must be JSON with a "data" array of numbers.']);
    exit;
}

const MAX_DATA_POINTS = 50000;

if (count($payload['data']) > MAX_DATA_POINTS) {
    http_response_code(413);
    echo json_encode([
        'error' => 'Data array exceeds the maximum of ' . MAX_DATA_POINTS . ' values.',
        'limit' => MAX_DATA_POINTS,
        'received' => count($payload['data']),
    ]);
    exit;
}

$data = array_values(array_filter(
    array_map(static fn($v) => is_numeric($v) ? (float) $v : null, $payload['data']),
    static fn($v) => $v !== null && is_finite($v)
));

$trimPercent = isset($payload['trimPercent']) && is_numeric($payload['trimPercent'])
    ? max(0.0, min(0.45, (float) $payload['trimPercent']))
    : 0.10;

$huberK = isset($payload['huberK']) && is_numeric($payload['huberK'])
    ? max(0.1, (float) $payload['huberK'])
    : CentralTendencyEngine::DEFAULT_HUBER_K;

try {
    $classic = new ClassicStats();
    $robust = new RobustStats();
    $comparator = new StatsComparator($robust, $classic);

    $classicSummary = $classic->getSummary($data);
    $robustSummary = $robust->getSummary($data);
    $comparison = $comparator->analyze($data);

    $huberMean = $robust->getHuberMean($data, $huberK);
    $trimmedMean = $robust->getTrimmedMean($data, $trimPercent);
    $winsorizedMean = $robust->getWinsorizedMean($data, $trimPercent);

    echo json_encode([
        'inputCount' => count($data),
        'classic' => $classicSummary,
        'robust' => $robustSummary,
        'centralTendency' => [
            'mean' => $classicSummary['mean'],
            'median' => $robustSummary['median'],
            'huberMean' => round($huberMean, 4),
            'trimmedMean' => round($trimmedMean, 4),
            'winsorizedMean' => round($winsorizedMean, 4),
        ],
        'comparison' => $comparison,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (InvalidDataSetException $e) {
    http_response_code(422);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal error: ' . $e->getMessage()]);
}
