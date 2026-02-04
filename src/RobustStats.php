<?php

declare(strict_types=1);

namespace Cjuol\SymmetricalOcto;

/**
 * RobustStats - Biblioteca de Estadística Robusta
 * * Implementa cálculos resistentes a valores atípicos (outliers) 
 * utilizando S* y el Rango Intercuartílico (IQR).
 */
class RobustStats
{
    /**
     * Valida que los datos sean procesables.
     */
    private function validarDatos(array $datos): void
    {
        if (count($datos) < 2) {
            throw new \InvalidArgumentException("Se necesitan al menos 2 valores para los cálculos.");
        }

        foreach ($datos as $valor) {
            if (!is_numeric($valor)) {
                throw new \InvalidArgumentException("Todos los valores deben ser numéricos.");
            }
        }
    }

    public function calcularMedia(array $datos): float
    {
        $this->validarDatos($datos);
        return array_sum($datos) / count($datos);
    }

    public function calcularMediana(array $datos): float
    {
        $this->validarDatos($datos);
        $copia = $datos;
        sort($copia);
        $n = count($copia);
        $medio = floor(($n - 1) / 2);

        if ($n % 2) {
            return (float) $copia[(int)$medio];
        }

        return ($copia[(int)$medio] + $copia[(int)$medio + 1]) / 2.0;
    }

    /**
     * Calcula S* (Desviación Robusta)
     * Fórmula: S* = (1.25 / 1.35) × (IQR / √n)
     */
    public function calcularDesviacionRobusta(array $datos): float
    {
        $this->validarDatos($datos);
        $n = count($datos);
        $iqr = $this->calcularIQR($datos);
        
        return (1.25 / 1.35) * ($iqr / sqrt($n));
    }

    /**
     * Calcula la Varianza Robusta (Cuadrado de S*)
     */
    public function calcularVarianzaRobusta(array $datos): float
    {
        return pow($this->calcularDesviacionRobusta($datos), 2);
    }

    /**
     * Calcula el Coeficiente de Variación Robusto (CVr%) basado en la Mediana.
     */
    public function calcularCVr(array $datos): float
    {
        $mediana = $this->calcularMediana($datos);
        if ($mediana == 0) return 0.0;

        $desviacionRobusta = $this->calcularDesviacionRobusta($datos);
        return ($desviacionRobusta / $mediana) * 100;
    }

    /**
     * Calcula el Rango Intercuartílico (IQR).
     */
    public function calcularIQR(array $datos): float
    {
        $this->validarDatos($datos);
        $copia = $datos;
        sort($copia);
        
        $q1 = $this->getPercentil($copia, 25);
        $q3 = $this->getPercentil($copia, 75);

        return $q3 - $q1;
    }

    /**
     * Calcula la Desviación Absoluta de la Mediana (MAD).
     */
    public function calcularMAD(array $datos): float
    {
        $this->validarDatos($datos);
        $mediana = $this->calcularMediana($datos);
        $desviaciones = array_map(fn($x) => abs($x - $mediana), $datos);
        
        return $this->calcularMediana($desviaciones);
    }

    /**
     * Detecta valores fuera de los límites de Tukey (Outliers).
     */
    public function detectarOutliers(array $datos): array
    {
        $this->validarDatos($datos);
        $iqr = $this->calcularIQR($datos);
        $copia = $datos;
        sort($copia);
        
        $q1 = $this->getPercentil($copia, 25);
        $q3 = $this->getPercentil($copia, 75);

        $limiteInf = $q1 - 1.5 * $iqr;
        $limiteSup = $q3 + 1.5 * $iqr;

        return array_values(array_filter($datos, fn($x) => $x < $limiteInf || $x > $limiteSup));
    }

    /**
     * Calcula Intervalos de Confianza al 95%.
     */
    public function calcularIntervalosConfianza(array $datos): array
    {
        $mediana = $this->calcularMediana($datos);
        $sEstrella = $this->calcularDesviacionRobusta($datos);
        $margen = 1.96 * $sEstrella;
        
        return [
            'superior' => $mediana + $margen,
            'inferior' => $mediana - $margen,
        ];
    }

    private function getPercentil(array $datosOrdenados, int $percentil): float
    {
        $index = ($percentil / 100) * (count($datosOrdenados) - 1);
        $low = floor($index);
        $high = ceil($index);
        $fraction = $index - $low;

        return $datosOrdenados[$low] + $fraction * ($datosOrdenados[$high] - $datosOrdenados[$low]);
    }
}