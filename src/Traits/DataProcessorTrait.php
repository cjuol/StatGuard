<?php

declare(strict_types=1);

namespace Cjuol\StatGuard\Traits;

trait DataProcessorTrait
{
    private function validateData(array $data): array
    {
        if (count($data) < 2) {
            throw new \InvalidArgumentException('At least 2 numeric values are required.');
        }

        $cleanData = array_values(array_filter($data, 'is_numeric'));

        if (count($cleanData) !== count($data)) {
            throw new \InvalidArgumentException('All sample values must be numeric.');
        }

        return $cleanData;
    }

    private function prepareData(array $data, bool $sort = true): array
    {
        $processedData = $this->validateData($data);
        if ($sort) {
            sort($processedData);
        }
        return $processedData;
    }
}