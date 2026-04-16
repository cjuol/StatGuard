# Changelog
[English](CHANGELOG.md) | [Español]

Todos los cambios notables en este proyecto se documentan en este archivo.

El formato esta basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/)
y este proyecto se adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-04-16

### Rompedor
- `Contracts\StatsInterface` ahora requiere `getVariance(array $data): float`. Los implementadores externos deben anadir este metodo.
- Los valores `NaN`, `INF` e `-INF` ahora lanzan `InvalidDataSetException` en lugar de propagarse silenciosamente. Filtra el dataset previamente si dependias del comportamiento anterior.

### Agregado
- `Contracts\ExportableInterface` que publica la superficie `toJson` / `toCsv` para tipar contra el contrato y no contra la clase concreta.
- Demo web Outlier Playground (`web/public/`) con UI bilingue, leyenda de severidad, panel Classic vs Robust en paralelo y estilo Nothing.
- Lanzador `scripts/serve-demo.sh` con PHP nativo y fallback a Docker.
- Configuracion PHPStan nivel 5 (`phpstan.neon.dist`) y script `composer analyse`.
- Salida de phpDocumentor publicada en GitHub Pages con medalla Docs en el README.
- Medalla de cobertura via endpoint en gist (`scripts/coverage_shield.php`, job PHP 8.3 + pcov).

### Cambiado
- Calculo de mediana unificado en `QuantileEngine::medianSorted`; eliminadas las implementaciones duplicadas en `ClassicStats`, `RobustStats` y `CentralTendencyEngine`.
- El benchmark de R (`tests/r_performance.R`) ahora invoca `MASS::huber` con los defaults de StatGuard (`k=1.345`, `tol=0.001`) en vez de los de R, restaurando la paridad de Huber.

### Corregido
- Tres hallazgos de PHPStan nivel 5 en `QuantileEngine` (rama de tipo redundante y dos `match` sin arm por defecto).

## [1.1.0] - 2026-02-11

### Agregado
- Suite de benchmarks de rendimiento (StatGuard vs R vs MathPHP) con ratios.
- Script de rendimiento en R para mediana, cuantil tipo 7 y media de Huber.
- Perfil performance en Docker Compose para ejecuciones reproducibles.
- Workflow de GitHub Actions para la medalla dinamica de rendimiento.
- Certificacion de rendimiento y paridad con R para v1.1.0.
- Suite de benchmarking integrada con MathPHP y R.
- Documentacion bilingue con soporte MkDocs y GitHub Pages.

### Cambiado
- El benchmark ahora incluye tiempos de R y warnings de precision para paridad de Huber.

## [1.0.0] - 2026-02-11

### Agregado
- Lanzamiento inicial.
- Motores internos independientes: `QuantileEngine` y `CentralTendencyEngine` para nucleos matematicos reutilizables.
- Paridad con R v4.x validada para cuantiles y metodos de tendencia central robusta.
- **ClassicStats**: Estadistica descriptiva clasica (media, varianza, desviacion estandar, CV).
- **RobustStats**: Estimadores robustos (mediana, MAD, media recortada, media winsorizada, estimador M de Huber).
- **StatsComparator**: Deteccion de sesgo entre metricas clasicas y robustas.
- **ExportableTrait**: Exportaciones CSV/JSON para todas las clases estadisticas.
- **DataProcessorTrait**: Validacion y normalizacion centralizada de datasets.
- Pruebas y benchmarks para reproducibilidad y precision.

### Changed
- N/A (lanzamiento inicial).

### Fixed
- N/A (lanzamiento inicial).

Built with ❤️ by cjuol.
