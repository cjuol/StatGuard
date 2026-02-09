<?php

declare(strict_types=1);

namespace Cjuol\StatGuard\Contracts;

interface StatsInterface
{
    public function getMedia(array $datos): float;
    public function getMediana(array $datos): float;
    public function getDesviacion(array $datos): float;
    public function getCV(array $datos): float;
    public function getOutliers(array $datos): array;
    public function obtenerResumen(array $datos, bool $ordenar = true, int $decimales = 2): array;
}