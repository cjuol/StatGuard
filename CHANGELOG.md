# Changelog

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/),
y este proyecto se adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-02-10

### Añadido
- **CentralTendencyEngine**: Nuevo motor estático para medias robustas (Trimmed, Winsorized, Huber M-estimator).
- **RobustStats**: Nuevos métodos `getTrimmedMean()`, `getWinsorizedMean()` y `getHuberMean()`.
- **QuantileEngineTest**: Validación con expectativas compatibles con los 9 tipos de `quantile()` en R.
- **scripts/validate_with_r.php**: Script rápido de comparación de cuantiles con R en tiempo real.
- **tests/BenchmarkStatGuard.php**: Benchmark del Huber M-estimator con 10,000 elementos.

### Cambiado
- Documentación ampliada para compatibilidad exacta con R v4.x y referencia a Hyndman & Fan (1996) y Huber (1964).

### Notas
- Este cambio no es Breaking Change: no modifica la API pública existente de `RobustStats`, solo añade nuevos métodos.

## [1.1.0] - 2025-05-22

### Añadido
- **StatsInterface**: Nuevo contrato para estandarizar todas las clases estadísticas.
- **ClassicStats**: Nueva clase para cálculos de estadística descriptiva tradicional (Media, Desviación Estándar, Varianza).
- **StatsComparator**: Servicio para comparar métricas Robustas vs Clásicas y detectar sesgos en los datos.
- **ExportableTrait**: Funcionalidad para exportar resultados de cualquier clase estadística a formatos **CSV** y **JSON**.
- Soporte oficial para **PHP 8.4** y **PHP 8.5** en el entorno de tests.

### Cambiado
- **RobustStats**: Refactorizado para implementar `StatsInterface` y utilizar la nueva arquitectura de Traits.
- **Seguridad**: Ahora todos los métodos de cálculo validan automáticamente los datos mediante `DataProcessorTrait` antes de procesar.
- Mejora en la precisión de la Desviación Robusta para ser comparable con la Desviación Estándar (MAD escalado).

### Eliminado
- Parámetro `$ordenar` opcional en métodos públicos para garantizar la integridad matemática por defecto.