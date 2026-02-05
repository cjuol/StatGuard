<?php

declare(strict_types=1);

namespace Cjuol\SymmetricalOcto;

use Cjuol\SymmetricalOcto\Traits\DataProcessorTrait;

/**
 * StatsComparator - Servicio de análisis comparativo.
 * Enfrenta la estadística clásica contra la robusta para detectar sesgos y ruido.
 */
class StatsComparator
{
    use DataProcessorTrait;

    private RobustStats $robust;
    private ClassicStats $classic;

    public function __construct(?RobustStats $robust = null, ?ClassicStats $classic = null)
    {
        $this->robust = $robust ?? new RobustStats();
        $this->classic = $classic ?? new ClassicStats();
    }

    /**
     * Compara las métricas y devuelve un informe de fidelidad de los datos.
     */
    public function analizar(array $datos, int $decimales = 2): array
    {
        $d = $this->prepararDatos($datos, true);

        $media = $this->classic->getMedia($d);
        $mediana = $this->robust->getMediana($d);
        $desvEst = $this->classic->getDesviacionEstandar($d);
        // getDesviacion() usa el MAD * 1.4826 para una comparativa justa
        $desvRob = $this->robust->getDesviacion($d);

        // 1. Cálculo del Sesgo (Bias) entre Media y Mediana
        // Usamos un umbral de seguridad (1e-9) en lugar de != 0
        // Formula: $$Sesgo = \frac{\text{media} - \text{mediana}}{|\text{mediana}|} \times 100$$
        $sesgo = (abs($mediana) > 1e-9) ? (($media - $mediana) / abs($mediana)) * 100 : 0.0;

        // 2. Ratio de Dispersión
        // Formula: $$Ratio = \frac{\sigma_{\text{clásica}}}{\sigma_{\text{robusta}}}$$
        if (abs($desvRob) > 1e-9) {
            $ratioDispersion = $desvEst / $desvRob;
        } else {
            // Si la robusta es 0 pero la clásica no, hay un ruido infinito (outliers extremos)
            $ratioDispersion = (abs($desvEst) > 1e-9) ? 2.0 : 1.0; 
        }

        return [
            'comparativa_central' => [
                'media_clasica' => round($media, $decimales),
                'mediana_robusta' => round($mediana, $decimales),
                'diferencia_abs' => round(abs($media - $mediana), $decimales),
                'sesgo_porcentaje' => round($sesgo, $decimales) . '%',
            ],
            'comparativa_dispersion' => [
                'desv_estandar' => round($desvEst, $decimales),
                'desv_robusta' => round($desvRob, $decimales),
                'ratio_ruido' => round($ratioDispersion, $decimales),
            ],
            'deteccion_outliers' => [
                'metodo_tukey' => count($this->robust->getOutliers($d)),
                'metodo_zscore' => count($this->classic->getOutliers($d)),
            ],
            'veredicto' => $this->generarVeredicto($sesgo, $ratioDispersion)
        ];
    }

    /**
     * Genera una conclusión humana basada en los datos.
     */
    private function generarVeredicto(float $sesgo, float $ratio): string
    {
        // Umbrales basados en experimentación estadística
        if (abs($sesgo) > 10 || $ratio > 1.5) {
            return "ALERTA: Datos altamente influenciados por outliers. Se recomienda usar métricas Robustas.";
        }

        if (abs($sesgo) > 5 || $ratio > 1.2) {
            return "PRECAUCIÓN: Existe un sesgo moderado. Compare ambas métricas antes de decidir.";
        }

        return "ESTABLE: Los datos siguen una distribución limpia. La estadística clásica es fiable.";
    }
}