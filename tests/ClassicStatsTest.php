<?php

declare(strict_types=1);

namespace Cjuol\SymmetricalOcto\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cjuol\SymmetricalOcto\ClassicStats;

class ClassicStatsTest extends TestCase
{
    private const DELTA = 0.001;

    private ClassicStats $stats;
    private array $datosReferencia = [87.30, 84.00, 85.40, 78.00, 85.00, 89.00, 79.00, 89.00, 76.00, 86.50];

    protected function setUp(): void
    {
        $this->stats = new ClassicStats();
    }

    public function testCalculoMedia(): void
    {
        $resultado = $this->stats->getMedia($this->datosReferencia);
        $this->assertEqualsWithDelta(83.92, $resultado, self::DELTA);
    }

    public function testCalculoMediana(): void
    {
        $this->assertEquals(85.2, $this->stats->getMediana($this->datosReferencia));
    }

    public function testCalculoDesviacionEstandar(): void
    {
        $resultado = $this->stats->getDesviacionEstandar($this->datosReferencia);
        $this->assertEqualsWithDelta(4.655176330351694, $resultado, self::DELTA);
    }

    public function testCalculoVarianzaMuestral(): void
    {
        $resultado = $this->stats->getVarianzaMuestral($this->datosReferencia);
        $this->assertEqualsWithDelta(21.67, $resultado, self::DELTA);
    }

    public function testCalculoVarianzaPoblacional(): void
    {
        $resultado = $this->stats->getVarianzaPoblacional($this->datosReferencia);
        $this->assertEqualsWithDelta(19.5036, $resultado, self::DELTA);
    }

    #[DataProvider('cvProvider')]
    public function testCalculoCV(array $datos, float $esperado): void
    {
        $resultado = $this->stats->getCV($datos);
        $this->assertEqualsWithDelta($esperado, $resultado, self::DELTA);
    }

    public function testDeteccionOutliers(): void
    {
        $outliers = $this->stats->getOutliers($this->datosReferencia);
        $this->assertEmpty($outliers);

        $datosConOutlier = array_merge(array_fill(0, 15, 0), [1000]);
        $outliersDetectados = $this->stats->getOutliers($datosConOutlier);
        $this->assertNotEmpty($outliersDetectados);
        $this->assertContains(1000, $outliersDetectados);
    }

    public function testObtenerResumen(): void
    {
        $resumen = $this->stats->obtenerResumen($this->datosReferencia, true, 2);

        $this->assertArrayHasKey('media', $resumen);
        $this->assertArrayHasKey('mediana', $resumen);
        $this->assertArrayHasKey('desviacionEstandar', $resumen);
        $this->assertArrayHasKey('varianzaMuestral', $resumen);
        $this->assertArrayHasKey('CV', $resumen);
        $this->assertArrayHasKey('outliers_zscore', $resumen);
        $this->assertArrayHasKey('count', $resumen);

        $this->assertEqualsWithDelta(83.92, $resumen['media'], self::DELTA);
        $this->assertEquals(85.2, $resumen['mediana']);
        $this->assertEqualsWithDelta(4.66, $resumen['desviacionEstandar'], self::DELTA);
        $this->assertEqualsWithDelta(21.67, $resumen['varianzaMuestral'], self::DELTA);
        $this->assertEqualsWithDelta(5.55, $resumen['CV'], self::DELTA);
        $this->assertIsArray($resumen['outliers_zscore']);
        $this->assertEquals(10, $resumen['count']);
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

    #[DataProvider('validacionProvider')]
    public function testValidacionDatos(array $datos): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->stats->getMedia($datos);
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
