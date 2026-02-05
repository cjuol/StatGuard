<?php

declare(strict_types=1);

namespace Cjuol\SymmetricalOcto\Traits;

trait DataProcessorTrait
{
    private function validarDatos(array $datos): array
    {
        if (count($datos) < 2) {
            throw new \InvalidArgumentException("Se necesitan al menos 2 valores numéricos.");
        }

        $datosLimpios = array_values(array_filter($datos, 'is_numeric'));
        
        if (count($datosLimpios) !== count($datos)) {
            throw new \InvalidArgumentException("Todos los valores de la muestra deben ser numéricos.");
        }

        return $datosLimpios;
    }

    private function prepararDatos(array $datos, bool $ordenar = true): array
    {
        $datosProcesados = $this->validarDatos($datos);
        if ($ordenar) {
            sort($datosProcesados);
        }
        return $datosProcesados;
    }
}