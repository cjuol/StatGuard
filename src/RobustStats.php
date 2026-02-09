<?php

declare(strict_types=1);

namespace Cjuol\StatGuard;

use Cjuol\StatGuard\Contracts\StatsInterface;
use Cjuol\StatGuard\Traits\DataProcessorTrait;
use Cjuol\StatGuard\Traits\ExportableTrait;

class RobustStats implements StatsInterface
{
    use DataProcessorTrait;
    use ExportableTrait;

    // ========== INTERFACE (For the Comparator) ==========

    public function getMean(array $data): float
    {
        return $this->calculateMean($this->prepareData($data, false));
    }

    public function getMedian(array $data): float
    {
        return $this->calculateMedian($this->prepareData($data, true));
    }

    public function getDeviation(array $data): float
    {
        // Use scaled MAD so the noise ratio is comparable to 1.0
        return $this->getMad($data) * 1.4826;
    }

    public function getCoefficientOfVariation(array $data): float
    {
        // For interface consistency, use the scaled deviation
        $prepared = $this->prepareData($data, true);
        $median = $this->calculateMedian($prepared);
        if (abs($median) < 1e-9) return 0.0;
        return ($this->getDeviation($prepared) / abs($median)) * 100;
    }

    // ========== SPECIFIC METHODS (For the S* tests) ==========

    public function getRobustDeviation(array $data): float
    {
        return $this->calculateRobustDeviation($this->prepareData($data, true));
    }

    public function getRobustCv(array $data): float
    {
        // Tests expect CV based on S*
        return $this->calculateRobustCv($this->prepareData($data, true));
    }

    public function getRobustVariance(array $data): float
    {
        $prepared = $this->prepareData($data, true);
        return pow($this->calculateRobustDeviation($prepared), 2);
    }

    public function getIqr(array $data): float
    {
        return $this->calculateIqr($this->prepareData($data, true));
    }

    public function getMad(array $data): float
    {
        return $this->calculateMad($this->prepareData($data, true));
    }

    public function getOutliers(array $data): array
    {
        return $this->detectOutliers($this->prepareData($data, true));
    }

    public function getConfidenceIntervals(array $data): array
    {
        return $this->calculateConfidenceIntervals($this->prepareData($data, true));
    }

    public function getSummary(array $data, bool $sort = true, int $decimals = 2): array
    {
        $prepared = $this->prepareData($data, $sort);

        return [
            'mean'                => round($this->calculateMean($prepared), $decimals),
            'median'              => round($this->calculateMedian($prepared), $decimals),
            'robustDeviation'     => round($this->calculateRobustDeviation($prepared), $decimals),
            'robustVariance'      => round(pow($this->calculateRobustDeviation($prepared), 2), $decimals),
            'robustCv'            => round($this->calculateRobustCv($prepared), $decimals),
            'iqr'                 => round($this->calculateIqr($prepared), $decimals),
            'mad'                 => round($this->calculateMad($prepared), $decimals),
            'outliers'            => $this->detectOutliers($prepared),
            'confidenceIntervals' => $this->calculateConfidenceIntervals($prepared),
            'count'               => count($prepared)
        ];
    }

    // ========== INTERNAL ENGINE ==========

    private function calculateMean(array $data): float
    {
        return array_sum($data) / count($data);
    }

    private function calculateMedian(array $data): float
    {
        $n = count($data);
        $m = intdiv($n, 2);
        return ($n % 2 === 0) ? ($data[$m - 1] + $data[$m]) / 2.0 : (float) $data[$m];
    }

    private function calculateRobustDeviation(array $data): float
    {
        $n = count($data);
        return (1.25 / 1.35) * ($this->calculateIqr($data) / sqrt($n));
    }

    private function calculateRobustCv(array $data): float
    {
        $median = $this->calculateMedian($data);
        if (abs($median) < 1e-9) return 0.0;
        return ($this->calculateRobustDeviation($data) / abs($median)) * 100;
    }

    private function calculateIqr(array $data): float
    {
        return $this->calculatePercentile($data, 75) - $this->originalPercentile($data, 25);
    }

    private function calculateMad(array $data): float
    {
        $median = $this->calculateMedian($data);
        $diffs = array_map(fn($x) => abs($x - $median), $data);
        sort($diffs);
        return $this->calculateMedian($diffs);
    }

    private function detectOutliers(array $data): array
    {
        $iqr = $this->calculateIqr($data);
        $q1 = $this->originalPercentile($data, 25);
        $q3 = $this->calculatePercentile($data, 75);
        return array_values(array_filter($data, fn($x) => $x < ($q1 - 1.5 * $iqr) || $x > ($q3 + 1.5 * $iqr)));
    }

    private function calculateConfidenceIntervals(array $data): array
    {
        $median = $this->calculateMedian($data);
        $margin = 1.96 * $this->calculateRobustDeviation($data);
        return ['upper' => $median + $margin, 'lower' => $median - $margin];
    }

    private function calculatePercentile(array $data, int $p): float
    {
        $i = ($p / 100) * (count($data) - 1);
        $low = (int) floor($i);
        $high = (int) ceil($i);
        return $data[$low] + ($i - $low) * ($data[$high] - $data[$low]);
    }

    private function originalPercentile(array $data, int $p): float 
    {
        return $this->calculatePercentile($data, $p);
    }
}