<?php

declare(strict_types=1);

use Cjuol\StatGuard\QuantileEngine;
use Cjuol\StatGuard\RobustStats;
use MathPHP\Statistics\Average;
use MathPHP\Statistics\Descriptive;

require __DIR__ . '/../vendor/autoload.php';

/**
 * CONFIGURACIÓN DEL BENCHMARK
 */
$sizes = [1000, 10000, 100000];
$quantileP = 0.75;
$quantileTypes = range(1, 9);
$format = $argv[1] ?? 'table';

mt_srand(42);

// --- 1. HELPERS DE DATOS Y SISTEMA ---

/** Genera un array de floats aleatorios */
function generateDataset(int $size): array {
    $data = [];
    for ($i = 0; $i < $size; $i++) {
        $data[] = mt_rand(0, 1000000) / 1000;
    }
    return $data;
}

/** Limpia ciclos de memoria antes de cada test para asegurar mediciones limpias */
function resetMemory(): void {
    gc_collect_cycles();
}

// --- 2. ALGORITMOS DE REFERENCIA (MANUALES) ---

function manualMedian(array $data): float {
    sort($data, SORT_NUMERIC);
    $n = count($data);
    $mid = intdiv($n, 2);
    return ($n % 2 === 0) 
        ? ((float)$data[$mid - 1] + (float)$data[$mid]) / 2.0 
        : (float)$data[$mid];
}

function manualQuantileType7(array $data, float $p): float {
    sort($data, SORT_NUMERIC);
    $n = count($data);
    $p = max(0.0, min(1.0, $p));
    $h = 1.0 + ($n - 1.0) * $p;
    $k = (int) floor($h);
    $d = $h - $k;
    
    if ($k <= 1) return (float)$data[0];
    if ($k >= $n) return (float)$data[$n - 1];
    
    return (float)$data[$k - 1] + $d * ((float)$data[$k] - (float)$data[$k - 1]);
}

// --- 3. INTEGRACIÓN CON R ---

/** Ejecuta el benchmark en R y devuelve los tiempos y valores de referencia */
function runRBenchmark(array $data): array {
    $tmpFile = '/tmp/bench.csv';
    file_put_contents($tmpFile, implode("\n", $data));

    $command = 'Rscript tests/r_performance.R ' . escapeshellarg($tmpFile);
    $output = shell_exec($command);
    @unlink($tmpFile);

    if (!$output || !preg_match('/\{.*\}/s', $output, $matches)) {
        throw new RuntimeException("Error ejecutando R o salida JSON no encontrada.");
    }

    return json_decode($matches[0], true);
}

// --- 4. MOTOR DEL BENCHMARK ---

/** Mide el rendimiento de una función callable */
function measure(string $label, callable $fn): array {
    resetMemory();
    $peakBefore = memory_get_peak_usage(true);
    
    $start = hrtime(true);
    $result = $fn();
    $end = hrtime(true);
    
    $peakAfter = memory_get_peak_usage(true);

    return [
        'label'  => $label,
        'ms'     => ($end - $start) / 1e6,
        'kb'     => max(0.0, ($peakAfter - $peakBefore) / 1024.0),
        'r_ms'   => null,
        'ratio'  => null,
        'value'  => $result // Guardamos el valor para verificar precisión
    ];
}

function buildShieldData(array $results): array {
    $statGuardLabel = 'median: StatGuard (100000)';
    $mathPhpLabel = 'median: MathPHP (100000)';
    $statGuardMs = null;
    $mathPhpMs = null;

    foreach ($results as $result) {
        if ($result['label'] === $statGuardLabel) {
            $statGuardMs = $result['ms'];
        }
        if ($result['label'] === $mathPhpLabel) {
            $mathPhpMs = $result['ms'];
        }
    }

    $message = 'n/a';
    if ($statGuardMs !== null && $mathPhpMs !== null && $statGuardMs > 0) {
        $ratio = round($mathPhpMs / $statGuardMs, 1);
        $message = $ratio . 'x faster than MathPHP';
    }

    return [
        'schemaVersion' => 1,
        'label' => 'performance',
        'message' => $message,
        'color' => 'brightgreen'
    ];
}

function recordSummary(array &$summary, int $size, string $method, string $impl, ?float $ms, ?float $value): void {
    if (!isset($summary[$size])) {
        $summary[$size] = [];
    }
    if (!isset($summary[$size][$method])) {
        $summary[$size][$method] = [];
    }
    $summary[$size][$method][$impl] = [
        'ms' => $ms,
        'value' => $value
    ];
}

function formatMs(?float $ms): string {
    return $ms === null ? 'n/a' : sprintf('%.2f', $ms);
}

