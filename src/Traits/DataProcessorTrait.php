<?php

declare(strict_types=1);

namespace Cjuol\StatGuard\Traits;

use Cjuol\StatGuard\Exceptions\InvalidDataSetException;

trait DataProcessorTrait
{
    private function validateData(array $data, bool $alreadyProcessed = false): array
    {
        $count = count($data);
        if ($count < 2) {
            throw new InvalidDataSetException('At least 2 numeric values are required.');
        }

        $isSequential = true;
        $expectedKey = 0;

        foreach ($data as $key => $value) {
            if (!is_numeric($value)) {
                throw new InvalidDataSetException('All sample values must be numeric.');
            }

            if (!$alreadyProcessed && $key !== $expectedKey) {
                $isSequential = false;
            }
            $expectedKey++;
        }

        if ($alreadyProcessed || $isSequential) {
            return $data;
        }

        return array_values($data);
    }

    private function prepareData(array $data, bool $sort = true, bool $alreadyProcessed = false, bool $alreadySorted = false): array
    {
        $processedData = $this->validateData($data, $alreadyProcessed);
        if ($sort && !$alreadySorted) {
            sort($processedData, SORT_NUMERIC);
        }
        return $processedData;
    }
}