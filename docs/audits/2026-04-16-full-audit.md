# StatGuard — Full Audit · 2026-04-16

Auditor: Claude (Opus 4.7 · 1M context)
Commit: `d17cb38` (tag v2.0.0)
Scope: full repository in its state as of 2026-04-16.

---

## 0. Alcance y nota previa

El repositorio **no** es la aplicación SaaS Symfony/Doctrine/RabbitMQ que presuponía la plantilla original de auditoría. StatGuard es:

1. **Una librería PHP 8.1+** publicada en Packagist (`cjuol/statguard`) con namespace `Cjuol\StatGuard\*` y sin dependencias de runtime distintas a PHP.
2. **Una demo web ligera** en `web/public/` (un único endpoint `api.php`, HTML estático con Alpine.js + Chart.js).
3. **Un set de scripts** de benchmark (`tests/BenchmarkStatGuard.php`), validación contra R (`scripts/validate_with_r.php`), y generación de badges (`scripts/coverage_shield.php`).
4. **Documentación MkDocs + phpDocumentor** publicada en GitHub Pages.

Por tanto, secciones como "filtro Doctrine multi-tenant", "workers Messenger", "JWT", "voters Symfony", "CSV upload tenant scope", "RabbitMQ DLQ", "Doctrine query cache", "migrations" **no aplican** a este repositorio y se documentan aquí como *N/A* para dejar el checklist cerrado. Si en el futuro esto evoluciona hacia un SaaS con esas características, esa plantilla vuelve a ser relevante.

La auditoría que sigue se adapta al producto real: una librería de estadística + demo web.

---

## 1. Mapa del proyecto

### 1.1 Inventario de código

```
src/
├── ClassicStats.php                 (~150 LoC) estadística clásica
├── RobustStats.php                  (~240 LoC) estadística robusta (fachada)
├── StatsComparator.php              (~90  LoC) comparador clásico vs robusto
├── QuantileEngine.php               (~165 LoC) cuantiles tipos 1-9 (Hyndman & Fan)
├── CentralTendencyEngine.php        (~210 LoC) trimmed / winsorized / Huber
├── Contracts/
│   ├── StatsInterface.php            contrato público de las clases Stats
│   └── ExportableInterface.php       contrato de toJson/toCsv
├── Exceptions/
│   └── InvalidDataSetException.php   extends \InvalidArgumentException
└── Traits/
    ├── DataProcessorTrait.php        validateData + prepareData
    └── ExportableTrait.php           toJson/toCsv genéricos
```

No hay entidades, migraciones ni capa HTTP/framework. Una dependencia "contractual" cruza el paquete: `StatsInterface::getVariance()` (añadido en v2.0.0 — breaking) y `ExportableInterface` (nuevo en v2.0.0).

### 1.2 Endpoints expuestos (sólo la demo, no la librería)

| Ruta               | Método     | Auth | Fichero                            |
| ------------------ | ---------- | ---- | ---------------------------------- |
| `GET /`            | GET        | —    | `web/public/index.html` (estático) |
| `POST /api.php`    | POST+OPTIONS | —  | `web/public/api.php`               |

`api.php` acepta `{ "data": number[], "huberK": number?, "trimPercent": number? }`, responde `application/json` con el resumen combinado (`classic`, `robust`, `centralTendency`, `comparison`). Maneja `OPTIONS` (CORS preflight) y devuelve `405` fuera de POST/OPTIONS.

### 1.3 Workers / consumers

**N/A** — no hay Symfony Messenger ni colas. La librería es 100 % síncrona.

### 1.4 Servicios inyectables

**N/A** — no hay contenedor DI. `StatsComparator::__construct(?RobustStats, ?ClassicStats)` permite inyección manual y tiene defaults.

### 1.5 Dependencias Composer

Runtime:
- `php >=8.1` (matrix CI: 8.1 → 8.5).
- **Ninguna dependencia de terceros** en `require`. Para una librería matemática esto es un valor: cero superficie de ataque transitiva.

Dev:
- `phpunit/phpunit: ^10.5 || ^11 || ^12`
- `phpstan/phpstan: ^2.1`
- `markrogoyski/math-php: ^2.0` (sólo para el benchmark comparativo)

`composer audit` → **sin advisories**. (Confirmado ejecutando en contenedor `php:8.3-cli`.)

### 1.6 Cobertura de tests

Ejecutado localmente con `vendor/bin/phpunit` (+ pcov) sobre PHP 8.3:

```
Tests: 85   Assertions: 132   Skipped: 3 (R no disponible en el contenedor)
Lines:   74.49 %  (254/341)
Methods: 68.33 %  (41/60)
Classes:  0.00 %  (0/7)   ← ninguna clase tiene cobertura *completa*
```

Por clase:

| Clase                 | Líneas  | Métodos |
| --------------------- | ------: | ------: |
| ClassicStats          | 97.06 % | 91.67 % |
| StatsComparator       | 97.06 % | 66.67 % |
| DataProcessorTrait    | 90.00 % | 50.00 % |
| ExportableTrait       | 93.75 % | 50.00 % |
| QuantileEngine        | 87.84 % | 60.00 % |
| RobustStats           | 76.54 % | 83.33 % |
| **CentralTendencyEngine** | **34.15 %** | **0.00 %** |

Lectura: el hueco real es `CentralTendencyEngine`. Sus tests ejecutables (tamaño `[42]` y datasets vacíos) no entran al cuerpo de los algoritmos; los tests que sí ejercitan el cálculo (`*MatchesRReference`) están marcados `skipIfRUnavailable()` y se saltan en CI salvo en el workflow `main.yml` que instala R. Ver `MED-06`.

El comando `make test` que la plantilla sugería **no existe** (no hay `Makefile`). El flujo real es `composer run test` (que prioriza el perfil `r-validation` de Docker, con fallback a Docker `web`), o directamente `./vendor/bin/phpunit`.

