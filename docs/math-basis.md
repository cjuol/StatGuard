# Mathematical basis

This section explains the "why" with a simple approach. If you want recipes, go to Tutorials. If you want full formulas, keep reading.

## Huber M-Estimator

Simple idea: the classic mean gives too much weight to extreme values. Huber keeps the center stable and applies a gradual brake to outliers.

Quick example:

```text
[10, 12, 11, 15, 10, 1000]
```

The mean jumps too much, but Huber stays near the center.

Definition:

$$
\rho_k(r) = \begin{cases}
\frac{1}{2} r^2 & \text{if } |r| \le k \\
k\left(|r| - \frac{1}{2} k\right) & \text{if } |r| > k
\end{cases}
$$

Interpretation:
- Near the center, it behaves like the mean (quadratic).
- In the tails, it becomes linear to reduce impact.

## Scaled MAD

MAD measures dispersion around the median. To compare it with standard deviation, scale it as follows:

$$
\sigma_{robust} = MAD \times 1.4826
$$

This makes it comparable under normal distributions.

## Robust coefficient of variation

Instead of the mean, use the median to avoid inflating variability:

$$
CV_r = \left(\frac{\sigma_{robust}}{|\tilde{x}|}\right) \times 100
$$

## R quantiles (Hyndman & Fan)

For an ordered set $x_{(1)} \le \dots \le x_{(n)}$, quantiles follow rules defined by $p_k$ and parameters $(a, b)$:

$$
p_k = \frac{k - a}{n + b}
$$

Linear interpolation applies between $x_{(j)}$ and $x_{(j+1)}$ when $p$ falls between positions. StatGuard implements the 9 types used by R.

| Type | $p_k$ | $a$ | $b$ | Note |
| :---: | :--- | :---: | :---: | :--- |
| 1 | $k / n$ | 0 | 0 | Inverse of the empirical CDF (discontinuous). |
| 2 | $k / n$ | 0 | 0 | Averages at discontinuities. |
| 3 | $(k - 0.5) / n$ | -0.5 | 0 | Nearest order statistic. |
| 4 | $k / n$ | 0 | 1 | Linear interpolation of CDF. |
| 5 | $(k - 0.5) / n$ | 0.5 | 0.5 | Hazen (1914). |
| 6 | $k / (n + 1)$ | 0 | 1 | Weibull (1939). |
| 7 | $(k - 1) / (n - 1)$ | 1 | 1 | R default, mode of $F(x)$. |
| 8 | $(k - 1/3) / (n + 1/3)$ | 1/3 | 1/3 | Median-unbiased. |
| 9 | $(k - 3/8) / (n + 1/4)$ | 3/8 | 3/8 | Normal-unbiased. |

!!! success
	If you are unsure, type 7 is the default behavior in R.
