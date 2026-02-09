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
    public function testCalculoMediana(array $datos, float $esperado): void
    {
        $this->assertEquals($esperado, $this->stats->getMediana($datos));
    }

    #[DataProvider('desviacionRobustaProvider')]
    public function testCalculoDesviacionRobusta(array $datos, float $esperado): void
    {
        $resultado = $this->stats->getDesviacionRobusta($datos);
        // Usamos delta para permitir pequeñas variaciones de decimales
        $this->assertEqualsWithDelta($esperado, $resultado, self::DELTA);
    }

    public function testCalculoCVr(): void
    {
        $resultado = $this->stats->getCVr($this->datosReferencia);
        $this->assertEqualsWithDelta(2.354112542617955, $resultado, self::DELTA);
    }

    public function testCalculoCVConMedianaCero(): void
    {
        $resultado = $this->stats->getCV([0, 0, 0]);
        $this->assertSame(0.0, $resultado);
    }

    public function testIntervalosConfianza(): void
    {
        $ic = $this->stats->getIntervalosConfianza($this->datosReferencia);
        $this->assertEqualsWithDelta(89.13117961716858, $ic['superior'], self::DELTA);
        $this->assertEqualsWithDelta(81.26882038283142, $ic['inferior'], self::DELTA);
    }

    public function testCalculoMedia(): void
    {
        $resultado = $this->stats->getMedia($this->datosReferencia);
        $this->assertEqualsWithDelta(83.92, $resultado, self::DELTA);
    }

    public function testCalculoVarianzaRobusta(): void
    {
        $resultado = $this->stats->getVarianzaRobusta($this->datosReferencia);
        // Varianza robusta = S*²
        $this->assertEqualsWithDelta(4.022848079561035, $resultado, self::DELTA);
    }

    public function testCalculoIQR(): void
    {
        $resultado = $this->stats->getIQR($this->datosReferencia);
        // IQR = Q3 - Q1
        $this->assertEqualsWithDelta(6.85, $resultado, self::DELTA);
    }

    public function testCalculoMAD(): void
    {
        $resultado = $this->stats->getMAD($this->datosReferencia);
        // MAD = Desviación Absoluta de la Mediana
        $this->assertEqualsWithDelta(2.95, $resultado, self::DELTA);
    }

    public function testDeteccionOutliers(): void
    {
        // Con los datos de referencia no hay outliers
        $outliers = $this->stats->getOutliers($this->datosReferencia);
        $this->assertEmpty($outliers);

        // Probamos con datos que contienen outliers
        $datosConOutliers = [1, 2, 3, 4, 5, 100]; // 100 es un outlier claro
        $outliersDetectados = $this->stats->getOutliers($datosConOutliers);
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
        $this->assertEqualsWithDelta(83.92, $resumen['media'], self::DELTA);
        $this->assertEquals(85.2, $resumen['mediana']);
        $this->assertEqualsWithDelta(2.01, $resumen['desviacionRobusta'], self::DELTA);
        $this->assertIsArray($resumen['intervalosConfianza']);
        $this->assertIsArray($resumen['outliers']);
    }

    public function testExportJsonCoincideConResumen(): void
    {
        $json = $this->stats->toJson($this->datosReferencia);
        $this->assertJson($json);

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($this->stats->obtenerResumen($this->datosReferencia), $decoded);
    }

    public function testExportCsvCoincideConResumen(): void
    {
        $csv = $this->stats->toCsv($this->datosReferencia);
        $lineas = preg_split('/\r?\n/', trim($csv));

        $this->assertCount(2, $lineas);

        $cabeceras = str_getcsv($lineas[0], ',');
        $valores = str_getcsv($lineas[1], ',');

        $resumen = $this->stats->obtenerResumen($this->datosReferencia);
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

    public function testCVrConMedianaCero(): void
    {
        $datos = [-1, 0, 1, 0];
        $resultado = $this->stats->getCVr($datos);
        $this->assertSame(0.0, $resultado);
    }

    #[DataProvider('validacionProvider')]
    public function testValidacionDatos(array $datos): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->stats->getMedia($datos);
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