# FAQ

## Why do my results differ from R

- Verify that the quantile type is the same (1–9). R defaults to type 7; StatGuard exposes all nine.
- Make sure you sort and clean the data in the same way (NaN/Inf filtering, string coercion).
- Confirm you are using the same decimal precision and rounding policy.
- For Huber, check the tuning constant `k` (StatGuard uses `1.345` like `MASS::huber`).

## What minimum dataset size do I need

There is no hard minimum, but:

- With fewer than 5–7 values, variance is unstable and robust estimators degenerate.
- To detect outliers reliably, 20+ values are recommended.
- For Huber M-estimator to converge cleanly, aim for 30+ points.

## When should I use Huber instead of the median

- **Huber M-estimator**: keeps the efficiency of the mean when data is mostly clean but has a handful of extremes. Good default for monitoring/telemetry.
- **Median**: maximum robustness when a large fraction (up to 50%) of the data is contaminated. Use when outliers are the norm, not the exception.
- **Trimmed mean**: when you know the contamination rate upfront (e.g., "drop top/bottom 10%").
- **Winsorized mean**: when you want to keep the sample size but cap extreme influence.

## Why use MAD instead of the standard deviation

Standard deviation squares deviations, so a single outlier can inflate it by orders of magnitude. MAD (Median Absolute Deviation) uses the median of `|x - median(x)|`, scaled by `1.4826` for Gaussian consistency. It has a 50% breakdown point vs. 0% for stddev — one bad sample cannot distort it.

## Which quantile type should I pick

- **Type 7** (default in R/NumPy): linear interpolation, best general-purpose choice.
- **Types 1–3**: discrete, preserve sample values. Good for audit/forensic reports where you need an actual observed value.
- **Types 4–9**: continuous, interpolated. Types 8 and 9 are recommended by Hyndman & Fan (1996) for unbiased estimates on small samples.

## Can I export results for audits

Yes. Call `toJson()` or `toCsv()` on `ClassicStats` or `RobustStats`. Both methods include every computed statistic along with the input dataset signature so results are reproducible.

## Does StatGuard mutate my input array

No. All operations work on a defensive copy. You can pass the same array to several methods safely.

## How does the bias verdict work

`StatsComparator` compares classic vs. robust estimators and emits a verdict (`CLEAN`, `NOISE`, `BIAS`) based on the relative gap between `mean` and `huberMean` (default threshold 5%). Override the threshold in the constructor for stricter or looser auditing.

## Does StatGuard require R to run

No. R is only used in the validation script (`scripts/validate_with_r.php`) to cross-check numerical parity during development and CI. The library itself has no external language dependency.

## How do I regenerate the benchmark tables

```bash
php tests/BenchmarkStatGuard.php report
```

This updates `README.md` and `docs/benchmarks.md` with fresh measurements. Requires R installed locally or run via `docker compose --profile performance up benchmark`.
