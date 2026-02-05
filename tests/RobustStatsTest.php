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
        $this->assertEquals(85.2, $this->stats->getMediana($this->datosReferencia, true));
    }

    public function testCalculoDesviacionRobusta(): void
    {
        $resultado = $this->stats->getDesviacionRobusta($this->datosReferencia, true);
        // Usamos delta para permitir pequeñas variaciones de decimales
        $this->assertEqualsWithDelta(2.01, $resultado, 0.05);
    }

    public function testCalculoCVr(): void
    {
        $resultado = $this->stats->getCVr($this->datosReferencia, true);
        $this->assertEqualsWithDelta(2.35, $resultado, 0.05);
    }

    public function testIntervalosConfianza(): void
    {
        $ic = $this->stats->getIntervalosConfianza($this->datosReferencia, true);
        $this->assertEqualsWithDelta(89.13, $ic['superior'], 0.1);
        $this->assertEqualsWithDelta(81.27, $ic['inferior'], 0.1);
    }

    public function testCalculoMedia(): void
    {
        $resultado = $this->stats->getMedia($this->datosReferencia, true);
        $this->assertEqualsWithDelta(83.92, $resultado, 0.05);
    }

    public function testCalculoVarianzaRobusta(): void
    {
        $resultado = $this->stats->getVarianzaRobusta($this->datosReferencia, true);
        // Varianza robusta = S*²
        $this->assertEqualsWithDelta(4.04, $resultado, 0.1);
    }

    public function testCalculoIQR(): void
    {
        $resultado = $this->stats->getIQR($this->datosReferencia, true);
        // IQR = Q3 - Q1
        $this->assertEqualsWithDelta(6.85, $resultado, 0.1);
    }

    public function testCalculoMAD(): void
    {
        $resultado = $this->stats->getMAD($this->datosReferencia, true);
        // MAD = Desviación Absoluta de la Mediana
        $this->assertEqualsWithDelta(2.95, $resultado, 0.1);
    }

    public function testDeteccionOutliers(): void
    {
        // Con los datos de referencia no hay outliers
        $outliers = $this->stats->getOutliers($this->datosReferencia, true);
        $this->assertEmpty($outliers);

        // Probamos con datos que contienen outliers
        $datosConOutliers = [1, 2, 3, 4, 5, 100]; // 100 es un outlier claro
        $outliersDetectados = $this->stats->getOutliers($datosConOutliers, true);
        $this->assertNotEmpty($outliersDetectados);
        $this->assertContains(100, $outliersDetectados);
    }

    public function testObtenerResumen(): void
    {
        $resumen = $this->stats->obtenerResumen($this->datosReferencia, true, 2);

        // Validamos que el resumen contiene todas las claves esperadas
        $this->assertArrayHasKey('media', $resumen);
        $this->assertArrayHasKey('mediana', $resumen);
        $this->assertArrayHasKey('desviacionRobusta', $resumen);
        $this->assertArrayHasKey('varianzaRobusta', $resumen);
        $this->assertArrayHasKey('CVr', $resumen);
        $this->assertArrayHasKey('IQR', $resumen);
        $this->assertArrayHasKey('MAD', $resumen);
        $this->assertArrayHasKey('outliers', $resumen);
        $this->assertArrayHasKey('intervalosConfianza', $resumen);

        // Validamos algunos valores
        $this->assertEqualsWithDelta(83.92, $resumen['media'], 0.05);
        $this->assertEquals(85.2, $resumen['mediana']);
        $this->assertEqualsWithDelta(2.01, $resumen['desviacionRobusta'], 0.05);
        $this->assertIsArray($resumen['intervalosConfianza']);
        $this->assertIsArray($resumen['outliers']);
    }

    public function testValidacionDatos(): void
    {
        // Test con menos de 2 valores
        $this->expectException(\InvalidArgumentException::class);
        $this->stats->getMedia([1], true);
    }

    public function testValidacionDatosNoNumericos(): void
    {
        // Test con valores no numéricos
        $this->expectException(\InvalidArgumentException::class);
        $this->stats->getMedia([1, 'abc', 3], true);
    }

    public function testSinOrdenamiento(): void
    {
        // Probamos que funciona correctamente sin ordenar datos
        $datosDesordenados = [85.00, 87.30, 78.00, 89.00];
        $resultado = $this->stats->getMediana($datosDesordenados, false);
        // La mediana sin ordenar de [85.00, 87.30, 78.00, 89.00] sería (87.30 + 78.00) / 2 = 82.65
        $this->assertEqualsWithDelta(82.65, $resultado, 0.05);
    }
}