---

## 2. Auditoría de seguridad

Severidades: **CRÍTICO** (explotable / fuga / integridad) · **ALTO** (riesgo claro, no trivial) · **MEDIO** (buena práctica, mitigación razonable) · **BAJO** (higiene).

### 2.1 Autenticación y autorización

- Filtro multi-tenant Doctrine, voters, JWT: **N/A**.
- Los endpoints de la demo (`/` y `/api.php`) son **públicos por diseño** (es un playground). No hay datos persistidos.

### 2.2 Inyección, validación y DoS de entrada

#### SEC-01 · ALTO — API sin límite de tamaño de array
`web/public/api.php:38-42` acepta `payload['data']` sin cota superior. Un cliente puede mandar un JSON de p. ej. 10 M de floats y forzar:
- `json_decode` de varios GB,
- `sort()` O(n log n) en PHP,
- tres cálculos de Huber (iterativos, hasta 50 iteraciones completas cada llamada).

El `php -S` interno bloquea 1 worker por petición y colapsa la demo. No hay `post_max_size` explícito ni límite en el propio script.

**Mitigación sugerida**: rechazar con HTTP 413 si `count($data) > N` (p. ej. 50 000, consistente con el benchmark `100 000` como techo absoluto). Configurable vía env.

#### SEC-02 · MEDIO — Fuga de detalle en respuesta 500
`web/public/api.php:80-82`:
```php
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal error: ' . $e->getMessage()]);
}
```
`getMessage()` puede incluir nombres de clase, paths o detalles internos si la excepción no es `InvalidDataSetException`. La demo vive en Internet (probablemente tras proxy), así que no conviene devolver la traza aunque sea parcial.

**Mitigación**: en prod, loggear y devolver un mensaje genérico (`"Internal error"`) sin `$e->getMessage()`.

#### SEC-03 · MEDIO — Inconsistencia de validación en `CentralTendencyEngine::normalizeData`
`src/CentralTendencyEngine.php:168-196` valida `is_numeric` pero **no** rechaza `NaN`/`INF`, a diferencia de `QuantileEngine::normalizeData` (`src/QuantileEngine.php:68-74`) y `DataProcessorTrait::validateData` (`src/Traits/DataProcessorTrait.php:21-27`).

Cuando se llama vía `RobustStats::getHuberMean()` el flujo pasa antes por `prepareData()` → validado. Pero si un consumidor externo llama directamente a `CentralTendencyEngine::huberMean($data)` con un `NaN` dentro, obtendrá `NaN` propagado silenciosamente. En v2.0.0 (CHANGELOG) se anunció explícitamente que *"NaN/Inf ahora lanzan InvalidDataSetException"* — esto es un hueco respecto a ese contrato.

**Mitigación**: añadir `if (!is_finite((float)$value))` en `CentralTendencyEngine::normalizeData`, alineando con `QuantileEngine`.

#### SEC-04 · BAJO — Validación de parámetros en `api.php`
- `trimPercent` se clampea a `[0.0, 0.45]` ✓
- `huberK` sólo se asegura `>= 0.1`, **sin cota superior**. Un `huberK` gigantesco (p. ej. `1e308`) no rompe nada (cutoff = k*scale, ya no clasifica outliers), pero un `huberK` negativo grande convertido a `0.1` por el `max` ya está cubierto. OK.
- No hay validación sobre `maxIterations` o `tolerance` del Huber: se usan siempre los defaults. OK (no expuestos).

### 2.3 Configuración

#### SEC-05 · MEDIO — CORS abierto en `api.php`
`Access-Control-Allow-Origin: *` en `web/public/api.php:15`. Para una demo pública sin auth y sin estado es aceptable, pero conviene documentarlo explícitamente. Si algún día el endpoint sirve cálculos facturables o cuotados, este header hay que restringirlo.

#### SEC-06 · BAJO — CDN externos sin Subresource Integrity (SRI)
`web/public/index.html:10-12` carga desde jsDelivr:
- `alpinejs@3.14.1/dist/cdn.min.js`
- `chart.js@4.4.4/dist/chart.umd.min.js`
- `chartjs-plugin-annotation@3.0.1/...`

Sin `integrity=` ni `crossorigin=`. Si jsDelivr o el paquete se comprometiesen, la demo queda expuesta a XSS/sup-chain. Es un playground, pero la mitigación es trivial (añadir hashes SHA-384) y sube la postura sin coste.

#### SEC-07 · BAJO — Sin cabeceras de seguridad en la demo
`api.php` no emite `X-Content-Type-Options: nosniff`, `Content-Security-Policy`, `Referrer-Policy`, `X-Frame-Options`. Para un JSON endpoint el único relevante es `X-Content-Type-Options: nosniff`. Para `index.html` una CSP mínima (self + jsdelivr + fonts.googleapis) cerraría el vector si mañana alguien inyecta markup.

#### SEC-08 · BAJO — APP_SECRET / .env
No aplica: no hay `.env` ni Symfony. El entorno Docker sólo usa env declarativo (`CHOWN_USER`, `STATGUARD_DEMO_BIND`, `STATGUARD_DEMO_PORT`). No se commitean secretos.

#### SEC-09 · BAJO — Puertos Docker expuestos al host
`docker-compose.yml` sólo expone la demo (bind a `127.0.0.1:8080` por defecto, configurable a `0.0.0.0` vía env) y los perfiles opcionales `apache` (80), `docs` (8000). No hay Postgres/Redis/Rabbit que bindear. OK.

