# 🛡️ StatGuard: Robust Statistics & Data Integrity for PHP
[English] | [Español](docs/index.es.md)

[![Stable](https://img.shields.io/github/v/release/cjuol/statguard?color=brightgreen&label=stable)](https://github.com/cjuol/statguard/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![PHP Tests](https://github.com/cjuol/statguard/actions/workflows/php-tests.yml/badge.svg)](https://github.com/cjuol/statguard/actions)
[![Performance](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/cjuol/414f8bf15fbe9503c332a5c0a57a699f/raw/statguard-perf.json)](https://gist.github.com/cjuol/414f8bf15fbe9503c332a5c0a57a699f)
[![R-Compatibility](https://img.shields.io/badge/R-compatibility-blue?style=flat-square)](https://cran.r-project.org/)
[![PHP 8.x](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square)](https://www.php.net/)

StatGuard is a robust statistical analysis suite for PHP focused on scientific precision and data integrity. It compares classic statistics against robust statistics to detect bias, noise, and measurement anomalies in a fully automated way.

## Why StatGuard

Outliers are inevitable in telemetry, finance, sports tracking, and lab measurements. A single extreme value can pull the arithmetic mean far from the central mass, which biases decisions that depend on it. StatGuard provides robust estimators (median, MAD, trimmed and winsorized means, Huber M-estimator) that stay stable under contamination so you can trust summaries even when the data is messy.

## Highlights

- **ClassicStats**: Full classic descriptive statistics implementation.
- **StatsComparator**: The analysis core that evaluates data fidelity and issues a verdict.
- **ExportableTrait**: First-class CSV and JSON exports for every stats class.
- **Traits + Interfaces**: Built-in data validation and extensible architecture.
- **Independent engines**: `QuantileEngine` and `CentralTendencyEngine` keep core math isolated and reusable.
- **R parity**: Quantiles and robust means are validated against R outputs.

## Features

- 9 R-compatible quantile types (Hyndman & Fan 1-9).
- Robust means: Huber, winsorized, and trimmed.

## Installation

Install via Composer:

```bash
composer require cjuol/statguard
```

## Usage

### Robust Estimators (Quick Start)

```php
use Cjuol\StatGuard\RobustStats;

$stats = new RobustStats();
$data = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 1000];

$huber = $stats->getHuberMean($data);
$winsorized = $stats->getWinsorizedMean($data, 0.1);
$iqr = $stats->getIqr($data, RobustStats::TYPE_R_DEFAULT);
```

Robust estimators stay stable even with extreme outliers:

| Metric | Result | Comment |
| :--- | :--- | :--- |
| Arithmetic Mean | 95.9091 | Pulled up by the outlier |
| Huber Mean | 6.0982 | Stays close to the central mass |

### Example: Huber Mean

```php
use Cjuol\StatGuard\RobustStats;

$robust = new RobustStats();
$data = [10, 12, 11, 15, 10, 1000];

$huber = $robust->getHuberMean($data, 1.345, 50, 0.001);
```

### Example: Winsorized Mean (R-Compatible Quantile Type)

```php
use Cjuol\StatGuard\RobustStats;

$robust = new RobustStats();
$data = [10, 12, 11, 15, 10, 1000];

// Type 7 matches R's default quantile() behavior.
$winsorized = $robust->getWinsorizedMean($data, 0.1, 7);
```

### Comparator (Bias Detection)

```php
use Cjuol\StatGuard\StatsComparator;

$comparator = new StatsComparator();
$data = [10, 12, 11, 15, 10, 1000];

$analysis = $comparator->analyze($data);

echo $analysis['verdict'];
// ALERT: Data is highly influenced by outliers. Use robust metrics.
```

### Instant Export

```php
use Cjuol\StatGuard\RobustStats;

$robust = new RobustStats();

file_put_contents('report.csv', $robust->toCsv($data));
echo $robust->toJson($data);
```

### Summary Keys (Classic vs Robust)

Classic summary keys:

```php
[
	'mean',
	'median',
	'stdDev',
	'sampleVariance',
	'cv',
	'outliersZScore',
	'count'
]
```

Robust summary keys:

```php
[
	'mean',
	'median',
	'robustDeviation',
	'robustVariance',
	'robustCv',
	'iqr',
	'mad',
	'outliers',
	'confidenceIntervals',
	'count'
]
```

## Metrics Comparison

| Metric | ClassicStats | RobustStats | Outlier Impact |
| :--- | :--- | :--- | :--- |
| Center | Mean | Median | High in classic |
| Dispersion | Standard Deviation | MAD (Scaled) | Extreme in classic |
| Variability | CV% | Robust CV% | Very high in classic |
| Exportable | ✅ Yes | ✅ Yes | - |

## R Quantile Types (1-9)

StatGuard matches R v4.x quantile definitions. The table below summarizes the nine Hyndman & Fan (1996) types supported by `quantile()`.

| Type | $p_k$ | $a$ | $b$ | Notes |
| :--- | :--- | :--- | :--- | :--- |
| 1 | $k / n$ | 0 | 0 | Inverse of empirical CDF (discontinuous). |
| 2 | $k / n$ | 0 | 0 | Averaged at discontinuities. |
| 3 | $(k - 0.5) / n$ | -0.5 | 0 | Nearest order statistic. |
| 4 | $k / n$ | 0 | 1 | Linear interpolation of CDF. |
| 5 | $(k - 0.5) / n$ | 0.5 | 0.5 | Hazen (1914). |
| 6 | $k / (n + 1)$ | 0 | 1 | Weibull (1939). |
| 7 | $(k - 1) / (n - 1)$ | 1 | 1 | R default, mode of $F(x)$. |
| 8 | $(k - 1/3) / (n + 1/3)$ | 1/3 | 1/3 | Median-unbiased. |
| 9 | $(k - 3/8) / (n + 1/4)$ | 3/8 | 3/8 | Normal-unbiased. |

## Implemented Methods

### ClassicStats

- `getMean(array $data): float`
- `getMedian(array $data): float`
- `getDeviation(array $data): float`
- `getStandardDeviation(array $data): float`
- `getCoefficientOfVariation(array $data): float`
- `getSampleVariance(array $data): float`
- `getPopulationVariance(array $data): float`
- `getOutliers(array $data): array`
- `getSummary(array $data, bool $sort = true, int $decimals = 2): array`
- `toJson(array $data, int $options = JSON_PRETTY_PRINT): string`
- `toCsv(array $data, string $delimiter = ","): string`

### RobustStats

- `getMean(array $data): float`
- `getMedian(array $data): float`
- `getDeviation(array $data): float`
- `getCoefficientOfVariation(array $data): float`
- `getRobustDeviation(array $data): float`
- `getRobustCv(array $data): float`
- `getRobustVariance(array $data): float`
- `getIqr(array $data): float`
- `getMad(array $data): float`
- `getOutliers(array $data): array`
- `getConfidenceIntervals(array $data): array`
- `getTrimmedMean(array $data, float $trimPercentage = 0.1): float`
- `getWinsorizedMean(array $data, float $trimPercentage = 0.1, int $type = 7): float`
- `getHuberMean(array $data, float $k = 1.345, int $maxIterations = 50, float $tolerance = 0.001): float`
- `getSummary(array $data, bool $sort = true, int $decimals = 2): array`
- `toJson(array $data, int $options = JSON_PRETTY_PRINT): string`
- `toCsv(array $data, string $delimiter = ","): string`

### StatsComparator

- `__construct(?RobustStats $robust = null, ?ClassicStats $classic = null)`
- `analyze(array $data, int $decimals = 2): array`

## Mathematical Basis

### Scaled Robust Deviation

To keep comparisons fair, MAD is scaled to be comparable to standard deviation under normal distributions:

$$\sigma_{robust} = MAD \times 1.4826$$

### Robust Coefficient of Variation ($CV_r$)

Calculated over the median to avoid a single extreme value inflating volatility:

$$CV_r = \left( \frac{\sigma_{robust}}{|\tilde{x}|} \right) \times 100$$

## R Compatibility & Accuracy

Every public statistic is tested against R v4.x outputs to ensure scientific accuracy. Quantile calculations use Type 7 by default (the same default as `quantile()` in R), and robust central tendency methods (trimmed mean, winsorized mean, Huber M-estimator) are verified via R comparison scripts in the repository.

## Docker Profiles (Optional R Validation)

StatGuard does not require R for normal usage. The default container is lightweight and focused on PHP development. For scientific auditing, you can enable the `r-validation` profile to run the R comparison script.

```bash
# Default dev container (no R runtime)
docker compose up -d

# Run tests in the default container
composer run test

# Run R validation in the heavy profile
composer run validate-r
```

## Performance Benchmarks (StatGuard vs MathPHP vs R)

Up to 5x faster than MathPHP in median calculations.

20x faster than MathPHP in robust mean estimation.

Dataset: 100,000 random floats. Benchmarks executed in the Docker performance profile using `docker compose --profile performance run --rm benchmark report`. R timings use `system.time()` and only measure computation (file load excluded).

Use `json` only when you need the shield data output (it does not update the markdown tables).

### Scientific Parity (vs R)

Status shows ✅ when the absolute difference between StatGuard and R is below 0.0001.

Generate or refresh the table with `php tests/BenchmarkStatGuard.php report`.

<!-- BENCHMARK_PARITY_START -->
| Method | StatGuard ms | StatGuard value | MathPHP ms | MathPHP value | R ms | R value | Status |
| :--- | ---: | ---: | ---: | ---: | ---: | ---: | :---: |
| Median | - | - | - | - | - | - | ❌ |
| Quantile Type 1 (p=0.75) | - | - | - | - | - | - | ❌ |
| Quantile Type 2 (p=0.75) | - | - | - | - | - | - | ❌ |
| Quantile Type 3 (p=0.75) | - | - | - | - | - | - | ❌ |
| Quantile Type 4 (p=0.75) | - | - | - | - | - | - | ❌ |
| Quantile Type 5 (p=0.75) | - | - | - | - | - | - | ❌ |
| Quantile Type 6 (p=0.75) | - | - | - | - | - | - | ❌ |
| Quantile Type 7 (p=0.75) | - | - | - | - | - | - | ❌ |
| Quantile Type 8 (p=0.75) | - | - | - | - | - | - | ❌ |
| Quantile Type 9 (p=0.75) | - | - | - | - | - | - | ❌ |
| Huber mean | - | - | - | - | - | - | ❌ |
<!-- BENCHMARK_PARITY_END -->

| Metric (100k) | StatGuard ms | MathPHP ms | R ms | Ratio (PHP/R) |
| :--- | ---: | ---: | ---: | ---: |
| Median | 15.8 | 76.5 | 2.00 | 7.92 |
| Quantile Type 7 (p=0.75) | 16.2 | 16.0 | 2.00 | 8.09 |
| Huber mean | 34.8 | 788.7 | 10.00 | 3.48 |

Precision check (Huber): $\Delta = 0.0056111266$ for $n = 100000$ (warning threshold $10^{-10}$). Smaller datasets showed higher deltas, which are reported by the benchmark warnings.

Consistent results with R core within 0.01% tolerance on the benchmark scale (0-1000).

## Tests and Quality

Validated with PHPUnit for full coverage of calculations and data validation.

```bash
./vendor/bin/phpunit tests
```

## License

This project is licensed under the MIT License. See LICENSE for details.

Built with ❤️ by cjuol.
