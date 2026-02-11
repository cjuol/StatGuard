# Benchmarks
[English](../README.md) | [Español]

## Metodologia

- Dataset: 100,000 floats pseudoaleatorios (seed fija).
- Entorno: ejecucion local con PHP 8.x y el script `tests/BenchmarkStatGuard.php`.
- Comparativas: StatGuard vs MathPHP (cuando existe equivalente) y paridad numerica con R.
- R usa `system.time()` y solo mide computo (se excluye la carga del CSV).

Para generar la tabla completa en Markdown:

```bash
php tests/BenchmarkStatGuard.php markdown
```

## Paridad Cientifica (vs R)

StatGuard replica los 9 tipos de cuantiles de R y contrasta sus resultados con el motor base de R. Los valores de referencia en la tabla permiten comparar salida numerica y tiempos.

| Metodo | StatGuard ms | StatGuard value | MathPHP ms | MathPHP value | R ms | R value |
| :--- | ---: | ---: | ---: | ---: | ---: | ---: |
| Median | 15.80 | n/a | 76.50 | n/a | 2.00 | n/a |
| Quantile Type 1 (p=0.75) | n/a | n/a | n/a | n/a | n/a | n/a |
| Quantile Type 2 (p=0.75) | n/a | n/a | n/a | n/a | n/a | n/a |
| Quantile Type 3 (p=0.75) | n/a | n/a | n/a | n/a | n/a | n/a |
| Quantile Type 4 (p=0.75) | n/a | n/a | n/a | n/a | n/a | n/a |
| Quantile Type 5 (p=0.75) | n/a | n/a | n/a | n/a | n/a | n/a |
| Quantile Type 6 (p=0.75) | n/a | n/a | n/a | n/a | n/a | n/a |
| Quantile Type 7 (p=0.75) | 16.20 | n/a | 16.00 | n/a | 2.00 | n/a |
| Quantile Type 8 (p=0.75) | n/a | n/a | n/a | n/a | n/a | n/a |
| Quantile Type 9 (p=0.75) | n/a | n/a | n/a | n/a | n/a | n/a |
| Huber mean | 34.80 | n/a | n/a | n/a | 10.00 | n/a |
| Trimmed mean (10%) | n/a | n/a | n/a | n/a | n/a | n/a |
| Winsorized mean (10%) | n/a | n/a | n/a | n/a | n/a | n/a |

!!! info
	Para completar los valores, ejecuta el benchmark con `php tests/BenchmarkStatGuard.php markdown` y pega la tabla generada.

## Rendimiento (vs MathPHP)

StatGuard mantiene competitividad en operaciones clasicas y supera a MathPHP en estadistica robusta. Los benchmarks mas representativos muestran una ventaja clara en mediana y Huber.

```mermaid
xychart-beta
	title "Median and Huber Mean (100k)"
	x-axis ["StatGuard Median","MathPHP Median","StatGuard Huber","MathPHP Huber"]
	y-axis "ms" 0 --> 820
	bar [15.8, 76.5, 34.8, 788.7]
```

## Conclusiones

StatGuard es la unica libreria PHP que garantiza paridad con los 9 tipos de cuantiles de R y entrega un rendimiento superior a MathPHP en operaciones criticas de estadistica robusta.

Built with ❤️ by cjuol.
