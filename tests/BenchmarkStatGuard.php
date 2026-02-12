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
$parityTolerance = 0.0001;

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

function huberMeanMathPhp(array $data, float $k = 1.345, int $maxIterations = 50, float $tolerance = 0.001): float {
    $n = count($data);
    if ($n === 0) {
        throw new RuntimeException('Cannot compute Huber mean for empty dataset.');
    }

    $mu = Average::mean($data);
    for ($i = 0; $i < $maxIterations; $i++) {
        $weightedSum = 0.0;
        $weightTotal = 0.0;
        foreach ($data as $value) {
            $diff = $value - $mu;
            $absDiff = abs($diff);
            $weight = $absDiff <= $k ? 1.0 : $k / $absDiff;
            $weightedSum += $value * $weight;
            $weightTotal += $weight;
        }

        $nextMu = $weightTotal > 0 ? $weightedSum / $weightTotal : $mu;
        if (abs($nextMu - $mu) < $tolerance) {
            $mu = $nextMu;
            break;
        }
        $mu = $nextMu;
    }

    return $mu;
}

// --- 3. INTEGRACIÓN CON R ---

/** Ejecuta el benchmark en R y devuelve los tiempos y valores de referencia */
function runRBenchmark(array $data): array {
    $tmpFile = '/tmp/bench.csv';
    file_put_contents($tmpFile, implode("\n", $data));

    $command = 'Rscript tests/r_performance.R ' . escapeshellarg($tmpFile) . ' 2>&1';
    $output = shell_exec($command);
    @unlink($tmpFile);

    if (!$output || !preg_match('/\{.*\}/s', $output, $matches)) {
        $details = $output ? trim($output) : 'sin salida';
        throw new RuntimeException("Error ejecutando R o salida JSON no encontrada: {$details}");
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

function buildMarkdownTable(array $summaryForSize, array $methodOrder, array $methodLabels, float $tolerance): string {
    $lines = [];
    $lines[] = '| Method | StatGuard ms | StatGuard value | MathPHP ms | MathPHP value | R ms | R value | Status |';
    $lines[] = '| :--- | ---: | ---: | ---: | ---: | ---: | ---: | :---: |';

    foreach ($methodOrder as $methodKey) {
        $label = $methodLabels[$methodKey] ?? $methodKey;
        $stat = $summaryForSize[$methodKey]['statguard'] ?? ['ms' => null, 'value' => null];
        $math = $summaryForSize[$methodKey]['mathphp'] ?? ['ms' => null, 'value' => null];
        $r = $summaryForSize[$methodKey]['r'] ?? ['ms' => null, 'value' => null];

        $status = '❌';
        if ($stat['value'] !== null && $r['value'] !== null) {
            $status = abs($stat['value'] - $r['value']) < $tolerance ? '✅' : '❌';
        }

        $lines[] = sprintf(
            '| %s | %s | %s | %s | %s | %s | %s | %s |',
            $label,
            formatMs($stat['ms']),
            formatValue($stat['value']),
            formatMs($math['ms']),
            formatValue($math['value']),
            formatMs($r['ms']),
            formatValue($r['value']),
            $status
        );
    }

    return implode("\n", $lines);
}

function updateMarkdownSection(string $filePath, string $startMarker, string $endMarker, string $table): void {
    $contents = file_get_contents($filePath);
    if ($contents === false) {
        throw new RuntimeException("Unable to read {$filePath}.");
    }

    $startPos = strpos($contents, $startMarker);
    $endPos = strpos($contents, $endMarker);
    if ($startPos === false || $endPos === false || $endPos <= $startPos) {
        throw new RuntimeException("Markers not found in {$filePath}.");
    }

    $before = substr($contents, 0, $startPos + strlen($startMarker));
    $after = substr($contents, $endPos);

    $updated = $before . "\n\n" . $table . "\n\n" . $after;
    file_put_contents($filePath, $updated);
}

function replaceMarkdownPlaceholders(string $filePath, array $replacements): void {
    $contents = file_get_contents($filePath);
    if ($contents === false) {
        throw new RuntimeException("Unable to read {$filePath}.");
    }

    $updated = strtr($contents, $replacements);
    file_put_contents($filePath, $updated);
}

function buildPlaceholderReplacements(array $summaryForSize, array $methodOrder, float $tolerance): array {
    $replacements = [];

    foreach ($methodOrder as $methodKey) {
        $stat = $summaryForSize[$methodKey]['statguard'] ?? ['ms' => null, 'value' => null];
        $math = $summaryForSize[$methodKey]['mathphp'] ?? ['ms' => null, 'value' => null];
        $r = $summaryForSize[$methodKey]['r'] ?? ['ms' => null, 'value' => null];

        $status = '❌';
        if ($stat['value'] !== null && $r['value'] !== null) {
            $status = abs($stat['value'] - $r['value']) < $tolerance ? '✅' : '❌';
        }

        $prefix = $methodKey;
        $replacements["{{{$prefix}_statguard_ms}}"] = formatMs($stat['ms']);
        $replacements["{{{$prefix}_statguard_value}}"] = formatValue($stat['value']);
        $replacements["{{{$prefix}_mathphp_ms}}"] = formatMs($math['ms']);
        $replacements["{{{$prefix}_mathphp_value}}"] = formatValue($math['value']);
        $replacements["{{{$prefix}_r_ms}}"] = formatMs($r['ms']);
        $replacements["{{{$prefix}_r_value}}"] = formatValue($r['value']);
        $replacements["{{{$prefix}_status}}"] = $status;
    }

    return $replacements;
}

// --- 5. EJECUCIÓN DEL FLUJO PRINCIPAL ---

$stats = new RobustStats();
$results = [];
$summary = [];

$methodLabels = [
    'median' => 'Median',
    'huber_mean' => 'Huber mean'
];

$methodOrder = ['median'];
foreach ($quantileTypes as $type) {
    $methodKey = "quantile_t{$type}";
    $methodLabels[$methodKey] = "Quantile Type {$type} (p={$quantileP})";
    $methodOrder[] = $methodKey;
}
$methodOrder[] = 'huber_mean';

$methods = [
    'median' => [
        'label' => 'Median',
        'statguard' => fn(array $data) => $stats->getMedian($data),
        'mathphp' => fn(array $data) => Average::median($data),
        'stat_label' => fn(int $size) => "median: StatGuard ({$size})",
        'math_label' => fn(int $size) => "median: MathPHP ({$size})",
        'r_ms_key' => 'median_ms',
        'r_value_key' => 'median'
    ],
    'huber_mean' => [
        'label' => 'Huber mean',
        'statguard' => fn(array $data) => $stats->getHuberMean($data),
        'mathphp' => fn(array $data) => huberMeanMathPhp($data),
        'stat_label' => fn(int $size) => "mean: Huber StatGuard ({$size})",
        'math_label' => fn(int $size) => "mean: Huber MathPHP ({$size})",
        'r_ms_key' => 'huber_ms',
        'r_value_key' => 'huber_mu'
    ]
];

foreach ($quantileTypes as $type) {
    $methodKey = "quantile_t{$type}";
    $methods[$methodKey] = [
        'label' => "Quantile Type {$type} (p={$quantileP})",
        'statguard' => fn(array $data) => QuantileEngine::calculate($data, $quantileP, $type),
        'mathphp' => fn(array $data) => Descriptive::percentile($data, $quantileP * 100),
        'stat_label' => fn(int $size) => "quantile t{$type}: StatGuard p={$quantileP} ({$size})",
        'math_label' => fn(int $size) => "quantile t{$type}: MathPHP p={$quantileP} ({$size})",
        'r_ms_key' => "quantile_t{$type}_ms",
        'r_value_key' => "quantile_t{$type}_value"
    ];
}

foreach ($sizes as $size) {
    $data = generateDataset($size);
    
    // Obtenemos los tiempos de R para este tamaño de dataset
    try {
        $rBench = runRBenchmark($data);
    } catch (Exception $e) {
        fwrite(STDERR, "R benchmark fallo: {$e->getMessage()}\n");
        $rBench = [];
    }

    foreach ($methodOrder as $methodKey) {
        $method = $methods[$methodKey];
        $statLabel = $method['stat_label']($size);
        $mathLabel = $method['math_label']($size);

        $statResult = measure($statLabel, fn() => $method['statguard']($data));
        $statResult['r_ms'] = $rBench[$method['r_ms_key']] ?? null;
        $results[] = $statResult;

        $mathResult = measure($mathLabel, fn() => $method['mathphp']($data));
        $mathResult['r_ms'] = $rBench[$method['r_ms_key']] ?? null;
        $results[] = $mathResult;

        recordSummary($summary, $size, $methodKey, 'statguard', $statResult['ms'], $statResult['value']);
        recordSummary($summary, $size, $methodKey, 'mathphp', $mathResult['ms'], $mathResult['value']);
        recordSummary(
            $summary,
            $size,
            $methodKey,
            'r',
            $rBench[$method['r_ms_key']] ?? null,
            $rBench[$method['r_value_key']] ?? null
        );
    }
}

// --- 6. RENDERIZADO DE RESULTADOS ---

if ($format === 'json' || $format === 'report') {
    $shieldData = buildShieldData($results);
    file_put_contents(
        'statguard-perf.json',
        json_encode($shieldData, JSON_UNESCAPED_SLASHES)
    );

    $tableSize = 100000;
    $summaryForTable = $summary[$tableSize] ?? [];
    $markdown = buildMarkdownTable($summaryForTable, $methodOrder, $methodLabels, $parityTolerance);
    $report = [
        'generated_at' => date('c'),
        'sizes' => $sizes,
        'quantile_p' => $quantileP,
        'tolerance' => $parityTolerance,
        'method_order' => $methodOrder,
        'method_labels' => $methodLabels,
        'benchmarks' => $results,
        'summary' => $summary,
        'table_size' => $tableSize,
        'table_markdown' => $markdown
    ];

    if ($format === 'report') {
        $benchmarksPaths = [
            __DIR__ . '/../docs/benchmarks.md',
            __DIR__ . '/../docs/benchmarks.es.md'
        ];

        foreach ($benchmarksPaths as $benchmarksPath) {
            if (file_exists($benchmarksPath)) {
                updateMarkdownSection(
                    $benchmarksPath,
                    '<!-- BENCHMARK_PARITY_START -->',
                    '<!-- BENCHMARK_PARITY_END -->',
                    $markdown
                );
            }
        }
    }

    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($format === 'markdown') {
    echo buildMarkdownTable($summary[100000] ?? [], $methodOrder, $methodLabels, $parityTolerance) . "\n";
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

echo "\nMARKDOWN SUMMARY (100000)\n";
echo buildMarkdownTable($summary[100000] ?? [], $methodOrder, $methodLabels, $parityTolerance) . "\n";