#### SEC-10 · MEDIO — Rate limiting en `api.php`
**Inexistente**. Ver SEC-01 (mismo vector: cualquiera puede hacer `curl -XPOST … --data @huge.json` en bucle). Para la demo pública, un `nginx limit_req_zone` (1 req/s por IP) delante o un middleware sencillo en PHP cerraría el riesgo. No bloqueante mientras la demo no anuncie SLA.

### 2.4 Dependencias

#### SEC-11 · INFO — `composer audit` limpio
Sin CVEs. La superficie es mínima por diseño (cero deps de runtime).

#### SEC-12 · BAJO — `composer.lock` gitignorado
`.gitignore:5` excluye `/composer.lock`. Para una **librería** publicable esto es correcto (los consumidores no deben heredar tus pins). Pero tiene consecuencias que conviene apuntar:
- En CI, `composer update` regenera dependencias cada run. Hay riesgo de "test green hoy / rojo mañana" si una dep transitiva de dev rompe API.
- `composer audit` en CI corre contra versiones recién resueltas, no contra una lock commiteada.

**Mitigación opcional**: conservar un `composer.lock` **sólo para CI** (ignorado del paquete pero commiteado). Requiere dos flujos separados (`install` en CI, `require` en consumer). Alternativa pragmática: dejarlo como está y aceptar el drift (no hay tantas deps de dev).

#### SEC-13 · MEDIO — Docker image `phpdocumentor/phpdocumentor` sin pin
`.github/workflows/docs.yml:34` usa `phpdoc/phpdoc:3.5` (pinned minor) ✓. `docker-compose.yml:61` usa `phpdocumentor/phpdocumentor` **sin tag** → `:latest` implícito. Mismo patrón en `squidfunk/mkdocs-material` (línea 69). Baja reproducibilidad en local.

---

## 3. Auditoría de rendimiento

### 3.1 Base de datos, índices, N+1, migrations, JSONB

**N/A** en bloque — no hay persistencia.

### 3.2 Asincronía

**N/A** — no hay Messenger. El dataset del benchmark llega a 100 k floats y corre síncronamente en PHP puro; el performance shield actual reporta `4.7x faster than MathPHP` (mediana). Todo in-memory.

### 3.3 CPU / memoria (biblioteca)

#### PERF-01 · MEDIO — Varias pasadas redundantes en `RobustStats::getSummary`
`src/RobustStats.php:155-180` valida/ordena una vez (`prepareData($data, $sort)`), pero luego llama a `calculateIqr($prepared, TYPE_R_DEFAULT)` y por separado a `QuantileEngine::calculateSorted($prepared, 0.25, …)` y `$prepared, 0.75, …`. `calculateIqr` internamente también calcula Q1 y Q3 → efectivamente **cuatro cálculos de cuantiles** cuando bastan dos. Para n=100 k no domina (cuantiles son O(1) sobre dato ordenado) pero es simplificable.

#### PERF-02 · MEDIO — `StatsComparator::analyze` sobre datos ya procesados, reprocesa
`src/StatsComparator.php:31-38` llama a `prepareData()` con `$data`, pero luego pasa `$prepared` a métodos de `ClassicStats`/`RobustStats` que **vuelven a llamar a `prepareData()` internamente**. Se re-ordena varias veces. Misma crítica a `api.php:56-58` (`getSummary`, `getSummary`, `analyze` → triple validación+sort).

**Mitigación**: introducir un método interno `*FromPrepared(array $sortedValidated)` o una bandera `$alreadyProcessed` ya presente en el trait y aprovecharla en el comparator y en la ruta HTTP. Para n=100 k ahorra tres sorts completos.

#### PERF-03 · BAJO — `calculateMean` usando `array_sum` no es numéricamente estable
`ClassicStats::calculateMean` y equivalentes en los dos engines hacen `array_sum($data)/count($data)`. Para datasets muy grandes con escala mixta pierden precisión (acumulación float). No es un bug práctico para el caso de uso, pero limita la precisión declarada a ~15 dígitos. Kahan/Neumaier summation lo eleva a ~25. Baja prioridad.

#### PERF-04 · MEDIO — `calculateRobustDeviation` divide IQR por √n
`src/RobustStats.php:189-193`:
```php
return (1.25 / 1.35) * ($this->calculateIqr($data) / sqrt($n));
```
Esto calcula **el error estándar** de la mediana, no la "desviación robusta" entendida como estimador de σ. El nombre es ambiguo y el valor se compara con `stdDev` en `StatsComparator::dispersionComparison.noiseRatio` — probablemente introduce sesgo contra la librería (stdDev crece con σ; robustDeviation encoge con √n). Ver QUA-02.

#### PERF-05 · INFO — Benchmark cierra con 4.7x-5.1x vs MathPHP en mediana
Repo benchmark json vs shield.json divergen (`4.7x` en `statguard-perf.json` vs `5.1h` en `shield.json`). Ver QUA-07 (typo).

### 3.4 Cache / HTTP

- **Doctrine metadata / query cache**: N/A.
- **HTTP cache headers**: `api.php` es POST puro → irrelevante. `index.html` servido por `php -S` → sin `Cache-Control`; correcto para demo.

### 3.5 Observabilidad

- **Logging**: no hay logger. `api.php` no emite nada. Para una demo es aceptable, pero si pasa a producción con tráfico real conviene un PSR-3 + rotate.
- **Health check**: no existe. `GET /healthz` devolviendo `{ok:true, deps:{php:"8.3.x"}}` es 10 líneas y le da monitoreo externo sin ambigüedad.

---

## 4. Calidad de código y arquitectura

### 4.1 Separación de responsabilidades

- Controllers/services/entities: la librería no los tiene. La distinción interesante aquí es **fachada vs engine** y en general está bien: `ClassicStats`/`RobustStats` son fachadas, los cálculos duros viven en `QuantileEngine` y `CentralTendencyEngine`. El refactor v2.0.0 (`a4dc535`) unificó la mediana y eliminó duplicación. 👍

