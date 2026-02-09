<?php

declare(strict_types=1);

namespace Cjuol\StatGuard\Contracts;

interface StatsInterface
{
    public function getMean(array $data): float;
    public function getMedian(array $data): float;
    public function getDeviation(array $data): float;
    public function getCoefficientOfVariation(array $data): float;
    public function getOutliers(array $data): array;
    public function getSummary(array $data, bool $sort = true, int $decimals = 2): array;
}