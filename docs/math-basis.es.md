# Fundamentos matematicos

Esta seccion explica el "por que" con un enfoque simple. Si quieres recetas, ve a Tutoriales. Si buscas formulas completas, continua aqui.

## Huber M-Estimator

Idea simple: la media clasica da demasiado peso a valores extremos. Huber mantiene el centro estable y aplica un freno gradual a los outliers.

Ejemplo rapido:

```text
[10, 12, 11, 15, 10, 1000]
```

La media sube demasiado, pero Huber se mantiene cerca del centro.

Definicion:

$$
\rho_k(r) = \begin{cases}
\frac{1}{2} r^2 & \text{si } |r| \le k \\
k\left(|r| - \frac{1}{2} k\right) & \text{si } |r| > k
\end{cases}
$$

Interpretacion:
- Cerca del centro, se comporta como la media (cuadratica).
- En las colas, se vuelve lineal para reducir el impacto.

## MAD escalado

MAD mide dispersion alrededor de la mediana. Para comparar con desviacion estandar, se escala asi:

$$
\sigma_{robust} = MAD \times 1.4826
$$

Esto lo hace comparable bajo distribuciones normales.

## Coeficiente de variacion robusto

En lugar de la media, se usa la mediana para evitar inflar la variabilidad:

$$
CV_r = \left(\frac{\sigma_{robust}}{|\tilde{x}|}\right) \times 100
$$

## Cuantiles de R (Hyndman & Fan)

Para un conjunto ordenado $x_{(1)} \le \dots \le x_{(n)}$, los cuantiles siguen reglas definidas por $p_k$ y por los parametros $(a, b)$:

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
	Si no sabes cual usar, el tipo 7 es el comportamiento por defecto de R.
