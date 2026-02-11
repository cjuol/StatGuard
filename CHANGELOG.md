# Changelog
[English] | [Español](CHANGELOG.es.md)

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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