# StatGuard — Propuesta de roadmap KPI (pura-cálculo) · 2026-04-16

Autor: Claude (Opus 4.7 · 1M context) — sesión de producto con el mantenedor.
Complementa: `docs/audits/2026-04-16-full-audit.md` (auditoría inicial + §6.1 changelog post-lote).
Scope: redefinir el plan **v2.2.0 → v3.0.0** bajo un nuevo foco de producto.

---

## 1. Contexto y decisión de producto

El mantenedor quiere pivotar StatGuard como **motor de cálculo para generación de KPIs empresariales**, con un primer caso real: planta de **mecanizado industrial con 15 máquinas** que fabrican piezas para sus clientes. El dashboard de KPIs vivirá en **otro repositorio**.

Principio duro acordado:

> **StatGuard es librería de cálculo pura. Todo lo que sea orquestación de dominio (OEE aggregator, readers I/O, loggers, UI, share URLs) queda FUERA y vive en el repo del dashboard.**

Esto reescribe el mapeo de features de `§5` de la auditoría original.

---

## 2. Reclasificación de features propuestas en la auditoría

### 2.1 Se mantienen (encajan con "solo cálculo")

| ID         | Feature                                   | Justificación bajo nuevo foco                                                            |
| ---------- | ----------------------------------------- | ---------------------------------------------------------------------------------------- |
| **FEAT-01** | Streaming: Welford + P²                  | P95/P99 de tiempo de ciclo online por máquina. 15 máquinas × 24 h no caben en RAM.       |
| **FEAT-02** | Bootstrap CIs                             | Cpk con CI y MTBF con CI son requisito enterprise. Datos de fabricación no son normales. |
| **FEAT-03** | Skewness/kurtosis + Shapiro-Wilk         | Cálculo puro. Base para comparador más cuantitativo y para diagnóstico de normalidad antes de Cpk. |
| **FEAT-04** | Rolling windowed stats                   | Cycle-time últimas 8 h (deriva intraturno). Cálculo puro (ventana deslizante).           |

### 2.2 Se descartan bajo nuevo foco

| ID         | Feature                      | Motivo                                                                                            |
| ---------- | ---------------------------- | ------------------------------------------------------------------------------------------------- |
| **FEAT-05** | CSV/JSON/NDJSON reader       | I/O: el dashboard parsea y alimenta arrays. No es math.                                           |
| **FEAT-06** | PSR-3 logger hook            | Observabilidad de app, no cálculo. Añadiría dep (`psr/log`) que contamina el `require`.           |
| **FEAT-07** | Playground share URL + PNG   | UI del playground. Vive en `web/` como demo, no es core de la librería.                           |

### 2.3 Se mantiene en v3.0.0 (decisión ya tomada en audit)

| ID         | Feature              | Estado                                                                                         |
| ---------- | -------------------- | ---------------------------------------------------------------------------------------------- |
| **FEAT-08** | Modo `exact` / bcmath | Cálculo sí, pero no urgente. Mantener en v3.0.0 junto con rename de `robustDeviation` (QUA-02). |

---

## 3. Nuevos cálculos a añadir (pivote KPI industrial)

Todo lo siguiente es **cálculo estadístico puro** (fórmulas cerradas, algoritmos documentados, sin dependencias de dominio). Encaja con el principio "solo math".

### 3.1 Distribución y tests

| Módulo (propuesto)                       | Cálculos                                                                    | Uso típico en KPI industrial                                   |
| ---------------------------------------- | --------------------------------------------------------------------------- | -------------------------------------------------------------- |
| `src/Distribution/NormalityTests.php`    | Shapiro-Wilk, Anderson-Darling                                              | Pre-check antes de aplicar Cpk (requiere normalidad).         |
| `src/Distribution/MomentStats.php`       | Skewness, excess kurtosis                                                   | Diagnóstico asimetría / colas gruesas en KPI.                 |
| `src/Distribution/TwoSampleTests.php`    | Mann-Whitney U, Kolmogorov-Smirnov, permutation test                        | Comparar **máquina A vs B** sin asumir normalidad.            |

### 3.2 Detección de cambio

