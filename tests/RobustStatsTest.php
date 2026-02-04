<?php

declare(strict_types=1);

namespace Cjuol\SymmetricalOcto\Tests;

use PHPUnit\Framework\TestCase;
use Cjuol\SymmetricalOcto\RobustStats;

class RobustStatsTest extends TestCase
{
    private RobustStats $stats;
    private array $datosReferencia = [87.30, 84.00, 85.40, 78.00, 85.00, 89.00, 79.00, 89.00, 76.00, 86.50];

    protected function setUp(): void
    {
        $this->stats = new RobustStats();
    }

    public function testCalculoMediana(): void
    {
        $this->assertEquals(85.2, $this->stats->calcularMediana($this->datosReferencia));
    }

    public function testCalculoDesviacionRobusta(): void
    {
        $resultado = $this->stats->calcularDesviacionRobusta($this->datosReferencia);
        // Usamos delta para permitir pequeñas variaciones de decimales
        $this->assertEqualsWithDelta(2.01, $resultado, 0.05);
    }

    public function testCalculoCVr(): void
    {
        $resultado = $this->stats->calcularCVr($this->datosReferencia);
        $this->assertEqualsWithDelta(2.35, $resultado, 0.05);
    }

    public function testIntervalosConfianza(): void
    {
        $ic = $this->stats->calcularIntervalosConfianza($this->datosReferencia);
        $this->assertEqualsWithDelta(89.13, $ic['superior'], 0.1);
        $this->assertEqualsWithDelta(81.27, $ic['inferior'], 0.1);
    }
}