function formatValue(?float $value): string {
    if ($value === null) {
        return 'n/a';
    }
    $formatted = sprintf('%.6f', $value);
    return rtrim(rtrim($formatted, '0'), '.');
}

function buildMarkdownTable(array $summaryForSize, array $methodOrder, array $methodLabels): string {
    $lines = [];
    $lines[] = '| Method | StatGuard ms | StatGuard value | MathPHP ms | MathPHP value | R ms | R value |';
    $lines[] = '| :--- | ---: | ---: | ---: | ---: | ---: | ---: |';

    foreach ($methodOrder as $methodKey) {
        $label = $methodLabels[$methodKey] ?? $methodKey;
        $stat = $summaryForSize[$methodKey]['statguard'] ?? ['ms' => null, 'value' => null];
        $math = $summaryForSize[$methodKey]['mathphp'] ?? ['ms' => null, 'value' => null];
        $r = $summaryForSize[$methodKey]['r'] ?? ['ms' => null, 'value' => null];

        $lines[] = sprintf(
            '| %s | %s | %s | %s | %s | %s | %s |',
            $label,
            formatMs($stat['ms']),
            formatValue($stat['value']),
            formatMs($math['ms']),
            formatValue($math['value']),
            formatMs($r['ms']),
            formatValue($r['value'])
        );
    }

    return implode("\n", $lines);
}

// --- 5. EJECUCIÓN DEL FLUJO PRINCIPAL ---

$stats = new RobustStats();
$results = [];
$precisionWarnings = [];
$summary = [];

$methodLabels = [
    'median' => 'Median',
    'huber_mean' => 'Huber mean',
    'trimmed_mean_10' => 'Trimmed mean (10%)',
    'winsorized_mean_10' => 'Winsorized mean (10%)'
];

$methodOrder = ['median'];
foreach ($quantileTypes as $type) {
    $methodKey = "quantile_t{$type}";
    $methodLabels[$methodKey] = "Quantile Type {$type} (p={$quantileP})";
    $methodOrder[] = $methodKey;
}
$methodOrder = array_merge($methodOrder, ['huber_mean', 'trimmed_mean_10', 'winsorized_mean_10']);