| Módulo                                   | Cálculos                                                                    | Uso típico                                                    |
| ---------------------------------------- | --------------------------------------------------------------------------- | ------------------------------------------------------------- |
| `src/Changepoint/CusumDetector.php`      | CUSUM para detección de punto de cambio                                    | "¿Cuándo empezó a subir el scrap?"                           |
| `src/Changepoint/Pelt.php`               | PELT (Pruned Exact Linear Time)                                             | Segmentación de series temporales largas.                     |
| `src/Changepoint/BinarySegmentation.php` | Binary segmentation                                                         | Alternativa ligera a PELT.                                    |

### 3.3 SPC — Statistical Process Control (ISO 22514)

| Módulo                                   | Cálculos                                                                    | Uso típico                                                    |
| ---------------------------------------- | --------------------------------------------------------------------------- | ------------------------------------------------------------- |
| `src/Spc/ProcessCapability.php`          | `cp`, `cpk`, `pp`, `ppk` + bootstrap CI                                    | Exigido por cliente automoción (IATF 16949): Cpk ≥ 1.33.     |
| `src/Spc/ShewhartChart.php`              | Xbar-R, Xbar-S, I-MR → `{center, ucl, lcl, violations[]}`                  | Carta de control de cotas dimensionales.                     |
| `src/Spc/CusumChart.php`                 | CUSUM control chart                                                         | Detección de deriva sutil (desgaste herramienta).            |
| `src/Spc/EwmaChart.php`                  | EWMA con `λ` configurable                                                   | Suavizado exponencial, más sensible que Shewhart.            |

### 3.4 Streaming / ventana

| Módulo                                   | Cálculos                                                                    |
| ---------------------------------------- | --------------------------------------------------------------------------- |
| `src/Online/Welford.php`                 | Media/varianza incremental (FEAT-01).                                       |
| `src/Online/P2Quantile.php`              | Cuantiles online algoritmo P² (FEAT-01).                                    |
| `src/Online/RollingWindow.php`           | Ventana deslizante sobre cualquier estadístico (FEAT-04).                   |

### 3.5 Bootstrap (infra compartida)

| Módulo                                   | Cálculos                                                                    |
| ---------------------------------------- | --------------------------------------------------------------------------- |
| `src/BootstrapEngine.php`                | Resampling + CI percentil y BCa, seedable (FEAT-02).                        |

### 3.6 Fiabilidad

| Módulo                                   | Cálculos                                                                    | Uso típico                                                    |
| ---------------------------------------- | --------------------------------------------------------------------------- | ------------------------------------------------------------- |
| `src/Reliability/WeibullFit.php`         | MLE de β (shape), η (scale); `reliability(t)`, `hazard(t)`                  | Vida útil de herramienta, análisis de fallos.                 |

### 3.7 Pareto

| Módulo                                   | Cálculos                                                                    |
| ---------------------------------------- | --------------------------------------------------------------------------- |
| `src/Pareto/ParetoRank.php`              | Ordenar + porcentaje acumulado + punto de corte 80/20.                      |

**Discusión**: Pareto es 50/50. Es cálculo (ordenación + acumulado), no visualización. **Propuesta: incluirlo.** Si se excluye, 20 líneas de lógica acaban duplicadas en N dashboards.

---

## 4. Lo que NO entra (queda en el repo del dashboard)

| Concepto                   | Motivo                                                                              |
| -------------------------- | ----------------------------------------------------------------------------------- |
| **OEE calculator**         | `OEE = A × P × Q` es orquestación de 3 ratios con contexto de turnos. Dominio.      |
| **MTBF / MTTR labels**     | Son `mean(intervals)`. El cálculo ya existe en `ClassicStats::getMean()`.           |
| **CSV/Excel/NDJSON reader**| I/O.                                                                                |
| **PSR-3 logger**           | Observabilidad.                                                                     |
| **Playground share URL**   | UI.                                                                                 |
| **Health endpoint**        | Infra de demo.                                                                      |
| **Gauge R&R**              | Diferido a v2.5.0. ANOVA de repetibilidad/reproducibilidad arrastra dominio de mediciones — frontera borrosa. |

---

## 5. Estructura propuesta de `src/`

