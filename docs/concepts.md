# Concepts (plain language)

These ideas help you pick the right statistic without heavy theory.

## Mean vs median vs Huber

Think of 6 measurements and one big error:

```text
[10, 12, 11, 15, 10, 1000]
```

- Mean: rises a lot because of 1000.
- Median: stays at the real center.
- Huber: behaves like the mean when data is clean, but "brakes" outliers.

Simple rule:
- Use the mean if data is clean.
- Use median or Huber if there are outliers.

## MAD and IQR

- MAD (Median Absolute Deviation) measures dispersion around the median.
- IQR (Interquartile Range) measures the spread between 25% and 75%.

If MAD or IQR are high, there is lots of noise or long tails.

## R quantiles

R defines 9 ways to compute quantiles. StatGuard implements all of them.

- Type 7: R default. Good balance.
- Types 1-3: more discrete (less interpolation).
- Types 8-9: bias adjustments for certain distributions.

If you are unsure, start with type 7.
