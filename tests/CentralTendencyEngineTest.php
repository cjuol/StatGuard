<?php

declare(strict_types=1);

namespace Cjuol\StatGuard\Tests;

use Cjuol\StatGuard\CentralTendencyEngine;
use Cjuol\StatGuard\Exceptions\InvalidDataSetException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CentralTendencyEngineTest extends TestCase
{
    private const DELTA = 1e-10;
    private const OUTLIER_DATA = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 1000];
    private const SINGLE_VALUE = [42];

    private static ?array $rReference = null;

    #[DataProvider('emptyDatasetProvider')]
    public function testEmptyDatasetThrows(callable $call): void
    {
        $this->expectException(InvalidDataSetException::class);
        $call();
    }

    #[DataProvider('singleElementProvider')]
    public function testSingleElementReturnsValue(callable $call): void
    {
        $this->assertEqualsWithDelta(42.0, $call(), self::DELTA);
    }

    public function testTrimmedMeanMatchesRReference(): void
    {
        $this->skipIfRUnavailable();
        $reference = self::getRReference();
        $result = CentralTendencyEngine::trimmedMean(self::OUTLIER_DATA, 0.1);

        $this->assertEqualsWithDelta($reference['trimmed'], $result, self::DELTA);
    }

    public function testWinsorizedMeanMatchesRReference(): void
    {
        $this->skipIfRUnavailable();
        $reference = self::getRReference();
        $result = CentralTendencyEngine::winsorizedMean(self::OUTLIER_DATA, 0.1, 7);

        $this->assertEqualsWithDelta($reference['winsorized'], $result, self::DELTA);
    }

    public function testHuberMeanMatchesRReference(): void
    {
        $this->skipIfRUnavailable();
        $reference = self::getRReference();
        $result = CentralTendencyEngine::huberMean(
            self::OUTLIER_DATA,
            (float) $reference['huber_k'],
            200,
            (float) $reference['huber_tol']
        );

        $this->assertEqualsWithDelta($reference['huber'], $result, 2e-6);
    }

    public static function emptyDatasetProvider(): array
    {
        return [
            'trimmedMean' => [fn() => CentralTendencyEngine::trimmedMean([])],
            'winsorizedMean' => [fn() => CentralTendencyEngine::winsorizedMean([])],
            'huberMean' => [fn() => CentralTendencyEngine::huberMean([])],
        ];
    }

    public static function singleElementProvider(): array
    {
        return [
            'trimmedMean' => [fn() => CentralTendencyEngine::trimmedMean(self::SINGLE_VALUE)],
            'winsorizedMean' => [fn() => CentralTendencyEngine::winsorizedMean(self::SINGLE_VALUE)],
            'huberMean' => [fn() => CentralTendencyEngine::huberMean(self::SINGLE_VALUE)],
        ];
    }

    private static function getRReference(): array
    {
        if (self::$rReference !== null) {
            return self::$rReference;
        }

        $scriptPath = realpath(__DIR__ . '/verify_means.R');
        if ($scriptPath === false) {
            throw new \RuntimeException('R verification script not found.');
        }

        $command = sprintf('Rscript %s 2>&1', escapeshellarg($scriptPath));
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \RuntimeException(
                'Rscript failed to run verify_means.R. Output: ' . trim(implode("\n", $output))
            );
        }

        $json = trim(implode("\n", $output));
        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            throw new \RuntimeException('Invalid JSON from verify_means.R.');
        }

        foreach (['trimmed', 'winsorized', 'huber', 'huber_k', 'huber_tol'] as $key) {
            if (!array_key_exists($key, $decoded)) {
                throw new \RuntimeException(sprintf('Missing key in R output: %s', $key));
            }
        }

        self::$rReference = $decoded;

        return self::$rReference;
    }

    private function skipIfRUnavailable(): void
    {
        $output = [];
        $exitCode = 0;
        exec('command -v Rscript', $output, $exitCode);
        if ($exitCode !== 0) {
            $this->markTestSkipped('Rscript not available; install R to run cross-validation tests.');
        }

        if (!$this->hasRPackage('DescTools')) {
            $this->markTestSkipped(
                'Missing R package DescTools. Install with: sudo apt-get install -y r-cran-desctools ' .
                'OR R -q -e "install.packages(\"DescTools\", repos=\"https://cloud.r-project.org\")"'
            );
        }

        if (!$this->hasRPackage('MASS')) {
            $this->markTestSkipped(
                'Missing R package MASS. Install with: sudo apt-get install -y r-cran-mass ' .
                'OR R -q -e "install.packages(\"MASS\", repos=\"https://cloud.r-project.org\")"'
            );
        }
    }

    private function hasRPackage(string $package): bool
    {
        $command = sprintf(
            'Rscript -e %s',
            escapeshellarg(sprintf("if (!requireNamespace('%s', quietly=TRUE)) quit(status=1)", $package))
        );
        $exitCode = 0;
        exec($command, $output, $exitCode);
        return $exitCode === 0;
    }
}