### 4.2 Tests multi-tenant

**N/A**.

### 4.3 Código muerto / inconsistencias

#### QUA-01 · MEDIO — Tres implementaciones de `normalizeData`/`validateData`
- `Traits/DataProcessorTrait::validateData` (usada por `ClassicStats`, `RobustStats`, `StatsComparator`)
- `QuantileEngine::normalizeData` (validación interna)
- `CentralTendencyEngine::normalizeData` (sin is_finite — SEC-03)

Las tres hacen casi lo mismo pero con matices distintos (mínimo 1 vs mínimo 2 valores, rechaza o no NaN). Unificar en un helper único en `Traits/` o un `DataValidator` final class baja la superficie de inconsistencias y arregla SEC-03 de un solo golpe.

#### QUA-02 · MEDIO — Naming ambiguo `robustDeviation` vs `robustVariance`
`RobustStats::getRobustDeviation` devuelve `(1.25/1.35) × IQR / √n` (error estándar de la mediana).
`RobustStats::getDeviation` devuelve `MAD × 1.4826` (σ robusto escalado).
`RobustStats::getSummary` usa `calculateRobustDeviation` (el primero).
`StatsInterface::getDeviation` expone el segundo.

En `getSummary` ambos valores son distintos y el nombre `robustDeviation` choca con el "deviation" del interface. Un lector del CSV/JSON exportado no sabe cuál es cuál. **Fix**: renombrar `calculateRobustDeviation` → `calculateMedianStandardError` y exponer `getRobustDeviation()` como MAD escalada (coherente con el interface). Breaking en la salida → 3.0.0.

#### QUA-03 · BAJO — `StatsComparator` usa `DataProcessorTrait` pero no lo aprovecha
`src/StatsComparator.php:15` importa el trait, llama a `prepareData` pero luego en el flujo de `analyze()` acaba re-entrando al validador 6 veces (vía `$classic->getMean`, `$classic->getStandardDeviation`, `$robust->getMedian`, `$robust->getDeviation`, y los dos `getOutliers`). Ver PERF-02.

#### QUA-04 · BAJO — `api.php` acepta valores no-finitos silenciosamente
`web/public/api.php:38-41` hace `array_filter(is_finite)` sobre los números **antes** de pasarlos a la librería. Eso significa que el endpoint **tolera** NaN/INF en la entrada (los descarta callado). Comportamiento distinto al de la librería (SEC-03). Decide una política y aplícala: o la API rechaza (422), o filtra *con aviso* en `inputCount` + `droppedCount`.

#### QUA-05 · BAJO — `QuantileEngine` es una clase estática accidentada
Es `final class` con sólo métodos `public static`. `api-reference.md:48-52` lo muestra como si fuese instanciable:
```php
$engine = new QuantileEngine();
$q7 = $engine->quantile($data, 0.75, 7);  // ← método inexistente
```
**El snippet de la docs no compila**. Hay que corregir a `QuantileEngine::calculate($data, 0.75, 7)`. Fix docs.

#### QUA-06 · BAJO — `DataProcessorTrait::prepareData` sobrevalidando
`prepareData` se llama también cuando el consumidor ya está dentro de un método que acaba de validar. Ningún *caller* usa las flags `$alreadyProcessed`/`$alreadySorted`. Las flags existen pero se quedan en el interfaz — o se usan (ver PERF-02) o se borran.

#### QUA-07 · MEDIO — `shield.json` tiene typo "5.1h faster than MathPHP"
`shield.json:4` dice `"5.1h"` donde claramente debería ser `"5.1x"`. No lo usa el workflow `performance.yml` (regenera `statguard-perf.json` de verdad vía `php tests/BenchmarkStatGuard.php json`), pero el fichero está tracked. O se borra del repo (genera dinámico) o se corrige. Actualmente `statguard-perf.json` (también tracked) dice `4.7x` → contradicción con `shield.json`.

#### QUA-08 · BAJO — `statguard-perf.json` tracked en git
`git ls-tree HEAD` lo incluye. Pero se regenera en cada build via `BenchmarkStatGuard.php json` y se publica a Gist (`performance.yml`). Tener un artifact generado en el repo es drift permanente; conviene gitignorarlo igual que hace con `statguard-coverage.json`.

#### QUA-09 · BAJO — CHANGELOG en inglés y español bien mantenidos
Ver `CHANGELOG.md` (v2.0.0 hoy 2026-04-16). Conventional Commits respetado en `git log` reciente (`feat:`, `fix:`, `docs:`, `chore:`, `refactor:`, `ci:`). 👍

#### QUA-10 · BAJO — `docs/decisions/` no existe
La plantilla lo daba por hecho. Si quieres llevar ADRs, se puede iniciar ahora con decisiones ya tomadas (política de NaN en v2.0.0, unificación de mediana, ExportableInterface…).

#### QUA-11 · BAJO — `web/Dockerfile` no limpia `apt`
`RUN apt-get update && apt-get install -y …` sin `&& rm -rf /var/lib/apt/lists/*`. Infla la imagen ~30-40 MB. También ausencia de `apt-get upgrade --no-install-recommends`. Higiene.

#### QUA-12 · BAJO — `web/entrypoint.sh` mezcla bilingüismo
Mensajes `echo` en español embebidos en el shell script (`"🔧 Configurando Apache…"`), mientras el resto del proyecto alterna ES/EN. Cosmético.

#### QUA-13 · BAJO — `tests/BenchmarkStatGuard.php` con globals
El benchmark genera `statguard-perf.json` como side-effect del formato `json` y reescribe `docs/benchmarks.md` / `.es.md` si se le pasa `report`. Cualquier contributor que ejecute `php tests/BenchmarkStatGuard.php json` local modifica tracked files. Encapsular el side-effect tras una flag `--write` ayuda.

