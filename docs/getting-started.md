# Guia de inicio

Esta guia te lleva de cero a un primer resultado en menos de 10 minutos.

## Instalacion

Instala via Composer:

```bash
composer require cjuol/statguard
```

Requisitos: PHP 8.x.

## Primeros 10 minutos

Objetivo: comparar la media clasica con un estimador robusto y generar un
reporte rapido.

```php
use Cjuol\StatGuard\RobustStats;
use Cjuol\StatGuard\StatsComparator;

$data = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 1000];

$robust = new RobustStats();
$mean = $robust->getMean($data);
$huber = $robust->getHuberMean($data);

$comparator = new StatsComparator();
$analysis = $comparator->analyze($data);

file_put_contents('report.json', $robust->toJson($data));

print_r([
    'mean' => $mean,
    'huber' => $huber,
    'verdict' => $analysis['verdict'],
]);
```

Que esperar:
- La media se desplaza por el outlier.
- Huber se mantiene cerca del centro.
- El veredicto indica sesgo por valores extremos.

## Siguiente paso

Pasa a los tutoriales para ver casos completos y recetas.
