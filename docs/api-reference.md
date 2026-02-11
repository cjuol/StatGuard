# Referencia de API

La API generada con phpDocumentor esta disponible en el menu bajo el enlace **API**.
Usa esta pagina como mapa rapido de clases y ejemplos minimos.

!!! info
	Si ejecutas el sitio localmente, abre la seccion API para navegar por namespaces, clases y metodos.

## Mapa de clases

- `ClassicStats`: estadistica clasica (media, desviacion, varianza, outliers).
- `RobustStats`: estadistica robusta (Huber, MAD, IQR, robust CV).
- `QuantileEngine`: cuantiles tipo 1-9 compatibles con R.
- `CentralTendencyEngine`: mediana, Huber y medias robustas.
- `StatsComparator`: veredicto de sesgo entre clasico y robusto.

## Ejemplos minimos

### ClassicStats

```php
use Cjuol\StatGuard\ClassicStats;

$classic = new ClassicStats();
$data = [1, 2, 3, 4, 5];

$mean = $classic->getMean($data);
$summary = $classic->getSummary($data);
```

### RobustStats

```php
use Cjuol\StatGuard\RobustStats;

$robust = new RobustStats();
$data = [1, 2, 3, 4, 5, 1000];

$huber = $robust->getHuberMean($data);
$iqr = $robust->getIqr($data, RobustStats::TYPE_R_DEFAULT);
```

### QuantileEngine

```php
use Cjuol\StatGuard\QuantileEngine;

$engine = new QuantileEngine();
$data = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

$q7 = $engine->quantile($data, 0.75, 7);
```

### StatsComparator

```php
use Cjuol\StatGuard\StatsComparator;

$comparator = new StatsComparator();
$data = [10, 12, 11, 15, 10, 1000];

$analysis = $comparator->analyze($data);
echo $analysis['verdict'];
```
