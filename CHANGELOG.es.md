# Changelog
[English](CHANGELOG.md) | [Español](CHANGELOG.es.md)

Todos los cambios notables en este proyecto se documentan en este archivo.

El formato esta basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/)
y este proyecto se adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-02-10

### Aniadido
- **ClassicStats**: Estadistica descriptiva clasica (media, varianza, desviacion estandar, CV).
- **RobustStats**: Estimadores robustos (mediana, MAD, media recortada, media winsorizada, estimador M de Huber).
- **QuantileEngine**: Cuantiles compatibles con R (tipos 1-9) con valores por defecto iguales a R.
- **StatsComparator**: Deteccion de sesgo entre metricas clasicas y robustas.
- **ExportableTrait**: Exportaciones CSV/JSON para todas las clases estadisticas.
- **DataProcessorTrait**: Validacion y normalizacion centralizada de datasets.
- **CentralTendencyEngine**: Motor interno compartido de tendencia central robusta.
- Pruebas y benchmarks para reproducibilidad y precision.
