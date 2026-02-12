# FAQ

## Por que mis resultados difieren de R

- Verifica que el tipo de cuantil sea el mismo (1-9).
- Asegurate de ordenar y limpiar los datos igual.
- Confirma que estas usando los mismos decimales y redondeo.

## Que tamanio minimo de dataset necesito

No hay minimo tecnico, pero:
- Con menos de 5-7 valores, la varianza es poco estable.
- Para detectar outliers, se recomiendan mas de 20 valores.

## Cuando usar Huber vs mediana

- Huber conserva eficiencia cuando los datos son limpios.
- Mediana es mas resistente si hay extremos muy agresivos.

## Puedo exportar resultados para auditoria

Si, usa `toJson()` o `toCsv()` en ClassicStats o RobustStats.
