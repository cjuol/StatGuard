# FAQ

## Why do my results differ from R

- Verify that the quantile type is the same (1-9).
- Make sure you sort and clean the data in the same way.
- Confirm you are using the same decimals and rounding.

## What minimum dataset size do I need

There is no hard minimum, but:
- With fewer than 5-7 values, variance is unstable.
- To detect outliers, more than 20 values are recommended.

## When to use Huber vs median

- Huber keeps efficiency when data is clean.
- Median is more resilient when extremes are aggressive.

## Can I export results for audits

Yes. Use `toJson()` or `toCsv()` in ClassicStats or RobustStats.