#### QUA-14 · INFO — README gigante
`README.md:310 L`, duplica buena parte de `docs/getting-started.md` y `docs/api-reference.md`. Es política válida para Packagist (mostrar todo en el README), pero mantenerlos en sync manualmente se rompe (ver QUA-05 docs mismatch).

### 4.4 Pruebas

- **Política de validación negativa**: `testDataValidation` en tests de Classic/Robust cubre `<2`, no-numéricos, NaN, ±INF. ✓
- **Aislamiento multi-tenant**: N/A.
- **Parity R/StatGuard**: se corre sólo cuando hay R (`CentralTendencyEngine Test` marca skipped sin R). `QuantileEngine` tiene valores hardcoded para los 9 tipos (OK para no depender de R en CI ligero). Falta el equivalente hardcoded para Huber/Trimmed/Winsorized en el suite fast — ver MED-06.
- **Comparator**: 4 tests, cubren ALERT/CAUTION/STABLE/estructura. Razonable pero no cubre edge case "mediana ≈ 0" en biasPercent branch.

### 4.5 CI

- `php-tests.yml`: matriz PHP 8.1–8.5, coverage job aparte, shield publicado a Gist vía `GIST_TOKEN`. Correcto. **Atención**: `coverage` job no corre sobre la matrix entera; sólo PHP 8.3. Aceptable.
- `main.yml` (nombre confuso: "Robust Means CI") instala R y corre el subset con R. Limpio.
- `performance.yml`: publica shield con el número más bajo. Ver QUA-07.
- `docs.yml`: phpDocumentor → docs/api/ → mkdocs gh-deploy. Bien.
- `cleanup.yml`: borra deployments inactivos tras docs. OK.

Uso de action de tercero `exuanbo/actions-deploy-gist@v1` (no pinned a SHA). **CI-01 · BAJO**: pinear a commit SHA en actions críticas que manejen el `GIST_TOKEN` es buena práctica.

### 4.6 Documentación

