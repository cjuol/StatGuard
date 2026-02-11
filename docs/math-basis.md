# Fundamentos matematicos

StatGuard aplica estadistica robusta para evitar que el ruido y los outliers distorsionen las conclusiones.

## Huber M-Estimator

El estimador de Huber es un compromiso entre la media y la mediana. Minimiza una perdida cuadratica cerca del centro y lineal en las colas, lo que reduce la influencia de valores extremos sin perder eficiencia cuando los datos son limpios.

En telemetria con ruido, el Huber M-Estimator es preferible porque mantiene sensibilidad al centro sin dejar que picos espurios dicten el resultado. Es ideal para series con mediciones intermitentes o sensores con drift.

$$
\rho_k(r) = \begin{cases}
\frac{1}{2}r^2 & |r| \le k \\
k\left(|r| - \frac{1}{2}k\right) & |r| > k
\end{cases}
$$

!!! info
	Cuando hay ruido o mediciones anomales, el Huber M-Estimator mantiene estabilidad donde la media clasica se sesga.

## Cuantiles de R (Hyndman & Fan)

Para un conjunto ordenado $x_{(1)} \le \dots \le x_{(n)}$, los cuantiles se calculan con reglas definidas por $p_k$ y por los parametros $(a, b)$:

$$
p_k = \frac{k - a}{n + b}
$$

La interpolacion lineal se aplica entre $x_{(j)}$ y $x_{(j+1)}$ cuando $p$ cae entre posiciones. StatGuard implementa los 9 tipos usados por R.

| Tipo | $p_k$ | $a$ | $b$ | Nota |
| :---: | :--- | :---: | :---: | :--- |
| 1 | $k / n$ | 0 | 0 | Inversa de la CDF empirica (discontinua). |
| 2 | $k / n$ | 0 | 0 | Promedia en discontinuidades. |
| 3 | $(k - 0.5) / n$ | -0.5 | 0 | Estadistico de orden mas cercano. |
| 4 | $k / n$ | 0 | 1 | Interpolacion lineal de CDF. |
| 5 | $(k - 0.5) / n$ | 0.5 | 0.5 | Hazen (1914). |
| 6 | $k / (n + 1)$ | 0 | 1 | Weibull (1939). |
| 7 | $(k - 1) / (n - 1)$ | 1 | 1 | Default de R, modo de $F(x)$. |
| 8 | $(k - 1/3) / (n + 1/3)$ | 1/3 | 1/3 | Mediana-no-sesgada. |
| 9 | $(k - 3/8) / (n + 1/4)$ | 3/8 | 3/8 | Normal-no-sesgada. |

!!! success
	Los cuantiles tipo 7 son el comportamiento por defecto de R y el mas comun en analisis exploratorio.

## Benchmark (100,000 elementos)

| Metrica (100k) | StatGuard (ms) | MathPHP (ms) | R (ms) | Relacion (PHP/R) |
| :--- | ---: | ---: | ---: | ---: |
| Mediana | 15.85 | 76.55 | 2.00 | 7.92 |
| Cuantil tipo 7 (p=0.75) | 16.19 | 16.03 | 2.00 | 8.09 |
| Media de Huber | 34.76 | 788.71 | 10.00 | 3.48 |
