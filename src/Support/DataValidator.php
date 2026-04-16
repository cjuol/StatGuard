<?php

declare(strict_types=1);

namespace Cjuol\StatGuard\Support;

use Cjuol\StatGuard\Exceptions\InvalidDataSetException;

/**
 * Single source of truth for dataset validation and normalization.
 *
 * Rejects non-numeric and non-finite values, reindexes non-sequential keys,
 * and optionally sorts numerically. Used by QuantileEngine, CentralTendencyEngine,
 * and DataProcessorTrait so the three entry points share one policy.
 */
final class DataValidator
{
    /**
     * @param array<int|string, mixed> $data
     * @return list<int|float>
     */
    public static function prepare(array $data, int $minCount = 1, bool $sort = false): array
    {
        if (count($data) < $minCount) {
            $noun = $minCount === 1 ? 'value is' : 'values are';
            throw new InvalidDataSetException(
                sprintf('At least %d numeric %s required.', $minCount, $noun)
            );
        }

        $isSequential = true;
        $expectedKey = 0;

        foreach ($data as $key => $value) {
            if (!is_numeric($value)) {
                throw new InvalidDataSetException('All sample values must be numeric.');
            }
            if (!is_finite((float) $value)) {
                throw new InvalidDataSetException('Non-finite values (NaN/Inf) are not allowed.');
            }
            if ($key !== $expectedKey) {
                $isSequential = false;
            }
            $expectedKey++;
        }

        $normalized = $isSequential ? $data : array_values($data);

        if ($sort) {
            sort($normalized, SORT_NUMERIC);
        }

        /** @var list<int|float> $normalized */
        return $normalized;
    }
}
