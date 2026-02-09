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

    private RobustStats $robust;
    private ClassicStats $classic;

    public function __construct(?RobustStats $robust = null, ?ClassicStats $classic = null)
    {
        $this->robust = $robust ?? new RobustStats();
        $this->classic = $classic ?? new ClassicStats();
    }

    /**
     * Compare metrics and return a data fidelity report.
     */
    public function analyze(array $data, int $decimals = 2): array
    {
        $prepared = $this->prepareData($data, true);

        $mean = $this->classic->getMean($prepared);
        $median = $this->robust->getMedian($prepared);
        $stdDev = $this->classic->getStandardDeviation($prepared);
        // getDeviation() uses MAD * 1.4826 for a fair comparison
        $robustDeviation = $this->robust->getDeviation($prepared);

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
                'tukeyMethod' => count($this->robust->getOutliers($prepared)),
                'zScoreMethod' => count($this->classic->getOutliers($prepared)),
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