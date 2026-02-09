<?php

declare(strict_types=1);

namespace Cjuol\StatGuard\Traits;

trait ExportableTrait
{
    /**
     * Export the statistical summary to JSON.
     */
    public function toJson(array $data, int $options = JSON_PRETTY_PRINT): string
    {
        return json_encode($this->getSummary($data), $options);
    }

    /**
     * Export the statistical summary to CSV.
     * @return string CSV content (Header + Values)
     */
    public function toCsv(array $data, string $delimiter = ","): string
    {
        $summary = $this->getSummary($data);

        // Flatten array fields (outliers, intervals, etc.)
        $csvData = [];
        foreach ($summary as $key => $value) {
            if (is_array($value)) {
                // Convert arrays to a pipe-separated string
                $csvData[$key] = empty($value) ? '' : implode('|', $value);
            } else {
                $csvData[$key] = $value;
            }
        }

        $handle = fopen('php://temp', 'r+');

        // 1. Insert headers
        fputcsv($handle, array_keys($csvData), $delimiter);

        // 2. Insert values
        fputcsv($handle, array_values($csvData), $delimiter);
        
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        if ($csv === false) {
            throw new \RuntimeException('Failed to read CSV content from temporary stream');
        }

        return $csv;
    }
}