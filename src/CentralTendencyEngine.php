<?php

declare(strict_types=1);

namespace Cjuol\StatGuard;

use Cjuol\StatGuard\Exceptions\InvalidDataSetException;

/**
 * CentralTendencyEngine - robust central tendency estimators.
 *
 * Quantile references: Hyndman & Fan (1996).
 * Huber M-estimator reference: Peter Huber (1964).
 */
final class CentralTendencyEngine
{
    public const DEFAULT_TRIM_PERCENTAGE = 0.1;
    public const DEFAULT_QUANTILE_TYPE = 7;
    public const DEFAULT_HUBER_K = 1.345;
    public const DEFAULT_HUBER_MAX_ITERATIONS = 50;
    public const DEFAULT_HUBER_TOLERANCE = 0.001;

    /**
     * Compute the trimmed mean by removing a percentage from each tail.
     *
     * Reference: Hyndman & Fan (1996) quantile definitions (used for tail logic).
     */
    public static function trimmedMean(
        array $data,
        float $trimPercentage = self::DEFAULT_TRIM_PERCENTAGE,
        bool $alreadySorted = false
    ): float {
        self::validateTrimPercentage($trimPercentage);
        $sorted = self::normalizeData($data, $alreadySorted);
        $n = count($sorted);

        if ($n === 1) {
            return (float) $sorted[0];
        }

        if ($trimPercentage === 0.0) {
            return self::mean($sorted);
        }

        $trimCount = (int) floor($n * $trimPercentage);
        if (($n - 2 * $trimCount) <= 0) {
            throw new InvalidDataSetException('Trim percentage too large for dataset size.');
        }

        $trimmed = array_slice($sorted, $trimCount, $n - 2 * $trimCount);
        return self::mean($trimmed);
    }

    /**
     * Compute the winsorized mean using quantile cut points.
     *
     * Reference: Hyndman & Fan (1996) quantile definitions.
     */
    public static function winsorizedMean(
        array $data,
        float $trimPercentage = self::DEFAULT_TRIM_PERCENTAGE,
        int $type = self::DEFAULT_QUANTILE_TYPE,
        bool $alreadySorted = false
    ): float {
        self::validateTrimPercentage($trimPercentage);
        $sorted = self::normalizeData($data, $alreadySorted);
        $n = count($sorted);

        if ($n === 1) {
            return (float) $sorted[0];
        }

        if ($trimPercentage === 0.0) {
            return self::mean($sorted);
        }

        $lower = QuantileEngine::calculateSorted($sorted, $trimPercentage, $type);
        $upper = QuantileEngine::calculateSorted($sorted, 1.0 - $trimPercentage, $type);

        $winsorized = [];
        foreach ($sorted as $value) {
            if ($value < $lower) {
                $winsorized[] = $lower;
                continue;
            }
            if ($value > $upper) {
                $winsorized[] = $upper;
                continue;
            }
            $winsorized[] = $value;
        }

        return self::mean($winsorized);
    }

    /**
     * Compute the Huber M-estimator for location.
     *
     * Reference: Peter Huber (1964) M-estimator of location.
     */
    public static function huberMean(
        array $data,
        float $k = self::DEFAULT_HUBER_K,
        int $maxIterations = self::DEFAULT_HUBER_MAX_ITERATIONS,
        float $tolerance = self::DEFAULT_HUBER_TOLERANCE,
        bool $alreadySorted = false
    ): float {
        $sorted = self::normalizeData($data, $alreadySorted);
        $n = count($sorted);

        if ($n === 1) {
            return (float) $sorted[0];
        }

        $median = self::median($sorted);
        $mad = self::mad($sorted, $median);
        $scale = $mad * 1.4826;

        if ($scale < 1e-9) {
            return $median;
        }

        $mu = $median;
        $cutoff = $k * $scale;

        for ($i = 0; $i < $maxIterations; $i++) {
            $weightedSum = 0.0;
            $weightTotal = 0.0;

            foreach ($sorted as $value) {
                $diff = $value - $mu;
                $absDiff = abs($diff);

                if ($absDiff <= $cutoff) {
                    $weight = 1.0;
                } else {
                    $weight = $cutoff / $absDiff;
                }

                $weightedSum += $weight * $value;
                $weightTotal += $weight;
            }

            if ($weightTotal < 1e-12) {
                break;
            }

            $newMu = $weightedSum / $weightTotal;

            if (abs($newMu - $mu) < $tolerance) {
                $mu = $newMu;
                break;
            }

            $mu = $newMu;
        }

        return $mu;
    }

    private static function validateTrimPercentage(float $trimPercentage): void
    {
        if ($trimPercentage < 0.0 || $trimPercentage >= 0.5) {
            throw new InvalidDataSetException('Trim percentage must be between 0.0 and 0.5 (exclusive).');
        }
    }

    private static function normalizeData(array $data, bool $alreadySorted): array
    {
        $count = count($data);
        if ($count === 0) {
            throw new InvalidDataSetException('At least 1 numeric value is required.');
        }

        $isSequential = true;
        $expectedKey = 0;

        foreach ($data as $key => $value) {
            if (!is_numeric($value)) {
                throw new InvalidDataSetException('All sample values must be numeric.');
            }

            if ($key !== $expectedKey) {
                $isSequential = false;
            }
            $expectedKey++;
        }

        $normalized = $isSequential ? $data : array_values($data);

        if (!$alreadySorted) {
            sort($normalized, SORT_NUMERIC);
        }

        return $normalized;
    }

    private static function mean(array $data): float
    {
        return array_sum($data) / count($data);
    }

    private static function median(array $sorted): float
    {
        $n = count($sorted);
        $m = intdiv($n, 2);
        if ($n % 2 === 0) {
            return ($sorted[$m - 1] + $sorted[$m]) / 2.0;
        }
        return (float) $sorted[$m];
    }

    private static function mad(array $sorted, float $median): float
    {
        $diffs = array_map(fn($x) => abs($x - $median), $sorted);
        sort($diffs, SORT_NUMERIC);
        return self::median($diffs);
    }
}
