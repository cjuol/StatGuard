# FAQ

## Por que mis resultados difieren de R

- Verifica que el tipo de cuantil sea el mismo (1-9). R usa type 7 por defecto; StatGuard expone los nueve.
- Asegurate de ordenar y limpiar los datos igual (filtrado de NaN/Inf, coercion de strings).
- Confirma que usas los mismos decimales y redondeo.
- Para Huber, revisa la constante de ajuste `k` (StatGuard usa `1.345`, igual que `MASS::huber`).

## Que tamanio minimo de dataset necesito

No hay minimo tecnico, pero:

- Con menos de 5-7 valores la varianza es poco estable y los estimadores robustos degeneran.
- Para detectar outliers de forma fiable se recomiendan mas de 20 valores.
- Para que Huber M-estimator converja limpio, apunta a 30+ puntos.

## Cuando usar Huber en vez de la mediana

- **Huber M-estimator**: conserva la eficiencia de la media cuando los datos son mayormente limpios pero hay algunos extremos. Buen default para monitoreo/telemetria.
- **Mediana**: maxima robustez cuando una fraccion grande (hasta 50%) esta contaminada. Usala cuando los outliers son la norma, no la excepcion.
- **Media recortada**: si conoces la tasa de contaminacion (por ejemplo "quitar 10% de arriba y abajo").
- **Media winsorizada**: si quieres mantener el tamanio de muestra pero acotar el efecto de los extremos.

## Por que usar MAD en vez de desviacion estandar

La desviacion estandar eleva al cuadrado las diferencias, asi un solo outlier puede inflarla ordenes de magnitud. MAD (Mediana de Desviaciones Absolutas) usa la mediana de `|x - median(x)|`, escalada por `1.4826` para consistencia gausiana. Tiene un punto de ruptura del 50% frente al 0% de la stddev: un solo dato malo no la distorsiona.

## Que tipo de cuantil elegir

- **Tipo 7** (default en R/NumPy): interpolacion lineal, mejor eleccion general.
- **Tipos 1-3**: discretos, devuelven valores observados. Utiles para auditoria/forense donde necesitas un valor real del dataset.
- **Tipos 4-9**: continuos, interpolados. Hyndman y Fan (1996) recomiendan los tipos 8 y 9 para estimadores insesgados en muestras pequenias.

## Puedo exportar resultados para auditoria

Si. Llama `toJson()` o `toCsv()` en `ClassicStats` o `RobustStats`. Ambos metodos incluyen todas las metricas calculadas junto con la firma del dataset de entrada para que los resultados sean reproducibles.

## StatGuard muta mi array de entrada

No. Todas las operaciones trabajan sobre una copia defensiva. Puedes pasar el mismo array a varios metodos sin problema.

## Como funciona el veredicto de sesgo

`StatsComparator` compara estimadores clasicos vs robustos y emite un veredicto (`CLEAN`, `NOISE`, `BIAS`) segun el gap relativo entre `mean` y `huberMean` (umbral por defecto 5%). Puedes sobreescribir el umbral en el constructor para auditorias mas estrictas o permisivas.

## StatGuard requiere R para funcionar

No. R se usa solo en el script de validacion (`scripts/validate_with_r.php`) para verificar paridad numerica durante desarrollo y CI. La libreria en si no tiene dependencia externa de otro lenguaje.

## Como regenero las tablas de benchmark

```bash
php tests/BenchmarkStatGuard.php report
```

Actualiza `README.md` y `docs/benchmarks.md` con mediciones frescas. Requiere R instalado localmente o ejecutado via `docker compose --profile performance up benchmark`.