```
src/
├── ClassicStats.php
├── RobustStats.php
├── StatsComparator.php
├── QuantileEngine.php
├── CentralTendencyEngine.php
├── BootstrapEngine.php                 (nuevo — FEAT-02)
├── Support/
│   └── DataValidator.php               (ya existe post-QUA-01)
├── Distribution/
│   ├── NormalityTests.php              (Shapiro-Wilk, AD)
│   ├── MomentStats.php                 (skewness, kurtosis)
│   └── TwoSampleTests.php              (MW-U, KS, permutation)
├── Online/
│   ├── Welford.php
│   ├── P2Quantile.php
│   └── RollingWindow.php
├── Changepoint/
│   ├── CusumDetector.php
│   ├── Pelt.php
│   └── BinarySegmentation.php
├── Spc/
│   ├── ProcessCapability.php
│   ├── ShewhartChart.php               (Xbar-R, Xbar-S, I-MR)
│   ├── CusumChart.php
│   └── EwmaChart.php
├── Reliability/
│   └── WeibullFit.php
├── Pareto/
│   └── ParetoRank.php
├── Contracts/
├── Exceptions/
└── Traits/
```

---

## 6. Plan de versiones (reemplaza §6 del audit para v2.2.0+)

| Versión   | Alcance                                                                                                         | Esfuerzo    |
| --------- | --------------------------------------------------------------------------------------------------------------- | ----------- |
| **v2.1.0** | Ya en curso por el otro agente: QUA-01 ✅, PERF-01 ✅, PERF-02 parcial. Pendiente: MED-06, SEC-05, SEC-10, QUA-11/12/13, QW-11. | ~3-5 días   |
| **v2.2.0 "Stat infra"** | `BootstrapEngine` + `Online/{Welford,P2Quantile,RollingWindow}` + `Distribution/*` + `Changepoint/*`. Base reutilizable por el resto. | **~2 sem** |
| **v2.3.0 "SPC core"** | `Spc/ProcessCapability` (Cp/Cpk/Pp/Ppk + bootstrap CI) + `Spc/ShewhartChart` + `Reliability/WeibullFit`. Primer corte que habilita KPIs reales de mecanizado. | **~2 sem** |
| **v2.4.0 "SPC avanzado + Pareto"** | `Spc/CusumChart` + `Spc/EwmaChart` + `Pareto/ParetoRank`.                                              | **~1 sem** |
| **v2.5.0 (reservado)** | Gauge R&R (ANOVA) — opcional, solo si el caso real lo demanda.                                           | TBD         |
| **v3.0.0** | Rename `robustDeviation` (QUA-02) + `exact`/`bcmath` mode (FEAT-08).                                       | TBD         |

---

## 7. Contratos y patrones recurrentes

Para que el dashboard consuma StatGuard limpio, proponemos tres patrones consistentes:

### 7.1 Resultado con CI bootstrap (FEAT-02 habilita todo)

Todo estadístico apto para bootstrap devuelve, opcionalmente, su CI:

```php
ProcessCapability::cpk($data, lsl: 9.95, usl: 10.05);
// → 1.42

ProcessCapability::cpkWithCi($data, lsl: 9.95, usl: 10.05, resamples: 2000, seed: 42);
// → ['point' => 1.42, 'ci' => ['lower' => 1.18, 'upper' => 1.61], 'method' => 'bootstrap-bca']
```

### 7.2 Carta de control como valor de datos

Los control charts **no renderizan**. Devuelven la estructura numérica y el dashboard pinta.

```php
ShewhartChart::iMR($values, sigma: 3.0);
// → [
//     'individuals' => ['center' => 10.01, 'ucl' => 10.06, 'lcl' => 9.96, 'values' => [...], 'violations' => [12, 47]],
//     'movingRange' => ['center' => 0.012, 'ucl' => 0.039, 'lcl' => 0.0, 'values' => [...], 'violations' => []]
//   ]
```

### 7.3 Estado online serializable

Los estimadores `Online/*` deben ser **serializables** (JSON) para que el dashboard persista estado entre requests/turnos:

```php
$welford = new Welford();
$welford->push(3.14);
$state = $welford->toArray();                 // ['count' => 1, 'mean' => 3.14, 'm2' => 0.0]
$restored = Welford::fromArray($state);       // continúa donde se quedó
```

