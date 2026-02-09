# 🐙 Symmetrical Octo: Robust Stats Suite for PHP
[![Latest Version on Packagist](https://img.shields.io/packagist/v/cjuol/symmetrical-octo.svg?style=flat-square)](https://packagist.org/packages/cjuol/symmetrical-octo)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![PHP Tests](https://github.com/cjuol/symmetrical-octo/actions/workflows/php-tests.yml/badge.svg)](https://github.com/cjuol/symmetrical-octo/actions)

Symmetrical Octo es una suite avanzada de análisis estadístico. Su propósito es permitir a los desarrolladores enfrentar la Estadística Clásica contra la Estadística Robusta para identificar sesgos, ruido y errores de medición de forma automatizada.

## 💡 Motivación y Origen

En entornos como el seguimiento deportivo o la telemetría, los datos suelen contener "ruido" (fallos de sensores, días excepcionales). La estadística clásica (Media) es un "cristal" que se rompe ante un solo valor extremo.

Symmetrical Octo actúa como un filtro de calidad, permitiéndote saber cuándo puedes confiar en la media y cuándo debes recurrir a la robustez de la mediana y el MAD.

## 🚀 Nuevas Funcionalidades (v1.1.0)

Esta versión transforma la biblioteca en una herramienta integral con arquitectura SOLID:

- **ClassicStats**: Implementación completa de estadística descriptiva tradicional.
- **StatsComparator**: El "cerebro" que analiza la fidelidad de tus datos y emite veredictos.
- **ExportableTrait**: Exportación nativa a CSV y JSON integrada en todas las clases.
- **Arquitectura de Traits e Interfaces**: Validación automática de datos y extensibilidad garantizada.

## 🛠 Instalación

```bash
composer require cjuol/symmetrical-octo
```

## 📖 Guía de Uso

### 1. El Comparador (Detección de Sesgos)

Es la herramienta más potente de la suite. Analiza si la media clásica está "muriendo" por culpa de los outliers.

```php
use Cjuol\SymmetricalOcto\StatsComparator;

$comparator = new StatsComparator();
$datos = [10, 12, 11, 15, 10, 1000]; // El 1000 es ruido

$analisis = $comparator->analizar($datos);

echo $analisis['veredicto'];
// ALERTA: Datos altamente influenciados por outliers. Se recomienda usar métricas Robustas.
```

### 2. Exportación Instantánea

Cualquier clase estadística puede generar informes listos para descargar o enviar por API:

```php
$robust = new \Cjuol\SymmetricalOcto\RobustStats();

// Generar un CSV para abrir en Excel
file_put_contents('informe.csv', $robust->toCsv($datos));

// O un JSON para tu Frontend
echo $robust->toJson($datos);
```

## 📊 Comparativa de Métricas

| Métrica | ClassicStats | RobustStats | Impacto de Outliers |
| :--- | :--- | :--- | :--- |
| Centro | Media | Mediana | Alta en Clásica |
| Dispersión | Desv. Estándar | MAD (Escalado) | Extremo en Clásica |
| Variabilidad | CV% | CVr% | Muy alto en Clásica |
| Exportable | ✅ Si | ✅ Si | - |

## 📌 Metodos Implementados

### ClassicStats

- `getMedia(array $datos): float`
- `getMediana(array $datos): float`
- `getDesviacion(array $datos): float`
- `getDesviacionEstandar(array $datos): float`
- `getCV(array $datos): float`
- `getVarianzaMuestral(array $datos): float`
- `getVarianzaPoblacional(array $datos): float`
- `getOutliers(array $datos): array`
- `obtenerResumen(array $datos, bool $ordenar = true, int $decimales = 2): array`
- `toJson(array $datos, int $options = JSON_PRETTY_PRINT): string`
- `toCsv(array $datos, string $delimiter = ","): string`

### RobustStats

- `getMedia(array $datos): float`
- `getMediana(array $datos): float`
- `getDesviacion(array $datos): float`
- `getCV(array $datos): float`
- `getDesviacionRobusta(array $datos): float`
- `getCVr(array $datos): float`
- `getVarianzaRobusta(array $datos): float`
- `getIQR(array $datos): float`
- `getMAD(array $datos): float`
- `getOutliers(array $datos): array`
- `getIntervalosConfianza(array $datos): array`
- `obtenerResumen(array $datos, bool $ordenar = true, int $decimales = 2): array`
- `toJson(array $datos, int $options = JSON_PRETTY_PRINT): string`
- `toCsv(array $datos, string $delimiter = ","): string`

### StatsComparator

- `__construct(?RobustStats $robust = null, ?ClassicStats $classic = null)`
- `analizar(array $datos, int $decimales = 2): array`

## 🧪 Fundamento Matemático

### Desviación Robusta Escalada

Para que el comparador sea justo, escalamos el MAD para hacerlo comparable a la desviación estándar en distribuciones normales:

$$\sigma_{robust} = MAD \times 1.4826$$

### Coeficiente de Variación Robusto ($CV_r$)

Calculado sobre la mediana para evitar que un solo valor extremo infle la percepción de volatilidad:

$$CV_r = \left( \frac{\sigma_{robust}}{|\tilde{x}|} \right) \times 100$$

## 🚦 Tests y Calidad

Validación completa mediante PHPUnit asegurando una cobertura total en cálculos y validaciones de datos.

```bash
./vendor/bin/phpunit tests
```

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Consulta el archivo LICENSE para más detalles.

Desarrollado con ❤️ por cjuol.
