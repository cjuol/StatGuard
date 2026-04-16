# Changelog
[English] | [Español](CHANGELOG.es.md)

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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