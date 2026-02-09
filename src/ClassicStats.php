<?php

declare(strict_types=1);

namespace Cjuol\StatGuard;

use Cjuol\StatGuard\Contracts\StatsInterface;
use Cjuol\StatGuard\Traits\DataProcessorTrait;
use Cjuol\StatGuard\Traits\ExportableTrait;

/**
 * ClassicStats - Biblioteca de Estadística Descriptiva Clásica
 * * Implementa cálculos basados en la media y desviación estándar tradicional.
 * * Útil para comparativas de sesgo frente a estadística robusta.
 */
class ClassicStats implements StatsInterface
{
    use DataProcessorTrait;
    use ExportableTrait;

    // ========== FUNCIONES PÚBLICAS - INTERFAZ Y CONTRATOS ==========

    /**
     * Calcula la media aritmética simple.
     */
    public function getMedia(array $datos): float
    {
        return $this->calcularMedia($this->prepararDatos($datos, false));
    }

    /**
     * Calcula la mediana (ordenando los datos).
     */
    public function getMediana(array $datos): float
    {
        return $this->calcularMediana($this->prepararDatos($datos, true));
    }

    /**
     * Implementación del contrato: Devuelve la Desviación Estándar Muestral.
     */
    public function getDesviacion(array $datos): float
    {
        return $this->getDesviacionEstandar($datos);
    }

    /**
     * Calcula la Desviación Estándar Muestral (Raíz cuadrada de la varianza muestral).
     */
    public function getDesviacionEstandar(array $datos): float
    {
        return sqrt($this->getVarianzaMuestral($datos));
    }

    /**
     * Implementación del contrato: Devuelve el Coeficiente de Variación (CV%).
     */
    public function getCV(array $datos): float
    {
        $d = $this->prepararDatos($datos, false);
        $media = $this->calcularMedia($d);

        if (abs($media) < 1e-9) return 0.0;

        return ($this->getDesviacionEstandar($d) / abs($media)) * 100;
    }

    /**
     * Calcula la Varianza Muestral (Corrección de Bessel: divide por n-1).
     */
    public function getVarianzaMuestral(array $datos): float
    {
        return $this->calcularVarianza($this->prepararDatos($datos, false), true);
    }

    /**
     * Calcula la Varianza Poblacional (Divide por n).
     */
    public function getVarianzaPoblacional(array $datos): float
    {
        return $this->calcularVarianza($this->prepararDatos($datos, false), false);
    }

    /**
     * Detecta outliers usando el método Z-Score tradicional (|Z| > 3).
     */
    public function getOutliers(array $datos): array
    {
        $d = $this->prepararDatos($datos, false);
        $media = $this->calcularMedia($d);
        $desviacion = $this->getDesviacionEstandar($d);

        if ($desviacion < 1e-9) return [];

        return array_values(array_filter($d, function ($x) use ($media, $desviacion) {
            return abs(($x - $media) / $desviacion) > 3;
        }));
    }

    /**
     * Obtiene un resumen completo de las métricas clásicas.
     */
    public function obtenerResumen(array $datos, bool $ordenar = true, int $decimales = 2): array
    {
        $d = $this->prepararDatos($datos, $ordenar);

        return [
            'media'              => round($this->calcularMedia($d), $decimales),
            'mediana'            => round($this->calcularMediana($d), $decimales),
            'desviacionEstandar' => round($this->getDesviacionEstandar($d), $decimales),
            'varianzaMuestral'   => round($this->getVarianzaMuestral($d), $decimales),
            // USAMOS EL MÉTODO SEGURO getCV($d) para evitar división por cero
            'CV'                 => round($this->getCV($d), $decimales),
            'outliers_zscore'    => $this->getOutliers($d),
            'count'              => count($d)
        ];
    }

    // ========== FUNCIONES PRIVADAS - MOTOR DE CÁLCULO PURO ==========

    private function calcularMedia(array $datos): float
    {
        return array_sum($datos) / count($datos);
    }

    private function calcularMediana(array $datos): float
    {
        $n = count($datos);
        $m = intdiv($n, 2);

        if ($n % 2 === 0) {
            return ($datos[$m - 1] + $datos[$m]) / 2.0;
        }
        return (float) $datos[$m];
    }

    private function calcularVarianza(array $datos, bool $muestral = true): float
    {
        $n = count($datos);
        $media = $this->calcularMedia($datos);
        
        $sumatorio = array_reduce($datos, fn($acc, $x) => $acc + pow($x - $media, 2), 0.0);
        
        $divisor = $muestral ? ($n - 1) : $n;
        
        return $sumatorio / $divisor;
    }
}