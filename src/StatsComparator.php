<?php

declare(strict_types=1);

namespace Cjuol\StatGuard;

use Cjuol\StatGuard\Traits\DataProcessorTrait;

/**
 * StatsComparator - Comparative analysis service.
 * Compares classic statistics against robust statistics to detect bias and noise.
 */
class StatsComparator
{
    use DataProcessorTrait;

    /**
     * The $robust/$classic parameters are retained for backwards compatibility
     * but are no longer used: analyze() computes directly against QuantileEngine
     * and the prepared dataset to avoid redundant validation and sorting passes.
     *
     * @phpstan-ignore constructor.unusedParameter, constructor.unusedParameter
     */
    public function __construct(?RobustStats $robust = null, ?ClassicStats $classic = null)
    {
    }

    /**
     * Compare metrics and return a data fidelity report.
     */
    public function analyze(array $data, int $decimals = 2): array
    {
        $prepared = $this->prepareData($data, true);
        $n = count($prepared);

        $mean = array_sum($prepared) / $n;
        $median = QuantileEngine::medianSorted($prepared);

        $sumSq = 0.0;
        foreach ($prepared as $v) {
            $sumSq += ($v - $mean) ** 2;
        }
        $stdDev = sqrt($sumSq / ($n - 1));

        $diffs = array_map(static fn($x) => abs($x - $median), $prepared);
        sort($diffs, SORT_NUMERIC);
        $mad = QuantileEngine::medianSorted($diffs);
        // getDeviation() uses MAD * 1.4826 for a fair comparison
        $robustDeviation = $mad * 1.4826;

        // 1. Bias between mean and median
        // Use a safety threshold (1e-9) instead of != 0
        // Formula: $$Bias = \frac{\text{mean} - \text{median}}{|\text{median}|} \times 100$$
        $bias = (abs($median) > 1e-9) ? (($mean - $median) / abs($median)) * 100 : 0.0;

        // 2. Dispersion ratio
        // Formula: $$Ratio = \frac{\sigma_{\text{classic}}}{\sigma_{\text{robust}}}$$
        if (abs($robustDeviation) > 1e-9) {
            $dispersionRatio = $stdDev / $robustDeviation;
        } else {
            // If robust is 0 but classic is not, there is extreme noise (outliers)
            $dispersionRatio = (abs($stdDev) > 1e-9) ? 2.0 : 1.0; 
        }

        $q1 = QuantileEngine::calculateSorted($prepared, 0.25, RobustStats::TYPE_R_DEFAULT);
        $q3 = QuantileEngine::calculateSorted($prepared, 0.75, RobustStats::TYPE_R_DEFAULT);
        $iqr = $q3 - $q1;
        $lowerFence = $q1 - 1.5 * $iqr;
        $upperFence = $q3 + 1.5 * $iqr;

        $tukeyCount = 0;
        $zCount = 0;
        foreach ($prepared as $v) {
            if ($v < $lowerFence || $v > $upperFence) {
                $tukeyCount++;
            }
            if ($stdDev > 1e-9 && abs(($v - $mean) / $stdDev) > 3) {
                $zCount++;
            }
        }

        return [
            'centralComparison' => [
                'classicMean' => round($mean, $decimals),
                'robustMedian' => round($median, $decimals),
                'absoluteDifference' => round(abs($mean - $median), $decimals),
                'biasPercent' => round($bias, $decimals) . '%',
            ],
            'dispersionComparison' => [
                'stdDev' => round($stdDev, $decimals),
                'robustDeviation' => round($robustDeviation, $decimals),
                'noiseRatio' => round($dispersionRatio, $decimals),
            ],
            'outlierDetection' => [
                'tukeyMethod' => $tukeyCount,
                'zScoreMethod' => $zCount,
            ],
            'verdict' => $this->generateVerdict($bias, $dispersionRatio)
        ];
    }

    /**
     * Generate a human-readable conclusion based on the data.
     */
    private function generateVerdict(float $bias, float $ratio): string
    {
        // Thresholds based on statistical experimentation
        if (abs($bias) > 10 || $ratio > 1.5) {
            return 'ALERT: Data is highly influenced by outliers. Use robust metrics.';
        }

        if (abs($bias) > 5 || $ratio > 1.2) {
            return 'CAUTION: There is moderate bias. Compare both metrics before deciding.';
        }

        return 'STABLE: Data follows a clean distribution. Classic statistics are reliable.';
    }
}