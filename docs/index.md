# 🛡️ StatGuard
[English](../README.md) | [Español]

[![GitHub Actions](https://github.com/cjuol/statguard/actions/workflows/docs.yml/badge.svg)](https://github.com/cjuol/statguard/actions)
[![Version](https://img.shields.io/badge/version-v1.1.0-brightgreen.svg)](https://packagist.org/packages/cjuol/statguard)
[![Licencia](https://img.shields.io/github/license/cjuol/statguard.svg)](LICENSE)

StatGuard es el motor estadistico mas rapido para PHP en analisis robusto, enfocado en precision cientifica y deteccion de sesgos por valores atipicos. En medianas supera a MathPHP por un factor cercano a 5x en datasets grandes y en la media de Huber alcanza cerca de 20x.

!!! info
	Incluye cuantiles compatibles con R, estimadores robustos (Huber, MAD, IQR) y exportaciones listas para auditoria.

## Benchmark (100,000 elementos)

| Metrica (100k) | StatGuard (ms) | MathPHP (ms) | R (ms) | Relacion (PHP/R) |
| :--- | ---: | ---: | ---: | ---: |
| Mediana | 15.85 | 76.55 | 2.00 | 7.92 |
| Cuantil tipo 7 (p=0.75) | 16.19 | 16.03 | 2.00 | 8.09 |
| Media de Huber | 34.76 | 788.71 | 10.00 | 3.48 |

## Quick Start

```php
use Cjuol\StatGuard\RobustStats;

$stats = new RobustStats();
$data = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 1000];

$mean = $stats->getMean($data);
$huber = $stats->getHuberMean($data);
```

Comparativa rapida (Media vs Huber):

| Metrica | Resultado | Interpretacion |
| :--- | ---: | :--- |
| Media | 95.9091 | Sesgada por el outlier |
| Huber | 6.0982 | Se mantiene cerca del centro |

!!! success
	El estimador de Huber conserva precision en el centro y controla el impacto de valores extremos.

Built with ❤️ by cjuol.
