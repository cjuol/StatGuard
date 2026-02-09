<?php

declare(strict_types=1);

namespace Cjuol\StatGuard;

use Cjuol\StatGuard\Contracts\StatsInterface;
use Cjuol\StatGuard\Traits\DataProcessorTrait;
use Cjuol\StatGuard\Traits\ExportableTrait;

/**
 * ClassicStats - Classical descriptive statistics library.
 * * Implements calculations based on mean and traditional standard deviation.
 * * Useful for bias comparisons against robust statistics.
 */
class ClassicStats implements StatsInterface
{
    use DataProcessorTrait;
    use ExportableTrait;

    // ========== PUBLIC METHODS - INTERFACE AND CONTRACTS ==========

    /**
     * Calculate the simple arithmetic mean.
     */
    public function getMean(array $data): float
    {
        return $this->calculateMean($this->prepareData($data, false));
    }

    /**
     * Calculate the median (sorting data first).
     */
    public function getMedian(array $data): float
    {
        return $this->calculateMedian($this->prepareData($data, true));
    }

    /**
     * Contract implementation: returns the sample standard deviation.
     */
    public function getDeviation(array $data): float
    {
        return $this->getStandardDeviation($data);
    }

    /**
     * Calculate the sample standard deviation (square root of sample variance).
     */
    public function getStandardDeviation(array $data): float
    {
        return sqrt($this->getSampleVariance($data));
    }

    /**
     * Contract implementation: returns the coefficient of variation (CV%).
     */
    public function getCoefficientOfVariation(array $data): float
    {
        $prepared = $this->prepareData($data, false);
        $mean = $this->calculateMean($prepared);

        if (abs($mean) < 1e-9) return 0.0;

        return ($this->getStandardDeviation($prepared) / abs($mean)) * 100;
    }

    /**
     * Calculate the sample variance (Bessel correction: divide by n-1).
     */
    public function getSampleVariance(array $data): float
    {
        return $this->calculateVariance($this->prepareData($data, false), true);
    }

    /**
     * Calculate the population variance (divide by n).
     */
    public function getPopulationVariance(array $data): float
    {
        return $this->calculateVariance($this->prepareData($data, false), false);
    }

    /**
     * Detect outliers using the traditional Z-Score method (|Z| > 3).
     */
    public function getOutliers(array $data): array
    {
        $prepared = $this->prepareData($data, false);
        $mean = $this->calculateMean($prepared);
        $stdDev = $this->getStandardDeviation($prepared);

        if ($stdDev < 1e-9) return [];

        return array_values(array_filter($prepared, function ($x) use ($mean, $stdDev) {
            return abs(($x - $mean) / $stdDev) > 3;
        }));
    }

    /**
     * Get a complete summary of classic metrics.
     */
    public function getSummary(array $data, bool $sort = true, int $decimals = 2): array
    {
        $prepared = $this->prepareData($data, $sort);

        return [
            'mean'           => round($this->calculateMean($prepared), $decimals),
            'median'         => round($this->calculateMedian($prepared), $decimals),
            'stdDev'         => round($this->getStandardDeviation($prepared), $decimals),
            'sampleVariance' => round($this->getSampleVariance($prepared), $decimals),
            // Use the safe method to avoid division by zero
            'cv'             => round($this->getCoefficientOfVariation($prepared), $decimals),
            'outliersZScore' => $this->getOutliers($prepared),
            'count'          => count($prepared)
        ];
    }

    // ========== PRIVATE METHODS - PURE CALCULATION ENGINE ==========

    private function calculateMean(array $data): float
    {
        return array_sum($data) / count($data);
    }

    private function calculateMedian(array $data): float
    {
        $n = count($data);
        $m = intdiv($n, 2);

        if ($n % 2 === 0) {
            return ($data[$m - 1] + $data[$m]) / 2.0;
        }
        return (float) $data[$m];
    }

    private function calculateVariance(array $data, bool $sample = true): float
    {
        $n = count($data);
        $mean = $this->calculateMean($data);

        $sumOfSquares = array_reduce($data, fn($acc, $x) => $acc + pow($x - $mean, 2), 0.0);

        $denominator = $sample ? ($n - 1) : $n;

        return $sumOfSquares / $denominator;
    }
}