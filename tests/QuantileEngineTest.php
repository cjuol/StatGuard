<?php

declare(strict_types=1);

namespace Cjuol\StatGuard\Tests;

use Cjuol\StatGuard\QuantileEngine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class QuantileEngineTest extends TestCase
{
    private const DELTA = 1e-7;

    #[DataProvider('rQuantileProvider')]
    public function testQuantilesMatchR(int $type, float $p, float $expected): void
    {
        $data = self::sampleData();
        $result = QuantileEngine::calculate($data, $p, $type);

        $this->assertEqualsWithDelta($expected, $result, self::DELTA);
    }

    public function testCalculateSortedMatchesUnsorted(): void
    {
        $sorted = self::sampleData();
        $unsorted = [9, 0, 1, 2, 3, 4, 5, 6, 7, 8];

        $expected = QuantileEngine::calculate($unsorted, 0.5, 7);
        $result = QuantileEngine::calculateSorted($sorted, 0.5, 7);

        $this->assertEqualsWithDelta($expected, $result, self::DELTA);
    }

    public static function rQuantileProvider(): array
    {
        return [
            't1_p25' => [1, 0.25, 2.0],
            't1_p50' => [1, 0.50, 4.0],
            't1_p75' => [1, 0.75, 7.0],

            't2_p25' => [2, 0.25, 2.0],
            't2_p50' => [2, 0.50, 4.5],
            't2_p75' => [2, 0.75, 7.0],

            't3_p25' => [3, 0.25, 1.0],
            't3_p50' => [3, 0.50, 4.0],
            't3_p75' => [3, 0.75, 7.0],

            't4_p25' => [4, 0.25, 1.5],
            't4_p50' => [4, 0.50, 4.0],
            't4_p75' => [4, 0.75, 6.5],

            't5_p25' => [5, 0.25, 2.0],
            't5_p50' => [5, 0.50, 4.5],
            't5_p75' => [5, 0.75, 7.0],

            't6_p25' => [6, 0.25, 1.75],
            't6_p50' => [6, 0.50, 4.5],
            't6_p75' => [6, 0.75, 7.25],

            't7_p25' => [7, 0.25, 2.25],
            't7_p50' => [7, 0.50, 4.5],
            't7_p75' => [7, 0.75, 6.75],

            't8_p25' => [8, 0.25, 1.9166666666667],
            't8_p50' => [8, 0.50, 4.5],
            't8_p75' => [8, 0.75, 7.0833333333333],

            't9_p25' => [9, 0.25, 1.9375],
            't9_p50' => [9, 0.50, 4.5],
            't9_p75' => [9, 0.75, 7.0625],
        ];
    }

    private static function sampleData(): array
    {
        return [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
    }
}
