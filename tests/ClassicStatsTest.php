<?php

declare(strict_types=1);

namespace Cjuol\StatGuard\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cjuol\StatGuard\ClassicStats;

class ClassicStatsTest extends TestCase
{
    private const DELTA = 0.001;

    private ClassicStats $stats;
    private array $datosReferencia = [87.30, 84.00, 85.40, 78.00, 85.00, 89.00, 79.00, 89.00, 76.00, 86.50];

    protected function setUp(): void
    {
        $this->stats = new ClassicStats();
    }

    public function testMeanCalculation(): void
    {
        $result = $this->stats->getMean($this->datosReferencia);
        $this->assertEqualsWithDelta(83.92, $result, self::DELTA);
    }

    public function testMedianCalculation(): void
    {
        $this->assertEquals(85.2, $this->stats->getMedian($this->datosReferencia));
    }

    public function testStandardDeviationCalculation(): void
    {
        $result = $this->stats->getStandardDeviation($this->datosReferencia);
        $this->assertEqualsWithDelta(4.655176330351694, $result, self::DELTA);
    }

    public function testSampleVarianceCalculation(): void
    {
        $result = $this->stats->getSampleVariance($this->datosReferencia);
        $this->assertEqualsWithDelta(21.67, $result, self::DELTA);
    }

    public function testPopulationVarianceCalculation(): void
    {
        $result = $this->stats->getPopulationVariance($this->datosReferencia);
        $this->assertEqualsWithDelta(19.5036, $result, self::DELTA);
    }

    #[DataProvider('cvProvider')]
    public function testCoefficientOfVariationCalculation(array $datos, float $esperado): void
    {
        $result = $this->stats->getCoefficientOfVariation($datos);
        $this->assertEqualsWithDelta($esperado, $result, self::DELTA);
    }

    public function testOutlierDetection(): void
    {
        $outliers = $this->stats->getOutliers($this->datosReferencia);
        $this->assertEmpty($outliers);

        $datosConOutlier = array_merge(array_fill(0, 15, 0), [1000]);
        $outliersDetectados = $this->stats->getOutliers($datosConOutlier);
        $this->assertNotEmpty($outliersDetectados);
        $this->assertContains(1000, $outliersDetectados);
    }

    public function testGetSummary(): void
    {
        $summary = $this->stats->getSummary($this->datosReferencia, true, 2);

        $this->assertArrayHasKey('mean', $summary);
        $this->assertArrayHasKey('median', $summary);
        $this->assertArrayHasKey('stdDev', $summary);
        $this->assertArrayHasKey('sampleVariance', $summary);
        $this->assertArrayHasKey('cv', $summary);
        $this->assertArrayHasKey('outliersZScore', $summary);
        $this->assertArrayHasKey('count', $summary);

        $this->assertEqualsWithDelta(83.92, $summary['mean'], self::DELTA);
        $this->assertEquals(85.2, $summary['median']);
        $this->assertEqualsWithDelta(4.66, $summary['stdDev'], self::DELTA);
        $this->assertEqualsWithDelta(21.67, $summary['sampleVariance'], self::DELTA);
        $this->assertEqualsWithDelta(5.55, $summary['cv'], self::DELTA);
        $this->assertIsArray($summary['outliersZScore']);
        $this->assertEquals(10, $summary['count']);
    }

    public function testExportJsonMatchesSummary(): void
    {
        $json = $this->stats->toJson($this->datosReferencia);
        $this->assertJson($json);

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($this->stats->getSummary($this->datosReferencia), $decoded);
    }

    public function testExportCsvMatchesSummary(): void
    {
        $csv = $this->stats->toCsv($this->datosReferencia);
        $lineas = preg_split('/\r?\n/', trim($csv));

        $this->assertCount(2, $lineas);

        $cabeceras = str_getcsv($lineas[0], ',');
        $valores = str_getcsv($lineas[1], ',');

        $resumen = $this->stats->getSummary($this->datosReferencia);
        $esperado = [];
        foreach ($resumen as $key => $value) {
            if (is_array($value)) {
                $esperado[$key] = empty($value) ? '' : implode('|', $value);
            } else {
                $esperado[$key] = (string) $value;
            }
        }

        $this->assertSame(array_keys($esperado), $cabeceras);
        $this->assertSame(array_values($esperado), $valores);
    }

    #[DataProvider('validacionProvider')]
    public function testDataValidation(array $datos): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->stats->getMean($datos);
    }

    public static function cvProvider(): array
    {
        return [
            'datos_referencia' => [[87.30, 84.00, 85.40, 78.00, 85.00, 89.00, 79.00, 89.00, 76.00, 86.50], 5.5471595928881],
            'media_cero' => [[0, 0, 0], 0.0],
        ];
    }

    public static function validacionProvider(): array
    {
        return [
            'menos_de_dos' => [[1]],
            'no_numericos' => [[1, 'abc', 3]],
        ];
    }
}
