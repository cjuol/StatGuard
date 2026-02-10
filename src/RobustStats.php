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

    public const TYPE_1 = 1;
    public const TYPE_2 = 2;
    public const TYPE_3 = 3;
    public const TYPE_4 = 4;
    public const TYPE_5 = 5;
    public const TYPE_6 = 6;
    public const TYPE_7 = 7;
    public const TYPE_8 = 8;
    public const TYPE_9 = 9;
    public const TYPE_R_DEFAULT = 7;

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
        $prepared = $this->prepareData($data, true);
        return $this->calculateMad($prepared) * 1.4826;
    }

    public function getCoefficientOfVariation(array $data): float
    {
        // For interface consistency, use the scaled deviation
        $prepared = $this->prepareData($data, true);
        $median = $this->calculateMedian($prepared);
        if (abs($median) < 1e-9) return 0.0;
        return (($this->calculateMad($prepared) * 1.4826) / abs($median)) * 100;
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

    public function getIqr(array $data, int $type = self::TYPE_R_DEFAULT): float
    {
        return $this->calculateIqr($this->prepareData($data, true), $type);
    }

    public function getMad(array $data): float
    {
        return $this->calculateMad($this->prepareData($data, true));
    }

    public function getOutliers(array $data, int $type = self::TYPE_R_DEFAULT): array
    {
        return $this->detectOutliers($this->prepareData($data, true), $type);
    }

    /**
     * Trimmed mean by removing a percentage from each tail.
     *
     * Reference: Hyndman & Fan (1996) quantile definitions.
     */
    public function getTrimmedMean(array $data, float $trimPercentage = CentralTendencyEngine::DEFAULT_TRIM_PERCENTAGE): float
    {
        return CentralTendencyEngine::trimmedMean(
            $this->prepareData($data, true),
            $trimPercentage,
            true
        );
    }

    /**
     * Winsorized mean using quantile cut points.
     *
     * Reference: Hyndman & Fan (1996) quantile definitions.
     */
    public function getWinsorizedMean(
        array $data,
        float $trimPercentage = CentralTendencyEngine::DEFAULT_TRIM_PERCENTAGE,
        int $type = self::TYPE_R_DEFAULT
    ): float {
        return CentralTendencyEngine::winsorizedMean(
            $this->prepareData($data, true),
            $trimPercentage,
            $type,
            true
        );
    }

    /**
     * Huber M-estimator for robust location.
     *
     * Reference: Peter Huber (1964).
     */
    public function getHuberMean(
        array $data,
        float $k = CentralTendencyEngine::DEFAULT_HUBER_K,
        int $maxIterations = CentralTendencyEngine::DEFAULT_HUBER_MAX_ITERATIONS,
        float $tolerance = CentralTendencyEngine::DEFAULT_HUBER_TOLERANCE
    ): float {
        return CentralTendencyEngine::huberMean(
            $this->prepareData($data, true),
            $k,
            $maxIterations,
            $tolerance,
            true
        );
    }

    public function getConfidenceIntervals(array $data): array
    {
        return $this->calculateConfidenceIntervals($this->prepareData($data, true));
    }

    public function getSummary(array $data, bool $sort = true, int $decimals = 2): array
    {
        $prepared = $this->prepareData($data, $sort);

        $median = $this->calculateMedian($prepared);
        $robustDeviation = $this->calculateRobustDeviation($prepared);
        $iqr = $this->calculateIqr($prepared, self::TYPE_R_DEFAULT);
        $robustCv = (abs($median) < 1e-9) ? 0.0 : ($robustDeviation / abs($median)) * 100;
        $mad = $this->calculateMad($prepared);
        $q1 = QuantileEngine::calculateSorted($prepared, 0.25, self::TYPE_R_DEFAULT);
        $q3 = QuantileEngine::calculateSorted($prepared, 0.75, self::TYPE_R_DEFAULT);
        $outliers = $this->collectOutliers($prepared, $q1, $q3, $iqr);

        return [
            'mean'                => round($this->calculateMean($prepared), $decimals),
            'median'              => round($median, $decimals),
            'robustDeviation'     => round($robustDeviation, $decimals),
            'robustVariance'      => round(pow($robustDeviation, 2), $decimals),
            'robustCv'            => round($robustCv, $decimals),
            'iqr'                 => round($iqr, $decimals),
            'mad'                 => round($mad, $decimals),
            'outliers'            => $outliers,
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

    private function calculateIqr(array $data, int $type = self::TYPE_R_DEFAULT): float
    {
        return QuantileEngine::calculateSorted($data, 0.75, $type)
            - QuantileEngine::calculateSorted($data, 0.25, $type);
    }

    private function calculateMad(array $data): float
    {
        $median = $this->calculateMedian($data);
        $diffs = array_map(fn($x) => abs($x - $median), $data);
        sort($diffs);
        return $this->calculateMedian($diffs);
    }

    private function detectOutliers(array $data, int $type = self::TYPE_R_DEFAULT): array
    {
        $iqr = $this->calculateIqr($data, $type);
        $q1 = QuantileEngine::calculateSorted($data, 0.25, $type);
        $q3 = QuantileEngine::calculateSorted($data, 0.75, $type);
        return $this->collectOutliers($data, $q1, $q3, $iqr);
    }

    private function calculateConfidenceIntervals(array $data): array
    {
        $median = $this->calculateMedian($data);
        $margin = 1.96 * $this->calculateRobustDeviation($data);
        return ['upper' => $median + $margin, 'lower' => $median - $margin];
    }

    private function collectOutliers(array $data, float $q1, float $q3, float $iqr): array
    {
        $lowerFence = $q1 - 1.5 * $iqr;
        $upperFence = $q3 + 1.5 * $iqr;

        return array_values(array_filter(
            $data,
            fn($x) => $x < $lowerFence || $x > $upperFence
        ));
    }
}