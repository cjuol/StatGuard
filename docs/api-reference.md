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

$data = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

$q7 = QuantileEngine::calculate($data, 0.75, 7);
```

### StatsComparator

```php
use Cjuol\StatGuard\StatsComparator;

$comparator = new StatsComparator();
$data = [10, 12, 11, 15, 10, 1000];

$analysis = $comparator->analyze($data);
echo $analysis['verdict'];
```

## Parameter ranges

Robust tuning knobs have well-defined domains. Values outside them either throw or are clamped — never silently accepted.

### `trimPercentage` (for `getTrimmedMean` / `getWinsorizedMean`)

- **Default**: `0.10` (trim 10% from each tail).
- **Library range**: `[0.0, 0.5)`. Exactly `0.0` is allowed and skips trimming; `0.5` or above is rejected with `InvalidDataSetException` (no central data would remain).
- **Demo HTTP (`POST /api.php`) clamp**: values outside `[0.0, 0.45]` are silently clamped to that window. `0.45` is a safety margin below the library's hard upper bound.
- **Interpretation**: fraction removed from *each* tail. `0.10` drops 10% lowest + 10% highest → 80% central mean.

### `huberK` (for `getHuberMean`)

- **Default**: `1.345`. This constant yields ~95% asymptotic efficiency at the normal distribution while retaining resistance to outliers (Huber, 1964).
- **Library range**: any positive float. No upper bound enforced. Values `<= 0` produce undefined behaviour — do not pass them.
- **Demo HTTP clamp**: values below `0.1` are clamped to `0.1`; no upper clamp. Very large `huberK` (e.g. `1e6`) effectively turns the Huber M-estimator into the arithmetic mean, since no point is ever classified as an outlier.
- **Interpretation**: the cut-off where the Huber loss switches from quadratic to linear is `k × scale`, where `scale = MAD × 1.4826`. Smaller `k` → more points winsorised → more robust but less efficient on clean data.

### Internal Huber iteration constants

These are not user-configurable via the public facades, but documented for transparency (see `CentralTendencyEngine`):

- `DEFAULT_HUBER_MAX_ITERATIONS = 50`
- `DEFAULT_HUBER_TOLERANCE = 0.001`

Convergence is typically reached in 5–10 iterations on clean data. The 50-iteration cap is a safety net, not an expected working point.
