<?php

declare(strict_types=1);

namespace Cjuol\StatGuard\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cjuol\StatGuard\StatsComparator;

class StatsComparatorTest extends TestCase
{

    #[DataProvider('veredictoProvider')]
    public function testAnalysisVerdict(array $datos, string $fragmentoEsperado): void
    {
        $comparator = new StatsComparator();
        $analisis = $comparator->analyze($datos);

        $this->assertStringContainsString($fragmentoEsperado, $analisis['verdict']);
    }

    public function testAnalysisDetectsHighBias(): void
    {
        $comparator = new StatsComparator();
        $datosSucios = [10, 10, 11, 12, 10, 500];

        $analisis = $comparator->analyze($datosSucios);

        $this->assertStringContainsString('ALERT', $analisis['verdict']);

        // Extract numeric value from the "X%" string
        $biasStr = $analisis['centralComparison']['biasPercent'];
        $biasFloat = (float) $biasStr;

        $this->assertGreaterThan(10.0, abs($biasFloat), 'Bias should exceed 10% to trigger ALERT');
    }

    public function testAnalysisContainsExpectedStructure(): void
    {
        $comparator = new StatsComparator();
        $analisis = $comparator->analyze([1, 2, 3, 4, 5, 6]);

        $this->assertArrayHasKey('centralComparison', $analisis);
        $this->assertArrayHasKey('dispersionComparison', $analisis);
        $this->assertArrayHasKey('outlierDetection', $analisis);
        $this->assertArrayHasKey('verdict', $analisis);

        $this->assertArrayHasKey('classicMean', $analisis['centralComparison']);
        $this->assertArrayHasKey('robustMedian', $analisis['centralComparison']);
        $this->assertArrayHasKey('absoluteDifference', $analisis['centralComparison']);
        $this->assertArrayHasKey('biasPercent', $analisis['centralComparison']);

        $this->assertArrayHasKey('stdDev', $analisis['dispersionComparison']);
        $this->assertArrayHasKey('robustDeviation', $analisis['dispersionComparison']);
        $this->assertArrayHasKey('noiseRatio', $analisis['dispersionComparison']);

        $this->assertArrayHasKey('tukeyMethod', $analisis['outlierDetection']);
        $this->assertArrayHasKey('zScoreMethod', $analisis['outlierDetection']);
    }

    public function testAnalysisBasicDispersionRatio(): void
    {
        $comparator = new StatsComparator();
        $analisis = $comparator->analyze([10, 10, 11, 12, 10, 500]);

        $ratio = (float) $analisis['dispersionComparison']['noiseRatio'];
        $this->assertGreaterThan(1.5, $ratio);
    }

    public static function veredictoProvider(): array
    {
        return [
            'alert_for_clear_outlier' => [[10, 10, 11, 12, 10, 500], 'ALERT'],
            'stable_clean'             => [[100, 102, 98, 101, 99], 'STABLE'],
            'moderate_caution'         => [[10, 11, 12, 13, 14, 19], 'CAUTION'],
        ];
    }
}