foreach ($sizes as $size) {
    $data = generateDataset($size);
    
    // Obtenemos los tiempos de R para este tamaño de dataset
    try {
        $rBench = runRBenchmark($data);
    } catch (Exception $e) {
        $rBench = [];
    }

    /** SECCIÓN: MEDIANA */
    $resMedian = measure("median: StatGuard ($size)", fn() => $stats->getMedian($data));
    $resMedian['r_ms'] = $rBench['median_ms'] ?? null;

    $results[] = $resMedian;
    $results[] = measure("median: manual sort ($size)", fn() => manualMedian($data));

    $resMedianMath = measure("median: MathPHP ($size)", fn() => Average::median($data));
    $resMedianMath['r_ms'] = $rBench['median_ms'] ?? null;
    $results[] = $resMedianMath;

    recordSummary($summary, $size, 'median', 'statguard', $resMedian['ms'], $resMedian['value']);
    recordSummary($summary, $size, 'median', 'mathphp', $resMedianMath['ms'], $resMedianMath['value']);
    recordSummary($summary, $size, 'median', 'r', $rBench['median_ms'] ?? null, $rBench['median'] ?? null);

    /** SECCIÓN: CUANTILES */
    foreach ($quantileTypes as $type) {
        $methodKey = "quantile_t{$type}";
        $resQ = measure(
            "quantile t{$type}: StatGuard p={$quantileP} ($size)",
            fn() => QuantileEngine::calculate($data, $quantileP, $type)
        );
        $resQ['r_ms'] = $rBench["quantile_t{$type}_ms"] ?? null;

        $results[] = $resQ;
        if ($type === 7) {
            $results[] = measure(
                "quantile t{$type}: manual p={$quantileP} ($size)",
                fn() => manualQuantileType7($data, $quantileP)
            );
        }

        recordSummary($summary, $size, $methodKey, 'statguard', $resQ['ms'], $resQ['value']);
        recordSummary(
            $summary,
            $size,
            $methodKey,
            'r',
            $rBench["quantile_t{$type}_ms"] ?? null,
            $rBench["quantile_t{$type}_value"] ?? null
        );

        if ($type === 7) {
            $resQMath = measure(
                "quantile t{$type}: MathPHP p={$quantileP} ($size)",
                fn() => Descriptive::percentile($data, $quantileP * 100)
            );
            $resQMath['r_ms'] = $rBench["quantile_t{$type}_ms"] ?? null;
            $results[] = $resQMath;
            recordSummary($summary, $size, $methodKey, 'mathphp', $resQMath['ms'], $resQMath['value']);
        } else {
            recordSummary($summary, $size, $methodKey, 'mathphp', null, null);
        }
    }

    /** SECCIÓN: MEDIAS ROBUSTAS (HUBER) */
    $resHuber = measure("mean: Huber StatGuard ($size)", fn() => $stats->getHuberMean($data));
    $resHuber['r_ms'] = $rBench['huber_ms'] ?? null;

    $results[] = measure("mean: arithmetic ($size)", fn() => array_sum($data) / count($data));
    $results[] = $resHuber;

    recordSummary($summary, $size, 'huber_mean', 'statguard', $resHuber['ms'], $resHuber['value']);
    recordSummary($summary, $size, 'huber_mean', 'mathphp', null, null);
    recordSummary($summary, $size, 'huber_mean', 'r', $rBench['huber_ms'] ?? null, $rBench['huber_mu'] ?? null);

    /** SECCIÓN: TRIMMED MEAN (10%) */
    $resTrimmed = measure("mean: Trimmed StatGuard 10% ($size)", fn() => $stats->getTrimmedMean($data, 0.1));
    $resTrimmed['r_ms'] = $rBench['trimmed_ms'] ?? null;
    $results[] = $resTrimmed;

    $resTrimmedMath = measure("mean: Trimmed MathPHP 10% ($size)", fn() => Average::truncatedMean($data, 10));
    $resTrimmedMath['r_ms'] = $rBench['trimmed_ms'] ?? null;
    $results[] = $resTrimmedMath;

    recordSummary($summary, $size, 'trimmed_mean_10', 'statguard', $resTrimmed['ms'], $resTrimmed['value']);
    recordSummary($summary, $size, 'trimmed_mean_10', 'mathphp', $resTrimmedMath['ms'], $resTrimmedMath['value']);
    recordSummary($summary, $size, 'trimmed_mean_10', 'r', $rBench['trimmed_ms'] ?? null, $rBench['trimmed_mean'] ?? null);

    /** SECCIÓN: WINSORIZED MEAN (10%) */
    $resWinsor = measure(
        "mean: Winsorized StatGuard 10% ($size)",
        fn() => $stats->getWinsorizedMean($data, 0.1, 7)
    );
    $resWinsor['r_ms'] = $rBench['winsorized_ms'] ?? null;
    $results[] = $resWinsor;

    recordSummary($summary, $size, 'winsorized_mean_10', 'statguard', $resWinsor['ms'], $resWinsor['value']);
    recordSummary($summary, $size, 'winsorized_mean_10', 'mathphp', null, null);
    recordSummary(
        $summary,
        $size,
        'winsorized_mean_10',
        'r',
        $rBench['winsorized_ms'] ?? null,
        $rBench['winsorized_mean'] ?? null
    );

    // Verificación de precisión contra R
    if (isset($rBench['huber_mu'])) {
        $diff = abs($resHuber['value'] - (float)$rBench['huber_mu']);
        if ($diff > 1e-10) {
            $precisionWarnings[] = "Huber Accuracy Warning ($size): Δ $diff";
        }
    }
}

// --- 6. RENDERIZADO DE RESULTADOS ---

if ($format === 'json') {
    $shieldData = buildShieldData($results);
    file_put_contents(
        'statguard-perf.json',
        json_encode($shieldData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    $markdown = buildMarkdownTable($summary[100000] ?? [], $methodOrder, $methodLabels);
    echo json_encode(
        ['benchmarks' => $results, 'warnings' => $precisionWarnings, 'markdown' => $markdown],
        JSON_PRETTY_PRINT
    );
    exit;
}

if ($format === 'markdown') {
    echo buildMarkdownTable($summary[100000] ?? [], $methodOrder, $methodLabels) . "\n";
    exit;
}

// Formato Tabla
echo str_pad("BENCHMARK", 45) . " | " . str_pad("ms", 10) . " | " . str_pad("KB", 10) . " | " . str_pad("R (ms)", 10) . " | Ratio (PHP/R)\n";
echo str_repeat("-", 95) . "\n";

foreach ($results as $r) {
    $ratio = ($r['r_ms'] > 0) ? sprintf("%.2fx", $r['ms'] / $r['r_ms']) : "-";
    $r_time = ($r['r_ms'] !== null) ? sprintf("%.3f", $r['r_ms']) : "-";
    
    printf(
        "%-45s | %10.3f | %10.2f | %10s | %10s\n",
        $r['label'], $r['ms'], $r['kb'], $r_time, $ratio
    );
}

foreach ($precisionWarnings as $w) echo "⚠️  $w\n";

echo "\nMARKDOWN SUMMARY (100000)\n";
echo buildMarkdownTable($summary[100000] ?? [], $methodOrder, $methodLabels) . "\n";