# Changelog
[English] | [Español](CHANGELOG.es.md)

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.1] - 2026-04-16

Patch release closing all ALTO/MEDIO/BAJO items from the full audit (`docs/audits/2026-04-16-full-audit.md`). No API changes.

### Security
- **SEC-01** Cap `POST /api.php` payloads at 50 000 data points; reject larger payloads with HTTP 413.
- **SEC-02** Demo no longer leaks `Throwable::getMessage()` content in 500 responses; errors are logged server-side and surfaced as generic `"Internal error."`.
- **SEC-03** `CentralTendencyEngine::normalizeData` now rejects NaN/Inf via `is_finite`, restoring the v2.0.0 contract across every code path.
- **SEC-06** SRI hashes (SHA-384) + `crossorigin=anonymous` + `referrerpolicy=no-referrer` on the three jsDelivr scripts in the playground.
- **SEC-07** `X-Content-Type-Options: nosniff` on `api.php`; meta CSP on `index.html` restricting script/style/font/connect origins and forbidding framing.

### Fixed
- **QUA-04** `api.php` no longer silently drops NaN/Inf values via `array_filter`. The library now sees the raw payload and returns HTTP 422 via `InvalidDataSetException`.
- **QUA-05** Broken QuantileEngine snippet in `docs/api-reference.md` replaced with the real static API (`QuantileEngine::calculate`).
- **QUA-07** `shield.json` typo (`"5.1h"` → `"5.1x"`) corrected.

### Changed
- **QUA-01** Dataset validation unified in `Cjuol\StatGuard\Support\DataValidator`. The trait and both engines now delegate here instead of duplicating `is_numeric` / `is_finite` checks.
- **PERF-01** `RobustStats::getSummary` reuses pre-computed Q1/Q3/median, going from four quantile calculations to two.
- **PERF-02** `StatsComparator::analyze` inlines the validation/sort pipeline via `QuantileEngine`, skipping redundant sorts on the shared dataset.

### Build & CI
- **SEC-13** Pin `phpdocumentor/phpdocumentor:3.5` and `squidfunk/mkdocs-material:9.5.44` tags in `docker-compose.yml`.
- **CI-01** Pin `exuanbo/actions-deploy-gist@47697fc` (v1.1.4) instead of the moving `@v1` tag in workflows handling `GIST_TOKEN`.
- **QUA-08** Stop tracking the regenerated `statguard-perf.json`; it is now written by the benchmark workflow only.
- **QW-08** Add a minimal `Makefile` (`test`, `test-docker`, `analyse`, `coverage`, `bench`, `clean`, `help`).

### Documentation
- **SEC-04** Document valid ranges of `huberK` (default 1.345, lib-open, demo-clamped ≥ 0.1) and `trimPercentage` (lib `[0.0, 0.5)` vs demo clamp `[0.0, 0.45]`), plus internal Huber iteration constants, in `docs/api-reference.md`.

## [2.0.0] - 2026-04-16

### Breaking
- `Contracts\StatsInterface` now requires `getVariance(array $data): float`. External implementers must add this method.
- `NaN`, `INF` and `-INF` inputs now raise `InvalidDataSetException` instead of silently propagating through aggregations. Pre-filter datasets if you relied on the previous behaviour.

### Added
- `Contracts\ExportableInterface` exposing the `toJson` / `toCsv` surface so callers can type-hint independently of the concrete class.
- Outlier Playground web demo (`web/public/`) with bilingual UI, severity legend, side-by-side Classic vs Robust panels and Nothing-inspired styling.
- `scripts/serve-demo.sh` launcher with native PHP and Docker fallback.
- PHPStan level 5 configuration (`phpstan.neon.dist`) and `composer analyse` script.
- phpDocumentor output published on GitHub Pages with a Docs badge in the README.
- Coverage shield powered by a gist endpoint (`scripts/coverage_shield.php`, PHP 8.3 + pcov CI job).

### Changed
- Median computation unified through `QuantileEngine::medianSorted`; duplicated implementations in `ClassicStats`, `RobustStats` and `CentralTendencyEngine` removed.
- R benchmark (`tests/r_performance.R`) now invokes `MASS::huber` with StatGuard defaults (`k=1.345`, `tol=0.001`) instead of R defaults, restoring Huber parity.

### Fixed
- Three PHPStan level 5 findings in `QuantileEngine` (redundant type guard and two `match` expressions without a default arm).

## [1.1.0] - 2026-02-11

### Added
- Performance benchmark suite (StatGuard vs R vs MathPHP) with ratio reporting.
- R performance script for median, quantile type 7, and Huber mean.
- Performance profile in Docker Compose for repeatable benchmark runs.
- GitHub Actions workflow for the dynamic performance badge.
- Performance certification and R parity reporting for v1.1.0.
- Integrated MathPHP & R benchmarking suite.
- Bilingual documentation with MkDocs & GitHub Pages support.

### Changed
- Benchmark output now includes R timings and precision warnings for Huber mean parity.

## [1.0.0] - 2026-02-11

### Added
- Initial Release.
- Independent internal engines: `QuantileEngine` and `CentralTendencyEngine` for reusable math cores.
- R v4.x parity validated for quantiles and robust central tendency methods.
- **ClassicStats**: Classic descriptive statistics (mean, variance, standard deviation, CV).
- **RobustStats**: Robust estimators (median, MAD, trimmed mean, winsorized mean, Huber M-estimator).
- **StatsComparator**: Bias detection between classic and robust metrics.
- **ExportableTrait**: CSV/JSON exports for all stats classes.
- **DataProcessorTrait**: Centralized validation and normalization of datasets.
- Tests and benchmarks for reproducibility and precision.

### Changed
- N/A (initial release).

### Fixed
- N/A (initial release).

Built with ❤️ by cjuol.