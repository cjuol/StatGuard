# 🛡️ StatGuard: Estadistica Robusta e Integridad de Datos para PHP
[English](README.md) | [Español]

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cjuol/statguard.svg?style=flat-square)](https://packagist.org/packages/cjuol/statguard)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![PHP Tests](https://github.com/cjuol/statguard/actions/workflows/php-tests.yml/badge.svg)](https://github.com/cjuol/statguard/actions)
[![Performance](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/cjuol/414f8bf15fbe9503c332a5c0a57a699f/raw/statguard-perf.json)](https://gist.github.com/cjuol/414f8bf15fbe9503c332a5c0a57a699f)
[![R-Compatibility](https://img.shields.io/badge/R-compatibility-blue?style=flat-square)](https://cran.r-project.org/)
[![PHP 8.x](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square)](https://www.php.net/)

StatGuard es una suite de analisis estadistico robusto para PHP enfocada en precision cientifica e integridad de datos. Compara estadistica clasica contra estadistica robusta para detectar sesgo, ruido y anomalias de medicion de forma automatica.

## ¿Por qué StatGuard?

Los valores atipicos son inevitables en telemetria, finanzas, deporte y laboratorios. Un solo valor extremo puede arrastrar la media aritmetica lejos de la masa central y sesgar las decisiones. StatGuard ofrece estimadores robustos (mediana, MAD, medias recortadas y winsorizadas, estimador M de Huber) que se mantienen estables bajo contaminacion, permitiendo confiar en los resumenes aun con datos ruidosos.

## Destacados

- **ClassicStats**: Implementacion completa de estadistica descriptiva clasica.
- **StatsComparator**: Nucleo de analisis que evalua la fidelidad de los datos y emite un veredicto.
- **ExportableTrait**: Exportacion CSV y JSON para cada clase estadistica.
- **Traits + Interfaces**: Validacion integrada y arquitectura extensible.
- **Motores independientes**: `QuantileEngine` y `CentralTendencyEngine` mantienen la matematica central aislada y reutilizable.
- **Paridad con R**: Cuantiles y medias robustas validadas contra resultados de R.

## Caracteristicas

- 9 tipos de cuantiles compatibles con R (Hyndman & Fan 1-9).
- Medias robustas: Huber, winsorizada y recortada.

## Instalacion

Instalacion via Composer:

```bash
composer require cjuol/statguard
```

## Uso

### Estimadores Robustos (Inicio Rapido)

```php
use Cjuol\StatGuard\RobustStats;

$stats = new RobustStats();
$data = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 1000];

$huber = $stats->getHuberMean($data);
$winsorized = $stats->getWinsorizedMean($data, 0.1);
$iqr = $stats->getIqr($data, RobustStats::TYPE_R_DEFAULT);
```

Los estimadores robustos se mantienen estables ante valores atipicos extremos:

| Metrica | Resultado | Comentario |
| :--- | :--- | :--- |
| Media aritmetica | 95.9091 | Sesgada por el valor atipico |
| Media de Huber | 6.0982 | Cerca de la masa central |

### Ejemplo: Media de Huber

```php
use Cjuol\StatGuard\RobustStats;

$robust = new RobustStats();
$data = [10, 12, 11, 15, 10, 1000];

$huber = $robust->getHuberMean($data, 1.345, 50, 0.001);
```

### Ejemplo: Media Winsorizada (Tipo de Cuantil Compatible con R)

```php
use Cjuol\StatGuard\RobustStats;

$robust = new RobustStats();
$data = [10, 12, 11, 15, 10, 1000];

// El tipo 7 coincide con el quantile() por defecto de R.
$winsorized = $robust->getWinsorizedMean($data, 0.1, 7);
```

### Comparator (Deteccion de Sesgo)

```php
use Cjuol\StatGuard\StatsComparator;

$comparator = new StatsComparator();
$data = [10, 12, 11, 15, 10, 1000];

$analysis = $comparator->analyze($data);

echo $analysis['verdict'];
// ALERTA: Los datos estan muy influidos por valores atipicos. Use metricas robustas.
```

### Exportacion Instantanea

```php
use Cjuol\StatGuard\RobustStats;

$robust = new RobustStats();

file_put_contents('report.csv', $robust->toCsv($data));
echo $robust->toJson($data);
```

### Claves de Resumen (Clasico vs Robusto)

Claves clasicas:

```php
[
	'mean',
	'median',
	'stdDev',
	'sampleVariance',
	'cv',
	'outliersZScore',
	'count'
]
```

Claves robustas:

```php
[
	'mean',
	'median',
	'robustDeviation',
	'robustVariance',
	'robustCv',
	'iqr',
	'mad',
	'outliers',
	'confidenceIntervals',
	'count'
]
```

## Comparativa de Metricas

| Metrica | ClassicStats | RobustStats | Impacto del Valor Atipico |
| :--- | :--- | :--- | :--- |
| Centro | Media | Mediana | Alto en clasico |
| Dispersion | Desviacion estandar | MAD (escalado) | Extremo en clasico |
| Variabilidad | CV% | CV% robusto | Muy alto en clasico |
| Exportable | ✅ Si | ✅ Si | - |

## Tipos de Cuantil R (1-9)

StatGuard replica las definiciones de cuantiles de R v4.x. La tabla resume los nueve tipos de Hyndman & Fan (1996) soportados por `quantile()`.

| Tipo | $p_k$ | $a$ | $b$ | Notas |
| :--- | :--- | :--- | :--- | :--- |
| 1 | $k / n$ | 0 | 0 | Inversa de la CDF empirica (discontinua). |
| 2 | $k / n$ | 0 | 0 | Promediado en discontinuidades. |
| 3 | $(k - 0.5) / n$ | -0.5 | 0 | Estadistico de orden mas cercano. |
| 4 | $k / n$ | 0 | 1 | Interpolacion lineal de la CDF. |
| 5 | $(k - 0.5) / n$ | 0.5 | 0.5 | Hazen (1914). |
| 6 | $k / (n + 1)$ | 0 | 1 | Weibull (1939). |
| 7 | $(k - 1) / (n - 1)$ | 1 | 1 | Por defecto de R, modo de $F(x)$. |
| 8 | $(k - 1/3) / (n + 1/3)$ | 1/3 | 1/3 | Mediana-no-sesgado. |
| 9 | $(k - 3/8) / (n + 1/4)$ | 3/8 | 3/8 | Normal-no-sesgado. |

## Metodos Implementados

### ClassicStats

- `getMean(array $data): float`
- `getMedian(array $data): float`
- `getDeviation(array $data): float`
- `getStandardDeviation(array $data): float`
- `getCoefficientOfVariation(array $data): float`
- `getSampleVariance(array $data): float`
- `getPopulationVariance(array $data): float`
- `getOutliers(array $data): array`
- `getSummary(array $data, bool $sort = true, int $decimals = 2): array`
- `toJson(array $data, int $options = JSON_PRETTY_PRINT): string`
- `toCsv(array $data, string $delimiter = ","): string`

### RobustStats

- `getMean(array $data): float`
- `getMedian(array $data): float`
- `getDeviation(array $data): float`
- `getCoefficientOfVariation(array $data): float`
- `getRobustDeviation(array $data): float`
- `getRobustCv(array $data): float`
- `getRobustVariance(array $data): float`
- `getIqr(array $data): float`
- `getMad(array $data): float`
- `getOutliers(array $data): array`
- `getConfidenceIntervals(array $data): array`
- `getTrimmedMean(array $data, float $trimPercentage = 0.1): float`
- `getWinsorizedMean(array $data, float $trimPercentage = 0.1, int $type = 7): float`
- `getHuberMean(array $data, float $k = 1.345, int $maxIterations = 50, float $tolerance = 0.001): float`
- `getSummary(array $data, bool $sort = true, int $decimals = 2): array`
- `toJson(array $data, int $options = JSON_PRETTY_PRINT): string`
- `toCsv(array $data, string $delimiter = ","): string`

### StatsComparator

- `__construct(?RobustStats $robust = null, ?ClassicStats $classic = null)`
- `analyze(array $data, int $decimals = 2): array`

## Base Matematica

### Desviacion Robusta Escalada

Para comparaciones justas, la MAD se escala para ser comparable con la desviacion estandar bajo distribuciones normales:

$$\sigma_{robust} = MAD \times 1.4826$$

### Coeficiente de Variacion Robusta ($CV_r$)

Se calcula sobre la mediana para evitar que un valor extremo infle la volatilidad:

$$CV_r = \left( \frac{\sigma_{robust}}{|\tilde{x}|} \right) \times 100$$

## Compatibilidad con R y Precision

Cada funcion publica se prueba contra los resultados de R v4.x para garantizar precision cientifica. Los cuantiles usan el Tipo 7 por defecto (el mismo de `quantile()` en R), y los metodos de tendencia central robusta (media recortada, winsorizada y estimador M de Huber) se verifican mediante scripts de comparacion con R incluidos en el repositorio.

## Perfiles Docker (Validacion R Opcional)

StatGuard no requiere R para su uso normal. El contenedor por defecto es liviano y esta enfocado en desarrollo PHP. Para auditoria cientifica, puedes habilitar el perfil `r-validation` para ejecutar el script de comparacion con R.

```bash
# Contenedor por defecto (sin runtime R)
docker compose up -d

# Ejecutar tests en el contenedor por defecto
composer run test

# Ejecutar validacion R en el perfil pesado
composer run validate-r
```

## Benchmarks de Rendimiento (StatGuard vs MathPHP vs R)

Hasta 5x mas rapido que MathPHP en calculos de mediana.

20x mas rapido que MathPHP en estimacion de media robusta.

Dataset: 100000 floats aleatorios. Benchmarks ejecutados en el perfil performance con `docker compose --profile performance run --rm benchmark json`. Los tiempos de R usan `system.time()` y miden solo computacion (carga del archivo excluida).

| Metrica (100k) | StatGuard ms | MathPHP ms | R ms | Relación (PHP/R) |
| :--- | ---: | ---: | ---: | ---: |
| Mediana | 15.8 | 76.5 | 2.00 | 7.92 |
| Cuantil Tipo 7 (p=0.75) | 16.2 | 16.0 | 2.00 | 8.09 |
| Media de Huber | 34.8 | 788.7 | 10.00 | 3.48 |

Chequeo de precision (Huber): $\Delta = 0.0056111266$ para $n = 100000$ (umbral de aviso $10^{-10}$). En datasets mas pequenos se observan deltas mayores y el benchmark los reporta como warnings.

Resultados consistentes con el core de R dentro de una tolerancia del 0.01% en la escala del benchmark (0-1000).

## Pruebas y Calidad

Validado con PHPUnit para cubrir calculos y validacion de datos.

```bash
./vendor/bin/phpunit tests
```

## Licencia

Este proyecto se publica bajo la licencia MIT. Ver LICENSE para detalles.

Built with ❤️ by cjuol.
