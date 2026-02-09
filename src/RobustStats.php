<?php

declare(strict_types=1);

namespace Cjuol\StatGuard;

use Cjuol\StatGuard\Contracts\StatsInterface;
use Cjuol\StatGuard\Traits\DataProcessorTrait;
use Cjuol\StatGuard\Traits\ExportableTrait;

class RobustStats implements StatsInterface
{
    use DataProcessorTrait;
    use ExportableTrait;

    // ========== INTERFAZ (Para el Comparador) ==========

    public function getMedia(array $datos): float
    {
        return $this->calcularMedia($this->prepararDatos($datos, false));
    }

    public function getMediana(array $datos): float
    {
        return $this->calcularMediana($this->prepararDatos($datos, true));
    }

    public function getDesviacion(array $datos): float
    {
        // Usamos MAD Escalado para que el Ratio de Ruido sea comparable a 1.0
        return $this->getMAD($datos) * 1.4826;
    }

    public function getCV(array $datos): float
    {
        // Por consistencia con la interfaz, usamos la desviación escalada
        $d = $this->prepararDatos($datos, true);
        $mediana = $this->calcularMediana($d);
        if (abs($mediana) < 1e-9) return 0.0;
        return ($this->getDesviacion($d) / abs($mediana)) * 100;
    }

    // ========== MÉTODOS ESPECÍFICOS (Para tus Tests de S*) ==========

    public function getDesviacionRobusta(array $datos): float
    {
        return $this->calcularDesviacionRobusta($this->prepararDatos($datos, true));
    }

    public function getCVr(array $datos): float
    {
        // Tus tests esperan el CV basado en S*
        return $this->calcularCVr($this->prepararDatos($datos, true));
    }

    public function getVarianzaRobusta(array $datos): float
    {
        $datosPreparados = $this->prepararDatos($datos, true);
        return pow($this->calcularDesviacionRobusta($datosPreparados), 2);
    }

    public function getIQR(array $datos): float
    {
        return $this->calcularIQR($this->prepararDatos($datos, true));
    }

    public function getMAD(array $datos): float
    {
        return $this->calcularMAD($this->prepararDatos($datos, true));
    }

    public function getOutliers(array $datos): array
    {
        return $this->detectarOutliers($this->prepararDatos($datos, true));
    }

    public function getIntervalosConfianza(array $datos): array
    {
        return $this->calcularIntervalosConfianza($this->prepararDatos($datos, true));
    }

    public function obtenerResumen(array $datos, bool $ordenar = true, int $decimales = 2): array
    {
        $d = $this->prepararDatos($datos, $ordenar);
        
        return [
            'media'               => round($this->calcularMedia($d), $decimales),
            'mediana'             => round($this->calcularMediana($d), $decimales),
            'desviacionRobusta'   => round($this->calcularDesviacionRobusta($d), $decimales),
            'varianzaRobusta'     => round(pow($this->calcularDesviacionRobusta($d), 2), $decimales),
            'CVr'                 => round($this->calcularCVr($d), $decimales),
            'IQR'                 => round($this->calcularIQR($d), $decimales),
            'MAD'                 => round($this->calcularMAD($d), $decimales),
            'outliers'            => $this->detectarOutliers($d),
            'intervalosConfianza' => $this->calcularIntervalosConfianza($d),
            'count'               => count($d)
        ];
    }

    // ========== MOTOR INTERNO ==========

    private function calcularMedia(array $datos): float
    {
        return array_sum($datos) / count($datos);
    }

    private function calcularMediana(array $datos): float
    {
        $n = count($datos);
        $m = intdiv($n, 2);
        return ($n % 2 === 0) ? ($datos[$m - 1] + $datos[$m]) / 2.0 : (float) $datos[$m];
    }

    private function calcularDesviacionRobusta(array $datos): float
    {
        $n = count($datos);
        return (1.25 / 1.35) * ($this->calcularIQR($datos) / sqrt($n));
    }

    private function calcularCVr(array $datos): float
    {
        $mediana = $this->calcularMediana($datos);
        if (abs($mediana) < 1e-9) return 0.0;
        return ($this->calcularDesviacionRobusta($datos) / abs($mediana)) * 100;
    }

    private function calcularIQR(array $datos): float
    {
        return $this->calcularPercentil($datos, 75) - $this->percentilOriginal($datos, 25);
    }

    private function calcularMAD(array $datos): float
    {
        $mediana = $this->calcularMediana($datos);
        $diffs = array_map(fn($x) => abs($x - $mediana), $datos);
        sort($diffs);
        return $this->calcularMediana($diffs);
    }

    private function detectarOutliers(array $datos): array
    {
        $iqr = $this->calcularIQR($datos);
        $q1 = $this->percentilOriginal($datos, 25);
        $q3 = $this->calcularPercentil($datos, 75);
        return array_values(array_filter($datos, fn($x) => $x < ($q1 - 1.5 * $iqr) || $x > ($q3 + 1.5 * $iqr)));
    }

    private function calcularIntervalosConfianza(array $datos): array
    {
        $mediana = $this->calcularMediana($datos);
        $margen = 1.96 * $this->calcularDesviacionRobusta($datos);
        return ['superior' => $mediana + $margen, 'inferior' => $mediana - $margen];
    }

    private function calcularPercentil(array $d, int $p): float
    {
        $i = ($p / 100) * (count($d) - 1);
        $low = (int)floor($i); $high = (int)ceil($i);
        return $d[$low] + ($i - $low) * ($d[$high] - $d[$low]);
    }

    private function percentilOriginal(array $d, int $p): float 
    {
        return $this->calcularPercentil($d, $p);
    }
}