Este contrato es **obligatorio** para que el dashboard no tenga que reprocesar millones de puntos en cada render.

---

## 8. Superficie pública: qué entra en `StatsInterface` / `ExportableInterface`

- **No** se toca `StatsInterface` en v2.2.0. Los nuevos módulos son clases finales con métodos estáticos o constructores simples.
- **No** se expone `toCsv` / `toJson` para cada nuevo módulo por defecto — sólo donde tiene sentido (p. ej. `ShewhartChart`).
- El rename de `robustDeviation` (QUA-02) se mantiene como único breaking en v3.0.0.

---

## 9. Preguntas abiertas para el otro agente

Antes de entrar a v2.2.0 necesitamos alinear:

1. **¿Pareto dentro o fuera?** Mi propuesta: dentro (`src/Pareto/ParetoRank.php`). Justificación: 80/20 es cálculo (ordenar + acumulado), no renderizado. Si queda fuera se duplicará en cada consumer. **Tu criterio.**

2. **¿Dependencias opcionales?** El bootstrap no necesita nada. Shapiro-Wilk tiene implementaciones en PHP puro; Weibull MLE también (Newton-Raphson). **Mi propuesta: cero deps nuevas en v2.2.0-v2.4.0.** Mantener el "zero-dep" como diferenciador vs MathPHP.

3. **¿Dónde vive el bootstrap?** Dos opciones:
   - `src/BootstrapEngine.php` expuesto público (el consumer puede hacer bootstrap sobre lo que quiera).
   - Método interno, solo accesible vía `*WithCi` en cada clase.

   **Mi propuesta: público.** Dashboard de KPIs querrá bootstrap sobre métricas propias (p. ej. OEE) que StatGuard no conoce.

4. **¿Estado online: `toArray`/`fromArray` o interfaz formal `Serializable`?** PHP `Serializable` está deprecado desde 8.1. Array idiomático + static factory es más limpio. Confirmar patrón antes de escribir 3 clases con ello.

5. **¿Nombre `Spc` vs `StatisticalProcessControl`?** Abreviatura `Spc` es estándar en literatura industrial y en nombres de namespace de librerías Python/R (`scipy.stats`, `qualityTools`). **Mi propuesta: `Spc`.**

6. **¿Versiona semver cada bloque o agrupamos?** Propuesta: agrupar por milestone (v2.2.0 Stat infra, v2.3.0 SPC core) para que cada release traiga valor coherente al dashboard. Tu criterio.

7. **¿Tests R de referencia para SPC?** Cpk, Xbar-R y Weibull tienen referencias fiables en `qcc` y `survival` de R. **Propuesta**: seguir patrón existente (`MatchesRReference` skippable), pero añadir también valores hardcoded generados una vez con R para CI sin R (mismo que FEAT `MED-06`).

---

## 10. Resumen ejecutivo para el otro agente

- **Pivote de producto**: StatGuard enfocado a motor de KPIs industriales, empezando por planta de mecanizado (15 máquinas). Dashboard vive aparte.
- **Principio**: solo cálculo. Se descartan **FEAT-05/06/07** de la auditoría original.
- **Se mantienen**: **FEAT-01/02/03/04** (encajan como primitivas estadísticas puras).
- **Se añaden** (cálculo puro, cero deps de runtime):
  - `Distribution/` — normality tests, momentos, two-sample tests.
  - `Changepoint/` — CUSUM, PELT, binary segmentation.
  - `Spc/` — Cp/Cpk/Pp/Ppk + Shewhart + CUSUM + EWMA.
  - `Reliability/` — Weibull MLE.
  - `Pareto/` — ranking 80/20.
  - `BootstrapEngine` — público, habilita CI en todo lo demás.
  - `Online/` + `RollingWindow` — con contrato serializable.
- **v2.1.0** la cerrás tú como veníamos. **v2.2.0 → v2.4.0** es la ejecución de este plan.
- Necesito respuesta a las **7 preguntas abiertas del §9** antes de escribir SDD proposal de v2.2.0.

Propongo siguiente paso: abrir un SDD `sdd-new v2.2.0-stat-infra` una vez resueltas las preguntas, con `artifact_store=engram` y `execution=interactive`.
