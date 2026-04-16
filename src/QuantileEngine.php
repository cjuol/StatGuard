<?php

declare(strict_types=1);

namespace Cjuol\StatGuard;

use Cjuol\StatGuard\Support\DataValidator;

final class QuantileEngine
{
    public static function calculate(array $data, float $probability, int $type): float
    {
        return self::calculateInternal($data, $probability, $type, false);
    }

    public static function calculateSorted(array $data, float $probability, int $type): float
    {
        return self::calculateInternal($data, $probability, $type, true);
    }

    /**
     * Median of an already-sorted, already-validated numeric array.
     * Equivalent to type 7 quantile at p=0.5. Skips validation for speed —
     * callers must pass numeric, finite, sorted input.
     */
    public static function medianSorted(array $sorted): float
    {
        $n = count($sorted);
        $m = intdiv($n, 2);
        if ($n % 2 === 0) {
            return ($sorted[$m - 1] + $sorted[$m]) / 2.0;
        }
        return (float) $sorted[$m];
    }

    private static function calculateInternal(array $data, float $probability, int $type, bool $alreadySorted): float
    {
        if ($type < 1 || $type > 9) {
            throw new \InvalidArgumentException('Quantile type must be between 1 and 9.');
        }

        $sorted = self::normalizeData($data, $alreadySorted);

        $n = count($sorted);
        if ($n === 1) {
            return (float) $sorted[0];
        }

        $p = max(0.0, min(1.0, $probability));

        if ($type <= 3) {
            return self::calculateDiscrete($sorted, $p, $type);
        }

        return self::calculateContinuous($sorted, $p, $type);
    }

    private static function normalizeData(array $data, bool $alreadySorted): array
    {
        return DataValidator::prepare($data, 1, !$alreadySorted);
    }

    private static function calculateDiscrete(array $sorted, float $p, int $type): float
    {
        $n = count($sorted);

        return match ($type) {
            1 => (float) $sorted[max(1, min($n, (int) ceil($n * $p))) - 1],
            2 => self::calculateType2($sorted, $p),
            3 => (float) $sorted[max(1, min($n, (int) round($n * $p, 0, PHP_ROUND_HALF_EVEN))) - 1],
            default => throw new \InvalidArgumentException("Unsupported discrete quantile type: {$type}"),
        };
    }

    private static function calculateType2(array $sorted, float $p): float
    {
        $n = count($sorted);
        $h = $n * $p;

        if ($h <= 0.0) {
            return (float) $sorted[0];
        }
        if ($h >= $n) {
            return (float) $sorted[$n - 1];
        }

        $k = (int) floor($h);
        $g = $h - $k;

        if ($g == 0.0) {
            return self::applyType2Averaging((float) $sorted[$k - 1], (float) $sorted[$k]);
        }

        return (float) $sorted[$k];
    }

    private static function applyType2Averaging(float $lower, float $upper): float
    {
        return ($lower + $upper) / 2.0;
    }

    private static function calculateContinuous(array $sorted, float $p, int $type): float
    {
        $n = count($sorted);
        [$alpha, $beta] = self::getHyndmanFanParameters($type);

        $h = $alpha + ($n + 1.0 - $alpha - $beta) * $p;
        $k = (int) floor($h);
        $d = $h - $k;

        if ($k <= 0) {
            return (float) $sorted[0];
        }
        if ($k >= $n) {
            return (float) $sorted[$n - 1];
        }

        $lower = (float) $sorted[$k - 1];
        $upper = (float) $sorted[$k];

        return $lower + $d * ($upper - $lower);
    }

    private static function getHyndmanFanParameters(int $type): array
    {
        return match ($type) {
            4 => [0.0, 1.0],
            5 => [0.5, 0.5],
            6 => [0.0, 0.0],
            7 => [1.0, 1.0],
            8 => [1.0 / 3.0, 1.0 / 3.0],
            9 => [3.0 / 8.0, 3.0 / 8.0],
            default => throw new \InvalidArgumentException("Unsupported continuous quantile type: {$type}"),
        };
    }
}
