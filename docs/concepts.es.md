# Conceptos (para dummys)

Estas ideas te ayudan a escoger la estadistica correcta sin entrar en teoria
pesada.

## Media vs mediana vs Huber

Piensa en 6 mediciones y un error grande:

```text
[10, 12, 11, 15, 10, 1000]
```

- Media: sube mucho por el 1000.
- Mediana: se queda en el centro real.
- Huber: se parece a la media cuando todo esta limpio, pero "frena" outliers.

Regla simple:
- Usa media si los datos son limpios.
- Usa mediana o Huber si hay outliers.

## MAD e IQR

- MAD (Median Absolute Deviation) mide dispersion alrededor de la mediana.
- IQR (Interquartile Range) mide el rango entre el 25% y 75%.

Si el MAD o el IQR son altos, hay mucho ruido o colas largas.

## Cuantiles de R

R define 9 formas de calcular cuantiles. StatGuard implementa todas.

- Tipo 7: default de R. Buen equilibrio.
- Tipos 1-3: mas discretos (menos interpolacion).
- Tipos 8-9: ajustes de sesgo para ciertas distribuciones.

Si no sabes cual usar, empieza por tipo 7.
