# 🛡️ StatGuard: Robust Statistics & Data Integrity for PHP
[![Latest Version on Packagist](https://img.shields.io/packagist/v/cjuol/statguard.svg?style=flat-square)](https://packagist.org/packages/cjuol/statguard)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![PHP Tests](https://github.com/cjuol/statguard/actions/workflows/php-tests.yml/badge.svg)](https://github.com/cjuol/statguard/actions)

StatGuard is a robust statistical analysis suite for PHP. It compares classic statistics against robust statistics to detect bias, noise, and measurement anomalies in a fully automated way.

## 💡 Motivation

In domains like sports tracking or telemetry, data often includes noise (sensor glitches, exceptional days). Classic statistics (mean) can break under a single extreme value. StatGuard acts as a data quality filter, telling you when you can trust the mean and when you should rely on the robustness of the median and MAD.

## 🚀 Highlights

- **ClassicStats**: Full classic descriptive statistics implementation.
- **StatsComparator**: The analysis core that evaluates data fidelity and issues a verdict.
- **ExportableTrait**: First-class CSV and JSON exports for every stats class.
- **Traits + Interfaces**: Built-in data validation and extensible architecture.

## 🛠 Installation

```bash
composer require cjuol/statguard
```

## 📖 Usage

### 1. Comparator (Bias Detection)

The most powerful tool in the suite. It detects when classic mean-based metrics are distorted by outliers.

```php
use Cjuol\StatGuard\StatsComparator;

$comparator = new StatsComparator();
$data = [10, 12, 11, 15, 10, 1000]; // 1000 is noise

$analysis = $comparator->analyze($data);

echo $analysis['verdict'];
// ALERT: Data is highly influenced by outliers. Use robust metrics.
```

### 2. Instant Export

Any stats class can generate reports for download or API responses:

```php
use Cjuol\StatGuard\RobustStats;

$robust = new RobustStats();

// Generate a CSV for spreadsheets
file_put_contents('report.csv', $robust->toCsv($data));

// Or JSON for your frontend
echo $robust->toJson($data);
```

### 3. Summary Keys (Classic vs Robust)

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

## 📊 Metrics Comparison

| Metric | ClassicStats | RobustStats | Outlier Impact |
| :--- | :--- | :--- | :--- |
| Center | Mean | Median | High in classic |
| Dispersion | Standard Deviation | MAD (Scaled) | Extreme in classic |
| Variability | CV% | Robust CV% | Very high in classic |
| Exportable | ✅ Yes | ✅ Yes | - |

## 📌 Implemented Methods

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

## 🧪 Mathematical Basis

### Scaled Robust Deviation

To keep comparisons fair, MAD is scaled to be comparable to standard deviation under normal distributions:

$$\sigma_{robust} = MAD \times 1.4826$$

### Robust Coefficient of Variation ($CV_r$)

Calculated over the median to avoid a single extreme value inflating volatility:

$$CV_r = \left( \frac{\sigma_{robust}}{|\tilde{x}|} \right) \times 100$$

## ✅ R Compatibility & Accuracy

StatGuard is bit-for-bit compatible with R v4.x for quantile calculations, using Type 7 as the default quantile definition (the same default as `quantile()` in R). Robust central tendency methods (trimmed mean, winsorized mean, and Huber M-estimator) are validated with R comparisons and scripting utilities included in the repository.

## 🚦 Tests and Quality

Validated with PHPUnit for full coverage of calculations and data validation.

```bash
./vendor/bin/phpunit tests
```

## 📄 License

This project is licensed under the MIT License. See LICENSE for details.

Built with care by cjuol.
