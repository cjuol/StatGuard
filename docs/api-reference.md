# API reference

The full API, generated with phpDocumentor on every push to `main`, lives at
[**/api/**](../api/index.html). Use this page as a quick map of classes and minimal examples.

!!! info
	The generated site is deployed alongside this documentation on GitHub Pages. If the link above 404s right after a fresh deploy, wait a minute for the Pages cache to refresh.

## Class map

- `ClassicStats`: classic statistics (mean, deviation, variance, outliers).
- `RobustStats`: robust statistics (Huber, MAD, IQR, robust CV).
- `QuantileEngine`: R-compatible quantiles types 1-9.
- `CentralTendencyEngine`: median, Huber, and robust means.
- `StatsComparator`: bias verdict between classic and robust.

## Minimal examples

### ClassicStats

```php
use Cjuol\StatGuard\ClassicStats;

$classic = new ClassicStats();
$data = [1, 2, 3, 4, 5];

$mean = $classic->getMean($data);
$summary = $classic->getSummary($data);
```

### RobustStats

```php
use Cjuol\StatGuard\RobustStats;

$robust = new RobustStats();
$data = [1, 2, 3, 4, 5, 1000];

$huber = $robust->getHuberMean($data);
$iqr = $robust->getIqr($data, RobustStats::TYPE_R_DEFAULT);
```

### QuantileEngine

```php
use Cjuol\StatGuard\QuantileEngine;

$engine = new QuantileEngine();
$data = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

$q7 = $engine->quantile($data, 0.75, 7);
```

### StatsComparator

```php
use Cjuol\StatGuard\StatsComparator;

$comparator = new StatsComparator();
$data = [10, 12, 11, 15, 10, 1000];

$analysis = $comparator->analyze($data);
echo $analysis['verdict'];
```
