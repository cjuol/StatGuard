# Symmetrical Octo: Robust Statistics for PHP 🚀

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cjuol/symmetrical-octo.svg?style=flat-square)](https://packagist.org/packages/cjuol/symmetrical-octo)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/badge/tests-passing-brightgreen.svg?style=flat-square)](tests/)

**Symmetrical Octo** es una biblioteca de PHP especializada en **Estadística Robusta**. A diferencia de la estadística clásica, este paquete implementa métodos basados en el **Rango Intercuartílico (IQR)** y la **Desviación Robusta ($S^*$)**, diseñados específicamente para mitigar el impacto de valores atípicos (*outliers*) y errores de medición.

---

## 💡 Motivación y Origen

Esta biblioteca nació de una necesidad técnica real. En entornos como el **seguimiento deportivo (diarios de entreno)** o la **gestión de hostelería**, los datos suelen contener "ruido": errores de registro, días excepcionales o fallos en sensores. Las librerías estándar de PHP se centran en la estadística clásica (Media/Desviación Estándar), que falla al procesar estas muestras.

**Symmetrical Octo** ofrece una alternativa fiable para proyectos donde la precisión del "centro" de los datos es crítica y no puede verse comprometida por fluctuaciones extremas.

---

## 📊 ¿Por qué Estadística Robusta?

La estadística tradicional es extremadamente sensible a valores extremos. Esta librería utiliza la **Mediana** y el factor de escala **$S^*$** para ofrecer una visión real del comportamiento habitual de tus datos.

### Comparativa: Clásica vs. Robusta
Datos de ejemplo (N=10) con ruido: `[87.3, 84, 85.4, 78, 85, 89, 79, 89, 76, 86.5]`

| Métrica | Estadística Clásica | **Symmetrical Octo (Robusta)** |
| :--- | :--- | :--- |
| **Centro** | Media: 83.92 | **Mediana: 85.20** |
| **Dispersión** | Desv. Estándar: 4.67 | **$S^*$ (Robusta): 2.01** |
| **Variabilidad** | CV: 5.57% | **CVr%: 2.35%** |

---

## 🛠 Instalación

```bash
composer require cjuol/symmetrical-octo
```

---

## 🚀 Uso Rápido

```php
use Cjuol\SymmetricalOcto\RobustStats;

$stats = new RobustStats();
$datos = [87.3, 84, 85.4, 78, 85, 89, 79, 89, 76, 86.5];

// Obtener un informe completo de una sola vez (Recomendado para Dashboards)
$resumen = $stats->obtenerResumen($datos, ordenar: true, decimales: 2);

print_r($resumen);
/*
Array(
    [media] => 83.92
    [mediana] => 85.2
    [desviacionRobusta] => 2.01
    [CVr] => 2.35
    [outliers] => Array()
    ...
)
*/

// O acceder a métodos individuales
$mediana = $stats->getMediana($datos);
$outliers = $stats->getOutliers($datos);
```

---

## 📖 Métodos Disponibles

La clase RobustStats ofrece una interfaz limpia y eficiente:

| Función | Descripción | Resultado |
| :--- | :--- | :--- |
| getMedia() | Promedio aritmético clásico. | float |
| getMediana() | Valor central resistente a outliers. | float |
| getDesviacionRobusta() | Calcula $S^*$, la alternativa robusta a la Desv. Estándar. | float |
| getCVr() | Coeficiente de Variación Robusto (en %). | float |
| getIQR() | Rango Intercuartílico ($Q3 - Q1$). | float |
| getMAD() | Desviación Absoluta de la Mediana. | float |
| getOutliers() | Identifica valores "extraños" (Método de Tukey). | array |
| getIntervalosConfianza() | Límites superior e inferior al 95%. | array |
| obtenerResumen() | Métrica completa optimizada en rendimiento. | array |

---

## 🧪 Fundamento Matemático

Esta librería implementa la estimación de escala consistente para datos normales:

$$S^* = \left( \frac{1.25}{1.35} \right) \times \left( \frac{IQR}{\sqrt{n}} \right)$$

- **Ajuste de Consistencia**: El factor $1.25/1.35$ permite que $S^*$ sea comparable a la desviación estándar en distribuciones normales, pero manteniendo la resistencia del IQR.
- **Intervalos**: Se utiliza un factor de cobertura $k=1.96$ para el 95% de confianza.

---

## 🚦 Tests

Validación completa mediante PHPUnit asegurando precisión matemática.

```bash
./vendor/bin/phpunit tests
```

---

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Consulta el archivo LICENSE para más detalles.

Desarrollado con ❤️ por **cjuol**.
