# Changelog
[English](CHANGELOG.md) | [Español](CHANGELOG.es.md)

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-02-10

### Added
- **ClassicStats**: Classic descriptive statistics (mean, variance, standard deviation, CV).
- **RobustStats**: Robust estimators (median, MAD, trimmed mean, winsorized mean, Huber M-estimator).
- **QuantileEngine**: R-compatible quantiles (types 1-9) with defaults matching R.
- **StatsComparator**: Bias detection between classic and robust metrics.
- **ExportableTrait**: CSV/JSON exports for all stats classes.
- **DataProcessorTrait**: Centralized validation and normalization of datasets.
- **CentralTendencyEngine**: Shared internal engine for robust central tendency.
- Tests and benchmarks for reproducibility and precision.