# Tutoriales

Recetas cortas para tareas comunes.

## 1) Detectar sesgo por outliers

```php
use Cjuol\StatGuard\StatsComparator;

$data = [10, 12, 11, 15, 10, 1000];

$comparator = new StatsComparator();
$analysis = $comparator->analyze($data);

echo $analysis['verdict'];
```

Interpretacion rapida:
- Si el veredicto alerta sesgo, usa medianas o Huber.
- Si el veredicto es estable, la media clasica es segura.

## 2) Resumen robusto para reportes

```php
use Cjuol\StatGuard\RobustStats;

$data = [10, 12, 11, 15, 10, 1000];

$robust = new RobustStats();
$summary = $robust->getSummary($data);

file_put_contents('summary.csv', $robust->toCsv($data));
file_put_contents('summary.json', $robust->toJson($data));
```

## 3) Cuantiles compatibles con R

```php
use Cjuol\StatGuard\QuantileEngine;

$data = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
$engine = new QuantileEngine();

// Type 7 es el default de R.
$q7 = $engine->quantile($data, 0.75, 7);

// Type 1 es mas discreto y depende del orden.
$q1 = $engine->quantile($data, 0.75, 1);
```

Cuando elegir tipo 7:
- Analisis exploratorio general.
- Consistencia con R por defecto.

Cuando elegir tipo 1:
- Series discretas o conteos donde no deseas interpolacion.