- README y docs/*.md: al día excepto QUA-05 (snippet roto).
- `docs/decisions/`: ausente (QUA-10).
- `docs/audits/`: se crea con este informe.
- `docs/api/`: generado en el workflow (gitignored). ✓

---

## 5. Funcionalidades propuestas

Todas las propuestas **encajan con el dominio real**: una librería de estadística robusta para PHP + playground web.

### 5.1 Quick wins (< 1 día)

| ID    | Idea                                                                                                  | Por qué                                                                                   | Esfuerzo |
| ----- | ----------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------- | -------: |
| QW-01 | Añadir `is_finite` en `CentralTendencyEngine::normalizeData` (fix SEC-03)                             | Restaura el contrato v2.0.0 del CHANGELOG                                                 |    15 min |
| QW-02 | Límite `count($data) <= N` + 413 en `api.php` (fix SEC-01)                                            | Blindaje básico de la demo                                                                |    30 min |
| QW-03 | Corregir `shield.json` "5.1h" → "5.1x" + `.gitignore` sobre `statguard-perf.json` (QUA-07/08)         | Higiene de artifacts regenerados                                                          |    10 min |
| QW-04 | Corregir ejemplo roto en `docs/api-reference.md` (QUA-05)                                             | El snippet actual no compila                                                              |    15 min |
| QW-05 | Añadir `X-Content-Type-Options: nosniff` y ocultar `$e->getMessage()` en 500 (SEC-02/07)              | Reduce fuga sin romper nada                                                               |    20 min |
| QW-06 | SRI hashes para los 3 CDN en `index.html` (SEC-06)                                                    | Sube la postura de la demo                                                                |    20 min |
| QW-07 | `/healthz` en `api.php` (o fichero aparte) devolviendo `{ok:true, version:"2.0.0"}`                   | Observabilidad externa trivial                                                             |    20 min |
| QW-08 | `Makefile` minimal con `test`/`analyse`/`coverage`/`bench` (la plantilla del user lo sugiere)         | Uniformiza `make test` que el user ya invoca mentalmente                                   |    20 min |
| QW-09 | Unificar las 3 `normalizeData`/`validateData` en un `DataValidator` final class (QUA-01)              | Cierra SEC-03 y simplifica                                                                |     2 h  |
| QW-10 | Desduplicar sort/validate en `StatsComparator::analyze` usando `alreadyProcessed` (PERF-02)           | Gana 3 sorts completos en datasets grandes                                                |     2 h  |
| QW-11 | `docs/decisions/0001-nan-handling.md` + `0002-v2-breaking-interfaces.md` (QUA-10)                     | Arranca el repo de ADRs                                                                   |    1 h   |

### 5.2 Funcionalidades de valor (1-5 días)

#### FEAT-01 · Streaming / online estimators (Welford + P²) — 3 días
Qué resuelve: caso "datos llegan de sensor/telemetría y no caben en RAM" mencionado en tu README. Hoy StatGuard es batch puro.
API: `OnlineStats { push(float), mean, variance, count }` con Welford, y `OnlineQuantile { push, estimate(p) }` con P² (sin sort, O(1) por push).
Dependencias: ninguna nueva.
Diferenciación: MathPHP **no tiene** online estimators. Es una línea clara de separación.

#### FEAT-02 · Bootstrap confidence intervals — 2 días
Qué resuelve: hoy `getConfidenceIntervals` usa la aproximación normal (±1.96·s.e.). Para datos no-normales, el CI correcto es bootstrap percentil o BCa. Un parámetro de `resamples` (default 1000, seedable) da intervalos fiables sin asumir normalidad.
API: `$robust->getConfidenceIntervals($data, method: 'bootstrap', resamples: 1000, seed: 42)`.
Dependencias: ninguna.
Diferenciación: justificable como "data integrity" (tu tagline), porque el CI normal infla la confianza cuando hay outliers.

#### FEAT-03 · Skewness + kurtosis + test de normalidad — 2 días
Qué resuelve: el comparator hoy sólo dice ALERT/CAUTION/STABLE. Con asimetría + kurtosis + Shapiro-Wilk (n≤5000) el veredicto queda cuantificado y no heurístico.
API: extender `StatsComparator` con `distributionTest` y nuevas entradas en el JSON del playground.
Dependencias: ninguna (Shapiro-Wilk tiene implementación en PHP puro conocida).

#### FEAT-04 · Rolling / windowed stats — 3 días
Qué resuelve: dashboards tipo "últimos 60 s / 5 min / 1 h" sobre series temporales. Hoy el usuario tiene que partir el array y pasar cada ventana.
API: `RollingWindowStats(size: 60)::push($v) → snapshot`, con rebanado en O(1) amortizado.
Dependencias: ninguna.
Sinergia con FEAT-01 (mismas primitivas).

#### FEAT-05 · CSV/JSON/NDJSON reader con exportación a `Stats` — 2 días
Qué resuelve: hoy hay `toCsv`/`toJson` de salida, pero ninguna entrada. Los usuarios deben parsear manualmente. Un `DataSource::fromCsv('file.csv', column: 'latency_ms')` cierra el loop.
Dependencias: ninguna (usar `fopen` + `fgetcsv`).

#### FEAT-06 · PSR-3 logging + tracing hook — 1 día
Qué resuelve: permitir que una app grande vea cuántas iteraciones consumió Huber, cuándo se lanzó un `InvalidDataSetException`, tiempos de cada cálculo.
API: `setLogger(LoggerInterface $logger)` en las fachadas.
Dependencias: `psr/log:^3` (runtime).

#### FEAT-07 · Playground: export a PNG / SVG del histograma y "share URL" — 2 días
Qué resuelve: lo que hoy es efímero (subes datos → ves resultado) pasa a ser compartible (`?data=base64&k=1.345`). Útil para enseñar. Además export PNG del chart.js con marcas de Huber/media.
Dependencias: sólo front.

#### FEAT-08 · Test multiplataforma: tolerancias por precision `decimal` — 2 días
Qué resuelve: hoy los ejemplos R usan 17 dígitos, los de PHP 6-8 (pasan por `round()` en summaries). Una flag `exact: bool` que devuelva valores sin redondeo en `getSummary` ayuda a casos científicos. Y un modo `precision: 'bcmath'` usando la extensión `bcmath` (ya listada en Dockerfile) para datasets con escala extrema.
Dependencias: `ext-bcmath` opcional.

### 5.3 Ideas a futuro (post-MVP)

- **Biblioteca hermana `StatGuardML`**: detectores de drift (Kolmogorov-Smirnov, Population Stability Index) para ML ops en PHP, que use StatGuard como base.
- **Integración con Laravel / Symfony**: bundle/service provider que registre `RobustStats`/`ClassicStats` como singletons + facade. Tratar en dos repos separados (`cjuol/statguard-laravel`, `cjuol/statguard-symfony-bundle`) para no cargar deps en el core.
- **SIMD via FFI**: para datasets enormes (>1M) se puede invocar a GSL o BLAS vía FFI y caer 50×. Alto coste de mantenimiento; valorar sólo si aparece demanda real.
- **Vertical "observabilidad"**: preset del playground con curvas de latencia P99/P99.9 y detector de regresión automática.
- **Vertical "finanzas"**: detectores de outliers en series temporales con rolling Huber y máximos drawdown robustos.
- **Playground server-side con tenants**: ya con auth (API key), cuotas, histórico de análisis. Aquí sí encaja Symfony + multi-tenant, y la plantilla de auditoría original volvería a ser pertinente.
- **Python / Rust bindings**: FFI-ready engines portables (QuantileEngine en particular es puro matemático, recompilable).
- **Artefacto único "verdict engine"**: dado un dataset, devuelve una puntuación 0-100 de "calidad", con umbrales configurables. Ya tienes el 80 % del trabajo en `StatsComparator`.

---

## 6. Plan de acción priorizado

Orden: severidad desc, dentro de la misma severidad esfuerzo asc. Columna `Estado` actualizada el 2026-04-16 tras el primer lote de fixes.

Leyenda de estado: ✅ resuelto (con commit asociado) · 🟡 parcial · ⏸ pausado pendiente de decisión · ⬜ pendiente.

| ID      | Categoría   | Severidad | Descripción corta                                                      | Archivos                                                    | Esfuerzo | Fase sugerida | Estado |
| ------- | ----------- | --------- | ---------------------------------------------------------------------- | ----------------------------------------------------------- | -------: | ------------- | ------ |
| SEC-01  | seguridad   | ALTO      | Cap de tamaño de `data[]` en `api.php` + HTTP 413                      | `web/public/api.php`                                        |    30 min | v2.0.1        | ✅ `c2b7c71` |
| SEC-03  | seguridad   | ALTO      | `CentralTendencyEngine::normalizeData` debe rechazar NaN/INF           | `src/CentralTendencyEngine.php`, `tests/CentralTendencyEngineTest.php` |    15 min | v2.0.1        | ✅ `8a96a4a` |
| QUA-01  | calidad     | MEDIO     | Unificar `normalizeData`/`validateData` en `DataValidator`             | `src/Support/DataValidator.php`, `src/Traits/DataProcessorTrait.php`, `src/QuantileEngine.php`, `src/CentralTendencyEngine.php` |     2 h  | v2.1.0        | ✅ `0536115` |
| QUA-02  | calidad     | MEDIO     | Renombrar `robustDeviation` para desambiguar MAD-σ vs SE(mediana)      | `src/RobustStats.php`, docs, `web/public/index.html` labels |     3 h  | v3.0.0        | ⏸ breaking, espera v3 |
| SEC-02  | seguridad   | MEDIO     | No exponer `$e->getMessage()` en 500                                   | `web/public/api.php`                                        |    15 min | v2.0.1        | ✅ `28e1fc4` |
| SEC-05  | seguridad   | MEDIO     | Restringir CORS (o documentar decisión)                                | `web/public/api.php`                                        |    20 min | v2.1.0        | ⏸ pendiente decisión |
| SEC-10  | seguridad   | MEDIO     | Rate limiting en `api.php` (o front nginx)                             | `web/public/api.php`, `docker-compose.yml`                  |     3 h  | v2.2.0        | ⏸ pendiente decisión (PHP vs proxy) |
| SEC-13  | seguridad   | MEDIO     | Pinear `phpdocumentor/phpdocumentor` y `squidfunk/mkdocs-material` tag | `docker-compose.yml`                                        |    10 min | v2.0.1        | ✅ `f41733b` |
| PERF-01 | rendimiento | MEDIO     | Evitar 4 cálculos de cuantil en `getSummary`                          | `src/RobustStats.php`                                       |     1 h  | v2.1.0        | ✅ `5d82fd4` |
| PERF-02 | rendimiento | MEDIO     | Desduplicar sort/validate en `StatsComparator` y `api.php`             | `src/StatsComparator.php`, `web/public/api.php`             |     2 h  | v2.1.0        | 🟡 `660c79d` (StatsComparator hecho; api.php pendiente de decisión sobre API pública) |
| QUA-07  | calidad     | MEDIO     | Fix typo `"5.1h"` en `shield.json` o regenerarlo                       | `shield.json`                                               |     5 min | v2.0.1        | ✅ `b4fdadb` |
| QUA-04  | calidad     | MEDIO     | Política única NaN/INF entre librería y `api.php`                      | `web/public/api.php`                                        |    30 min | v2.0.1        | ✅ `4eda9bf` |
| MED-06  | calidad     | MEDIO     | Test parity Huber/Trimmed/Winsorized con valores hardcoded (sin R)     | `tests/CentralTendencyEngineTest.php`                       |     2 h  | v2.1.0        | ⬜ |
| QW-07   | feature     | MEDIO     | Endpoint `/healthz`                                                    | `web/public/api.php` (o nuevo fichero)                      |    20 min | v2.1.0        | ⬜ |
| SEC-06  | seguridad   | BAJO      | SRI hashes en los 3 CDN                                                | `web/public/index.html`                                     |    20 min | v2.0.1        | ⬜ |
| SEC-07  | seguridad   | BAJO      | `X-Content-Type-Options: nosniff` + CSP mínima                         | `web/public/api.php`, `index.html`                          |    30 min | v2.0.1        | ⬜ |
| SEC-04  | seguridad   | BAJO      | Doc explícita de límites de `huberK`/`trimPercent`                     | `docs/api-reference.md`                                     |    15 min | v2.0.1        | ⬜ |
| SEC-12  | seguridad   | BAJO      | Decidir política sobre `composer.lock` en CI                           | `.gitignore`, CI                                            |     1 h  | v2.1.0        | ⬜ |
| CI-01   | seguridad   | BAJO      | Pinear `exuanbo/actions-deploy-gist` a SHA                             | `.github/workflows/*.yml`                                   |    10 min | v2.0.1        | ⬜ |
| QUA-05  | calidad     | BAJO      | Fix snippet roto en `docs/api-reference.md`                            | `docs/api-reference.md`                                     |    15 min | v2.0.1        | ⬜ |
| QUA-08  | calidad     | BAJO      | `.gitignore` para `statguard-perf.json`                                | `.gitignore`                                                |     5 min | v2.0.1        | ✅ incluido en `b4fdadb` |
| QUA-11  | calidad     | BAJO      | Limpiar `apt` lists en Dockerfile                                      | `web/Dockerfile`                                            |     5 min | v2.1.0        | ⬜ |
| QUA-12  | calidad     | BAJO      | Homogeneizar idioma en `web/entrypoint.sh`                             | `web/entrypoint.sh`                                         |    10 min | v2.1.0        | ⬜ |
| QUA-13  | calidad     | BAJO      | Encapsular side-effects del benchmark tras `--write`                  | `tests/BenchmarkStatGuard.php`                              |    30 min | v2.1.0        | ⬜ |
| QW-08   | herramienta | BAJO      | `Makefile` minimal                                                     | `Makefile` (nuevo)                                          |    20 min | v2.0.1        | ⬜ |
| QW-11   | docs        | BAJO      | Iniciar `docs/decisions/` con 2 ADRs retroactivos                      | `docs/decisions/`                                           |     1 h  | v2.1.0        | ⬜ |
| FEAT-01 | feature     | —         | Streaming estimators (Welford + P²)                                    | nuevo `src/Online/`                                         |     3 d  | v2.2.0        | ⬜ |
| FEAT-02 | feature     | —         | Bootstrap CIs                                                          | `src/RobustStats.php`, `src/BootstrapEngine.php` (nuevo)    |     2 d  | v2.2.0        | ⬜ |
| FEAT-03 | feature     | —         | Skewness/kurtosis + Shapiro-Wilk                                       | `src/ClassicStats.php`, `src/DistributionTests.php` (nuevo) |     2 d  | v2.2.0        | ⬜ |
| FEAT-04 | feature     | —         | Rolling windowed stats                                                 | nuevo `src/Online/RollingWindow.php`                        |     3 d  | v2.3.0        | ⬜ |
| FEAT-05 | feature     | —         | CSV/JSON/NDJSON reader                                                 | nuevo `src/IO/`                                             |     2 d  | v2.3.0        | ⬜ |
| FEAT-06 | feature     | —         | PSR-3 logger hook                                                      | fachadas + `composer.json`                                  |     1 d  | v2.3.0        | ⬜ |
| FEAT-07 | feature     | —         | Playground: share URL + export PNG                                     | `web/public/index.html`                                     |     2 d  | v2.2.0        | ⬜ |
| FEAT-08 | feature     | —         | Modo `exact`/`bcmath`                                                  | fachadas                                                    |     2 d  | v3.0.0        | ⬜ |

### 6.1 Changelog del lote aplicado (2026-04-16)

| # | Commit    | Ítem            | Resumen                                                                   |
| - | --------- | --------------- | ------------------------------------------------------------------------- |
| 1 | `c2b7c71` | SEC-01          | Cap de 50 000 puntos en `api.php` + HTTP 413 con `limit`+`received`.      |
| 2 | `8a96a4a` | SEC-03          | `is_finite` en `CentralTendencyEngine::normalizeData` + tests dedicados.  |
| 3 | `28e1fc4` | SEC-02          | Sustituye `$e->getMessage()` por `'Internal error.'` y loggea `error_log`. |
| 4 | `4eda9bf` | QUA-04          | Elimina el pre-filtro silencioso de NaN/INF; la librería rechaza con 422. |
| 5 | `f41733b` | SEC-13          | Pin `phpdocumentor/phpdocumentor:3.5` y `squidfunk/mkdocs-material:9.5.44`. |
| 6 | `b4fdadb` | QUA-07 + QUA-08 | Fix typo `5.1h→5.1x` en `shield.json`; untrack `statguard-perf.json`.     |
| 7 | `0536115` | QUA-01          | Nuevo `src/Support/DataValidator.php`; trait + engines delegan ahí.       |
| 8 | `5d82fd4` | PERF-01         | `RobustStats::getSummary` reutiliza q1/q3/mediana (de 4 cuantiles a 2).   |
| 9 | `660c79d` | PERF-02 (parcial) | `StatsComparator::analyze` inline con `QuantileEngine`; constructor BC.  |

Resultado post-lote (verificado):
- **Tests**: `94 passed · 3 skipped (R) · 0 failed` (suite ampliada con 9 tests de non-finite para `CentralTendencyEngine`).
- **PHPStan**: limpio en nivel 5.
- **`composer audit`**: sin advisories.
- **Contrato v2.0.0 restaurado**: cualquier entrada NaN/INF vía fachadas, engines o `api.php` termina en `InvalidDataSetException` (HTTP 422 en la demo).
- **Superficie de DoS** acotada en la demo: payloads > 50 k puntos devuelven 413 sin tocar la librería.

---

## 7. Resumen ejecutivo

### Instantánea inicial (mañana 2026-04-16)

- **Estado global**: saludable. Tests verdes, PHPStan L5 limpio, `composer audit` limpio, cobertura 74 % líneas.
- **Riesgos reales hoy**: (a) `api.php` de la demo pública puede ser DoS-eado con un POST grande, (b) `CentralTendencyEngine` no rechaza NaN/INF y rompe el contrato anunciado en el CHANGELOG v2.0.0, (c) inconsistencia semántica en `robustDeviation` que puede confundir consumidores.
- **Deuda técnica acotada**: duplicación de validación entre 3 sitios (QUA-01), sort/validate redundantes en el pipeline (PERF-01/02), doc-snippet roto (QUA-05), `shield.json` con typo (QUA-07).
- **Oportunidades de diferenciación claras**: streaming estimators (FEAT-01) y bootstrap CIs (FEAT-02) son features que MathPHP no cubre y encajan con el tagline "data integrity".
- **Ningún hallazgo de severidad CRÍTICO**. Dos ALTOS, corregibles en < 1 h cada uno, ambos candidatos a v2.0.1 patch.

### Post-lote (tarde 2026-04-16)

- Ambos ítems ALTO resueltos: `c2b7c71` (SEC-01) y `8a96a4a` (SEC-03). La demo ya no se puede DoS-ear con un POST gigante, y la política NaN/INF es coherente en toda la librería.
- 7 ítems MEDIO resueltos y 1 parcial (ver tabla §6).
- **Tests 94 pass · 3 skipped (R) · 0 failed** · **PHPStan L5 limpio** · **composer audit limpio**.
- Preparado para etiquetar **v2.0.1**: todos los fixes de severidad ALTO + la mayor parte de los MEDIO del patch están commiteados.

### Pendientes con decisión de producto

- **SEC-05 CORS**: abierto vs restringido. Sin decisión técnica en el código.
- **SEC-10 rate limit**: PHP middleware vs proxy delante. 3 h en cualquier caso.
- **PERF-02 lado `api.php`**: requiere exponer `$alreadyProcessed` en las fachadas. Pequeña ampliación de API pública.
- **QUA-02 rename `robustDeviation`**: breaking → v3.0.0.

### Plan recomendado (post-lote)

1. **v2.0.1 (listo para tag)**: SEC-01, SEC-03, SEC-02, QUA-04, QUA-07/08, SEC-13 ✅. Opcional para cerrar patch: SEC-06, SEC-07, QUA-05, CI-01, QW-08 (todos ⬜, baja fricción).
2. **v2.1.0 (en progreso)**: QUA-01, PERF-01, PERF-02 parcial ✅. Queda: MED-06, SEC-05, SEC-10, QUA-11/12/13, QW-11, cierre de PERF-02 (`api.php`).
3. **v2.2.0 (features)**: FEAT-01, FEAT-02, FEAT-03, FEAT-07.
4. **v3.0.0 (breaking sane)**: QUA-02 (rename), FEAT-08.

Si hubiera que priorizar una sola cosa esta semana, cerrar v2.0.1 con los ítems BAJO listados arriba y publicar el patch.
