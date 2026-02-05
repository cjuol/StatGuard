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
    // ========== FUNCIONES PÚBLICAS - INTERFAZ PÚBLICA ==========

    /**
     * Calcula la media aritmética de los datos.
     *
     * @param array $datos Array de valores numéricos
     * @param bool $ordenar Si true, ordena los datos antes de procesar (default: true)
     * @return float La media aritmética
     * @throws \InvalidArgumentException Si los datos no son válidos
     */
    public function getMedia(array $datos, bool $ordenar = true): float
    {
        return $this->calcularMedia($this->prepararDatos($datos, $ordenar));
    }

    /**
     * Calcula la mediana de los datos.
     *
     * @param array $datos Array de valores numéricos
     * @param bool $ordenar Si true, ordena los datos antes de procesar (default: true)
     * @return float La mediana
     * @throws \InvalidArgumentException Si los datos no son válidos
     */
    public function getMediana(array $datos, bool $ordenar = true): float
    {
        return $this->calcularMediana($this->prepararDatos($datos, $ordenar));
    }

    /**
     * Calcula la desviación robusta (S*) resistente a outliers.
     *
     * @param array $datos Array de valores numéricos
     * @param bool $ordenar Si true, ordena los datos antes de procesar (default: true)
     * @return float La desviación robusta
     * @throws \InvalidArgumentException Si los datos no son válidos
     */
    public function getDesviacionRobusta(array $datos, bool $ordenar = true): float
    {
        return $this->calcularDesviacionRobusta($this->prepararDatos($datos, $ordenar));
    }

    /**
     * Calcula la varianza robusta (cuadrado de S*).
     *
     * @param array $datos Array de valores numéricos
     * @param bool $ordenar Si true, ordena los datos antes de procesar (default: true)
     * @return float La varianza robusta
     * @throws \InvalidArgumentException Si los datos no son válidos
     */
    public function getVarianzaRobusta(array $datos, bool $ordenar = true): float
    {
        return $this->calcularVarianzaRobusta($this->prepararDatos($datos, $ordenar));
    }

    /**
     * Calcula el Coeficiente de Variación Robusto (CVr%).
     *
     * @param array $datos Array de valores numéricos
     * @param bool $ordenar Si true, ordena los datos antes de procesar (default: true)
     * @return float El coeficiente de variación robusto en porcentaje
     * @throws \InvalidArgumentException Si los datos no son válidos
     */
    public function getCVr(array $datos, bool $ordenar = true): float
    {
        return $this->calcularCVr($this->prepararDatos($datos, $ordenar));
    }

    /**
     * Calcula el Rango Intercuartílico (IQR).
     *
     * @param array $datos Array de valores numéricos
     * @param bool $ordenar Si true, ordena los datos antes de procesar (default: true)
     * @return float El rango intercuartílico (Q3 - Q1)
     * @throws \InvalidArgumentException Si los datos no son válidos
     */
    public function getIQR(array $datos, bool $ordenar = true): float
    {
        return $this->calcularIQR($this->prepararDatos($datos, $ordenar));
    }

    /**
     * Calcula la Desviación Absoluta de la Mediana (MAD).
     *
     * @param array $datos Array de valores numéricos
     * @param bool $ordenar Si true, ordena los datos antes de procesar (default: true)
     * @return float La desviación absoluta de la mediana
     * @throws \InvalidArgumentException Si los datos no son válidos
     */
    public function getMAD(array $datos, bool $ordenar = true): float
    {
        return $this->calcularMAD($this->prepararDatos($datos, $ordenar));
    }

    /**
     * Detecta valores atípicos (outliers) usando el método de Tukey.
     *
     * @param array $datos Array de valores numéricos
     * @param bool $ordenar Si true, ordena los datos antes de procesar (default: true)
     * @return array Array de valores identificados como outliers
     * @throws \InvalidArgumentException Si los datos no son válidos
     */
    public function getOutliers(array $datos, bool $ordenar = true): array
    {
        return $this->detectarOutliers($this->prepararDatos($datos, $ordenar));
    }

    /**
     * Calcula los intervalos de confianza al 95% basados en la mediana.
     *
     * @param array $datos Array de valores numéricos
     * @param bool $ordenar Si true, ordena los datos antes de procesar (default: true)
     * @return array Array asociativo con claves 'superior' e 'inferior'
     * @throws \InvalidArgumentException Si los datos no son válidos
     */
    public function getIntervalosConfianza(array $datos, bool $ordenar = true): array
    {
        return $this->calcularIntervalosConfianza($this->prepararDatos($datos, $ordenar));
    }

    /**
     * Obtiene un resumen completo de todas las métricas estadísticas robustas.
     *
     * @param array $datos Array de valores numéricos
     * @param bool $ordenar Si true, ordena los datos antes de procesar (default: true)
     * @param int $decimales Número de decimales para redondear (default: 2)
     * @return array Array asociativo con todas las métricas calculadas
     * @throws \InvalidArgumentException Si los datos no son válidos
     */
    public function obtenerResumen(array $datos, bool $ordenar = true, int $decimales = 2): array
    {
        $datosPreparados = $this->prepararDatos($datos, $ordenar);
        
        return [
            'media' => round($this->calcularMedia($datosPreparados), $decimales),
            'mediana' => round($this->calcularMediana($datosPreparados), $decimales),
            'desviacionRobusta' => round($this->calcularDesviacionRobusta($datosPreparados), $decimales),
            'varianzaRobusta' => round($this->calcularVarianzaRobusta($datosPreparados), $decimales),
            'CVr' => round($this->calcularCVr($datosPreparados), $decimales),
            'IQR' => round($this->calcularIQR($datosPreparados), $decimales),
            'MAD' => round($this->calcularMAD($datosPreparados), $decimales),
            'outliers' => $this->detectarOutliers($datosPreparados),
            'intervalosConfianza' => $this->calcularIntervalosConfianza($datosPreparados),
        ];
    }

    // ========== FUNCIONES PRIVADAS - MOTOR DE CÁLCULO ==========

    /**
     * Valida que los datos sean procesables.
     *
     * @param array $datos Array de valores a validar
     * @return array Array de valores limpios y validados
     * @throws \InvalidArgumentException Si hay menos de 2 valores o contiene no-numéricos
     */
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

    /**
     * Prepara los datos para el análisis, validando y opcionalmente ordenando.
     *
     * @param array $datos Array de valores numéricos
     * @param bool $ordenar Si true, ordena los datos en orden ascendente
     * @return array Array de datos validados y opcionalmente ordenados
     * @throws \InvalidArgumentException Si los datos no son válidos
     */
    private function prepararDatos(array $datos, bool $ordenar = true): array
    {
        $datosLimpios = $this->validarDatos($datos);

        if ($ordenar) {
            sort($datosLimpios);
        }

        return $datosLimpios;
    }

    /**
     * Calcula la media aritmética.
     *
     * @param array $datos Array de valores numéricos
     * @return float La media aritmética
     */
    private function calcularMedia(array $datos): float
    {
        return array_sum($datos) / count($datos);
    }

    /**
     * Calcula la mediana (valor central).
     *
     * @param array $datos Array de valores numéricos ordenados
     * @return float La mediana
     */
    private function calcularMediana(array $datos): float
    {
        $n = count($datos);
        $m = intdiv($n, 2);

        if ($n % 2 === 0) {
            return ($datos[$m - 1] + $datos[$m]) / 2.0;
        }
        return (float) $datos[$m];
    }

    /**
     * Calcula S* (Desviación Robusta).
     * 
     * Fórmula: S* = (1.25 / 1.35) × (IQR / √n)
     * 
     * Esta medida es resistente a valores atípicos (outliers).
     *
     * @param array $datos Array de valores numéricos ordenados
     * @return float La desviación robusta
     */
    private function calcularDesviacionRobusta(array $datos): float
    {
        $n = count($datos);
        $iqr = $this->calcularIQR($datos);
        
        return (1.25 / 1.35) * ($iqr / sqrt($n));
    }

    /**
     * Calcula la Varianza Robusta (cuadrado de S*).
     *
     * @param array $datos Array de valores numéricos ordenados
     * @return float La varianza robusta
     */
    private function calcularVarianzaRobusta(array $datos): float
    {
        return pow($this->calcularDesviacionRobusta($datos), 2);
    }

    /**
     * Calcula el Coeficiente de Variación Robusto (CVr%).
     * 
     * Basado en la mediana y la desviación robusta.
     * Mide la variabilidad relativa respecto a la tendencia central.
     *
     * @param array $datos Array de valores numéricos ordenados
     * @return float El coeficiente de variación robusto en porcentaje
     */
    private function calcularCVr(array $datos): float
    {
        $mediana = $this->calcularMediana($datos);
        if ($mediana == 0) {
            return 0.0;
        }

        $desviacionRobusta = $this->calcularDesviacionRobusta($datos);
        return ($desviacionRobusta / $mediana) * 100;
    }

    /**
     * Calcula el Rango Intercuartílico (IQR).
     * 
     * IQR = Q3 - Q1, representa la dispersión del 50% central de los datos.
     *
     * @param array $datos Array de valores numéricos ordenados
     * @return float El rango intercuartílico
     */
    private function calcularIQR(array $datos): float
    {
        $q1 = $this->calcularPercentil($datos, 25);
        $q3 = $this->calcularPercentil($datos, 75);

        return $q3 - $q1;
    }

    /**
     * Calcula la Desviación Absoluta de la Mediana (MAD).
     * 
     * Es la mediana de las desviaciones absolutas respecto a la mediana.
     * Medida robusta de dispersión.
     *
     * @param array $datos Array de valores numéricos ordenados
     * @return float La desviación absoluta de la mediana
     */
    private function calcularMAD(array $datos): float
    {
        $mediana = $this->calcularMediana($datos);
        $desviaciones = array_map(fn($x) => abs($x - $mediana), $datos);
        sort($desviaciones);
        return $this->calcularMediana($desviaciones);
    }

    /**
     * Detecta valores fuera de los límites de Tukey (Outliers).
     * 
     * Límites:
     * - Inferior: Q1 - 1.5 × IQR
     * - Superior: Q3 + 1.5 × IQR
     *
     * @param array $datos Array de valores numéricos ordenados
     * @return array Array de valores identificados como outliers
     */
    private function detectarOutliers(array $datos): array
    {
        $iqr = $this->calcularIQR($datos);
        
        $q1 = $this->calcularPercentil($datos, 25);
        $q3 = $this->calcularPercentil($datos, 75);

        $limiteInf = $q1 - 1.5 * $iqr;
        $limiteSup = $q3 + 1.5 * $iqr;

        return array_values(array_filter($datos, fn($x) => $x < $limiteInf || $x > $limiteSup));
    }

    /**
     * Calcula Intervalos de Confianza al 95%.
     * 
     * Basados en la mediana y la desviación robusta (S*).
     * Margen: 1.96 × S*
     *
     * @param array $datos Array de valores numéricos ordenados
     * @return array Array asociativo con claves 'superior' e 'inferior'
     */
    private function calcularIntervalosConfianza(array $datos): array
    {
        $mediana = $this->calcularMediana($datos);
        $sEstrella = $this->calcularDesviacionRobusta($datos);
        $margen = 1.96 * $sEstrella;
        
        return [
            'superior' => $mediana + $margen,
            'inferior' => $mediana - $margen,
        ];
    }

    /**
     * Calcula un percentil específico usando interpolación lineal.
     *
     * @param array $datosOrdenados Array de valores numéricos ordenados
     * @param int $percentil Percentil a calcular (0-100)
     * @return float El valor del percentil solicitado
     */
    private function calcularPercentil(array $datosOrdenados, int $percentil): float
    {
        $index = ($percentil / 100) * (count($datosOrdenados) - 1);
        $low = floor($index);
        $high = ceil($index);
        $fraction = $index - $low;

        return $datosOrdenados[$low] + $fraction * ($datosOrdenados[$high] - $datosOrdenados[$low]);
    }
}