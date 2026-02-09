<?php

declare(strict_types=1);

namespace Cjuol\StatGuard\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cjuol\StatGuard\StatsComparator;

class StatsComparatorTest extends TestCase
{

    #[DataProvider('veredictoProvider')]
    public function testAnalisisVeredicto(array $datos, string $fragmentoEsperado): void
    {
        $comparator = new StatsComparator();
        $analisis = $comparator->analizar($datos);

        $this->assertStringContainsString($fragmentoEsperado, $analisis['veredicto']);
    }

    public function testAnalysisDetectsHighBias(): void
    {
        $comparator = new StatsComparator();
        $datosSucios = [10, 10, 11, 12, 10, 500];

        $analisis = $comparator->analizar($datosSucios);

        $this->assertStringContainsString('ALERTA', $analisis['veredicto']);

        // Extraemos el valor numérico del string "X%"
        $sesgoStr = $analisis['comparativa_central']['sesgo_porcentaje'];
        $sesgoFloat = (float) $sesgoStr; 

        $this->assertGreaterThan(10.0, abs($sesgoFloat), "El sesgo debería ser superior al 10% para activar ALERTA");
    }

    public function testAnalysisContainsExpectedStructure(): void
    {
        $comparator = new StatsComparator();
        $analisis = $comparator->analizar([1, 2, 3, 4, 5, 6]);

        $this->assertArrayHasKey('comparativa_central', $analisis);
        $this->assertArrayHasKey('comparativa_dispersion', $analisis);
        $this->assertArrayHasKey('deteccion_outliers', $analisis);
        $this->assertArrayHasKey('veredicto', $analisis);

        $this->assertArrayHasKey('media_clasica', $analisis['comparativa_central']);
        $this->assertArrayHasKey('mediana_robusta', $analisis['comparativa_central']);
        $this->assertArrayHasKey('diferencia_abs', $analisis['comparativa_central']);
        $this->assertArrayHasKey('sesgo_porcentaje', $analisis['comparativa_central']);

        $this->assertArrayHasKey('desv_estandar', $analisis['comparativa_dispersion']);
        $this->assertArrayHasKey('desv_robusta', $analisis['comparativa_dispersion']);
        $this->assertArrayHasKey('ratio_ruido', $analisis['comparativa_dispersion']);

        $this->assertArrayHasKey('metodo_tukey', $analisis['deteccion_outliers']);
        $this->assertArrayHasKey('metodo_zscore', $analisis['deteccion_outliers']);
    }

    public function testAnalysisBasicDispersionRatio(): void
    {
        $comparator = new StatsComparator();
        $analisis = $comparator->analizar([10, 10, 11, 12, 10, 500]);

        $ratio = (float) $analisis['comparativa_dispersion']['ratio_ruido'];
        $this->assertGreaterThan(1.5, $ratio);
    }

    public static function veredictoProvider(): array
    {
        return [
            'alerta_por_outlier_claro' => [[10, 10, 11, 12, 10, 500], 'ALERTA'],
            'estable_limpio'           => [[100, 102, 98, 101, 99], 'ESTABLE'],
            'precaucion_moderada'      => [[10, 11, 12, 13, 14, 19], 'PRECAUCIÓN'],
        ];
    }
}
