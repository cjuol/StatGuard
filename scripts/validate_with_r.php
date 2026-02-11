<?php

declare(strict_types=1);

use Cjuol\StatGuard\QuantileEngine;

require __DIR__ . '/../vendor/autoload.php';

$dataset = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
$probs = [0.25, 0.5, 0.75];
$epsilon = 1e-6;

$rscriptPath = trim((string) shell_exec('command -v Rscript'));
if ($rscriptPath === '') {
    echo "Rscript not found. Install R to run this validation.\n";
    exit(1);
}

/**
 * Run R quantile() for the requested type.
 *
 * Reference: Hyndman & Fan (1996) quantile definitions.
 */
function runRQuantiles(array $data, array $probs, int $type): array
{
    $dataStr = implode(',', $data);
    $probStr = implode(',', $probs);

    $script = 'data <- c(' . $dataStr . '); ' .
        'probs <- c(' . $probStr . '); ' .
        'res <- quantile(data, probs = probs, type = ' . $type . ', names = FALSE); ' .
        'cat(res, sep = ",")';

    $command = 'Rscript -e ' . escapeshellarg($script);
    $output = shell_exec($command);

    if ($output === null) {
        throw new RuntimeException('Rscript execution failed.');
    }

    $values = array_filter(explode(',', trim($output)), 'strlen');
    return array_map('floatval', $values);
}

$allOk = true;

for ($type = 1; $type <= 9; $type++) {
    $rValues = runRQuantiles($dataset, $probs, $type);
    $phpValues = array_map(
        fn(float $p) => QuantileEngine::calculate($dataset, $p, $type),
        $probs
    );

    $typeOk = true;
    foreach ($probs as $index => $p) {
        $diff = abs($phpValues[$index] - $rValues[$index]);
        if ($diff > $epsilon) {
            $typeOk = false;
            $allOk = false;
            echo 'Type ' . $type . ' mismatch at p=' . $p .
                ' (php=' . $phpValues[$index] . ', r=' . $rValues[$index] . ")\n";
        }
    }

    if ($typeOk) {
        echo 'Type ' . $type . " OK\n";
    }
}

exit($allOk ? 0 : 2);
