# 🛡️ StatGuard
[English](../README.md) | [Español]

[![GitHub Actions](https://github.com/cjuol/statguard/actions/workflows/docs.yml/badge.svg)](https://github.com/cjuol/statguard/actions)
[![Version](https://img.shields.io/badge/version-v1.1.0-brightgreen.svg)](https://packagist.org/packages/cjuol/statguard)
[![Licencia](https://img.shields.io/github/license/cjuol/statguard.svg)](LICENSE)

StatGuard es una suite de estadistica robusta para PHP. Te ayuda a resumir datos con outliers sin sesgo y a comparar resultados clasicos vs robustos con un veredicto claro.

!!! info
	Incluye cuantiles compatibles con R, estimadores robustos (Huber, MAD, IQR) y exportaciones listas para auditoria.

## Empieza rapido

Instala via Composer:

```bash
composer require cjuol/statguard
```

Ejemplo minimo:

```php
use Cjuol\StatGuard\RobustStats;

$stats = new RobustStats();
$data = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 1000];

$mean = $stats->getMean($data);
$huber = $stats->getHuberMean($data);
```

Si quieres un flujo completo, sigue la guia de inicio y los tutoriales.

## Que puedes hacer con StatGuard

- Detectar sesgo por outliers con `StatsComparator`.
- Generar reportes robustos con `RobustStats`.
- Replicar cuantiles de R (tipos 1-9).

## Siguientes pasos

- Guia de inicio: instalacion y primer resultado.
- Tutoriales: recetas para casos reales.
- Conceptos: fundamentos simples antes de la teoria.

Built with ❤️ by cjuol.
