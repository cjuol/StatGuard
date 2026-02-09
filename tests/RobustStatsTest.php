<?php

declare(strict_types=1);

namespace Cjuol\StatGuard\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cjuol\StatGuard\RobustStats;

class RobustStatsTest extends TestCase
{
    private const DELTA = 0.001;

    private RobustStats $stats;
    private array $datosReferencia = [87.30, 84.00, 85.40, 78.00, 85.00, 89.00, 79.00, 89.00, 76.00, 86.50];

    protected function setUp(): void
    {
        $this->stats = new RobustStats();
    }

    #[DataProvider('medianaProvider')]
    public function testMedianCalculation(array $datos, float $esperado): void
    {
        $this->assertEquals($esperado, $this->stats->getMedian($datos));
    }

    #[DataProvider('desviacionRobustaProvider')]
    public function testRobustDeviationCalculation(array $datos, float $esperado): void
    {
        $result = $this->stats->getRobustDeviation($datos);
        // Use delta to allow small decimal variations
        $this->assertEqualsWithDelta($esperado, $result, self::DELTA);
    }

    public function testRobustCvCalculation(): void
    {
        $result = $this->stats->getRobustCv($this->datosReferencia);
        $this->assertEqualsWithDelta(2.354112542617955, $result, self::DELTA);
    }

    public function testCoefficientOfVariationWithZeroMedian(): void
    {
        $result = $this->stats->getCoefficientOfVariation([0, 0, 0]);
        $this->assertSame(0.0, $result);
    }

    public function testConfidenceIntervals(): void
    {
        $intervals = $this->stats->getConfidenceIntervals($this->datosReferencia);
        $this->assertEqualsWithDelta(89.13117961716858, $intervals['upper'], self::DELTA);
        $this->assertEqualsWithDelta(81.26882038283142, $intervals['lower'], self::DELTA);
    }

    public function testMeanCalculation(): void
    {
        $result = $this->stats->getMean($this->datosReferencia);
        $this->assertEqualsWithDelta(83.92, $result, self::DELTA);
    }

    public function testRobustVarianceCalculation(): void
    {
        $result = $this->stats->getRobustVariance($this->datosReferencia);
        // Robust variance = S*^2
        $this->assertEqualsWithDelta(4.022848079561035, $result, self::DELTA);
    }

    public function testIqrCalculation(): void
    {
        $result = $this->stats->getIqr($this->datosReferencia);
        // IQR = Q3 - Q1
        $this->assertEqualsWithDelta(6.85, $result, self::DELTA);
    }

    public function testMadCalculation(): void
    {
        $result = $this->stats->getMad($this->datosReferencia);
        // MAD = Median Absolute Deviation
        $this->assertEqualsWithDelta(2.95, $result, self::DELTA);
    }

    public function testOutlierDetection(): void
    {
        // No outliers for the reference data
        $outliers = $this->stats->getOutliers($this->datosReferencia);
        $this->assertEmpty($outliers);

        // Try data that contains outliers
        $dataWithOutliers = [1, 2, 3, 4, 5, 100]; // 100 is a clear outlier
        $detectedOutliers = $this->stats->getOutliers($dataWithOutliers);
        $this->assertNotEmpty($detectedOutliers);
        $this->assertContains(100, $detectedOutliers);
    }

    public function testGetSummary(): void
    {
        $summary = $this->stats->getSummary($this->datosReferencia, true, 2);

        // Validate that the summary contains all expected keys
        $this->assertArrayHasKey('mean', $summary);
        $this->assertArrayHasKey('median', $summary);
        $this->assertArrayHasKey('robustDeviation', $summary);
        $this->assertArrayHasKey('robustVariance', $summary);
        $this->assertArrayHasKey('robustCv', $summary);
        $this->assertArrayHasKey('iqr', $summary);
        $this->assertArrayHasKey('mad', $summary);
        $this->assertArrayHasKey('outliers', $summary);
        $this->assertArrayHasKey('confidenceIntervals', $summary);

        // Validate a few values
        $this->assertEqualsWithDelta(83.92, $summary['mean'], self::DELTA);
        $this->assertEquals(85.2, $summary['median']);
        $this->assertEqualsWithDelta(2.01, $summary['robustDeviation'], self::DELTA);
        $this->assertIsArray($summary['confidenceIntervals']);
        $this->assertIsArray($summary['outliers']);
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

    public function testRobustCvWithZeroMedian(): void
    {
        $datos = [-1, 0, 1, 0];
        $result = $this->stats->getRobustCv($datos);
        $this->assertSame(0.0, $result);
    }

    #[DataProvider('validacionProvider')]
    public function testDataValidation(array $datos): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->stats->getMean($datos);
    }

    public static function medianaProvider(): array
    {
        return [
            'datos_referencia' => [[87.30, 84.00, 85.40, 78.00, 85.00, 89.00, 79.00, 89.00, 76.00, 86.50], 85.2],
            'negativos' => [[-9, -7, -5, -3], -6.0],
        ];
    }

    public static function desviacionRobustaProvider(): array
    {
        return [
            'datos_referencia' => [[87.30, 84.00, 85.40, 78.00, 85.00, 89.00, 79.00, 89.00, 76.00, 86.50], 2.005703886310498],
            'negativos' => [[-9, -7, -5, -3], 1.388888888888889],
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