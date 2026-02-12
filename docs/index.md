# 🛡️ StatGuard

[![GitHub Actions](https://github.com/cjuol/statguard/actions/workflows/docs.yml/badge.svg)](https://github.com/cjuol/statguard/actions)
[![Stable](https://img.shields.io/github/v/release/cjuol/statguard?color=brightgreen&label=stable)](https://github.com/cjuol/statguard/releases)
[![License](https://img.shields.io/github/license/cjuol/statguard.svg)](LICENSE)

StatGuard is a robust statistics suite for PHP. It helps you summarize data with outliers without bias and compare classic vs robust results with a clear verdict.

!!! info
	Includes R-compatible quantiles, robust estimators (Huber, MAD, IQR), and audit-ready exports.

## Quick start

Install via Composer:

```bash
composer require cjuol/statguard
```

Minimal example:

```php
use Cjuol\StatGuard\RobustStats;

$stats = new RobustStats();
$data = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 1000];

$mean = $stats->getMean($data);
$huber = $stats->getHuberMean($data);
```

If you want a full workflow, follow the Getting started guide and the tutorials.

## What you can do with StatGuard

- Detect outlier bias with `StatsComparator`.
- Generate robust reports with `RobustStats`.
- Replicate R quantiles (types 1-9).

## Next steps

- Getting started: installation and first result.
- Tutorials: recipes for real cases.
- Concepts: simple foundations before theory.

Built with ❤️ by cjuol.
