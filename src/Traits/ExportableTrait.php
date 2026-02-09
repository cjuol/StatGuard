<?php

declare(strict_types=1);

namespace Cjuol\StatGuard\Traits;

trait ExportableTrait
{
    /**
     * Exporta el resumen estadístico a formato JSON.
     */
    public function toJson(array $datos, int $options = JSON_PRETTY_PRINT): string
    {
        return json_encode($this->obtenerResumen($datos), $options);
    }

    /**
     * Exporta el resumen estadístico a formato CSV.
     * @return string Contenido del CSV (Cabecera + Valores)
     */
    public function toCsv(array $datos, string $delimiter = ","): string
    {
        $resumen = $this->obtenerResumen($datos);
        
        // Aplanamos campos que sean arrays (outliers, intervalos, etc.)
        $datosParaCsv = [];
        foreach ($resumen as $key => $value) {
            if (is_array($value)) {
                // Convertimos el array a una cadena separada por pipes |
                $datosParaCsv[$key] = empty($value) ? '' : implode('|', $value);
            } else {
                $datosParaCsv[$key] = $value;
            }
        }

        $handle = fopen('php://temp', 'r+');
        
        // 1. Insertar Cabeceras
        fputcsv($handle, array_keys($datosParaCsv), $delimiter);
        
        // 2. Insertar Datos
        fputcsv($handle, array_values($datosParaCsv), $delimiter);
        
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        if ($csv === false) {
            throw new \RuntimeException('Failed to read CSV content from temporary stream');
        }

        return $csv;
    }
}