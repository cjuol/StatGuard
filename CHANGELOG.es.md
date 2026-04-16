# Changelog
[English](CHANGELOG.md) | [Español]

Todos los cambios notables en este proyecto se documentan en este archivo.

El formato esta basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/)
y este proyecto se adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.1] - 2026-04-16

Patch release que cierra todos los items ALTO/MEDIO/BAJO de la auditoria completa (`docs/audits/2026-04-16-full-audit.md`). Sin cambios de API.

### Seguridad
- **SEC-01** Limita los payloads de `POST /api.php` a 50 000 puntos; rechaza mayores con HTTP 413.
- **SEC-02** La demo ya no filtra el contenido de `Throwable::getMessage()` en respuestas 500; los errores se loguean server-side y se exponen como `"Internal error."` generico.
- **SEC-03** `CentralTendencyEngine::normalizeData` ahora rechaza NaN/Inf via `is_finite`, restaurando el contrato v2.0.0 en todas las rutas.
- **SEC-06** Hashes SRI (SHA-384) + `crossorigin=anonymous` + `referrerpolicy=no-referrer` en los tres scripts jsDelivr del playground.
- **SEC-07** `X-Content-Type-Options: nosniff` en `api.php`; meta CSP en `index.html` que restringe orígenes de script/estilo/fuente/conexión y prohíbe framing.

### Corregido
- **QUA-04** `api.php` ya no descarta silenciosamente NaN/Inf via `array_filter`. La libreria recibe el payload tal cual y devuelve HTTP 422 via `InvalidDataSetException`.
- **QUA-05** Snippet roto de QuantileEngine en `docs/api-reference.md` sustituido por la API estatica real (`QuantileEngine::calculate`).
- **QUA-07** Typo `"5.1h"` → `"5.1x"` corregido en `shield.json`.

### Cambiado
- **QUA-01** Validacion de datos unificada en `Cjuol\StatGuard\Support\DataValidator`. El trait y ambos engines delegan aqui en vez de duplicar los chequeos `is_numeric` / `is_finite`.
- **PERF-01** `RobustStats::getSummary` reusa Q1/Q3/mediana precalculados, pasando de cuatro calculos de cuantil a dos.
- **PERF-02** `StatsComparator::analyze` inlinea el pipeline de validacion/ordenacion via `QuantileEngine`, saltando ordenaciones redundantes sobre el dataset compartido.

### Build y CI
- **SEC-13** Fija los tags `phpdocumentor/phpdocumentor:3.5` y `squidfunk/mkdocs-material:9.5.44` en `docker-compose.yml`.
- **CI-01** Pinea `exuanbo/actions-deploy-gist@47697fc` (v1.1.4) en lugar del tag movil `@v1` en los workflows que manejan `GIST_TOKEN`.
- **QUA-08** Deja de trackear el `statguard-perf.json` regenerado; ahora lo escribe solo el workflow de benchmark.
- **QW-08** Anade un `Makefile` minimo (`test`, `test-docker`, `analyse`, `coverage`, `bench`, `clean`, `help`).

### Documentacion
- **SEC-04** Documenta los rangos validos de `huberK` (default 1.345, abierto en lib, clamp demo ≥ 0.1) y `trimPercentage` (lib `[0.0, 0.5)` vs clamp demo `[0.0, 0.45]`), ademas de las constantes internas de iteracion Huber, en `docs/api-reference.md`.

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
