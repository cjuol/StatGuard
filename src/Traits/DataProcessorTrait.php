<?php

declare(strict_types=1);

namespace Cjuol\StatGuard\Traits;

use Cjuol\StatGuard\Support\DataValidator;

trait DataProcessorTrait
{
    private function validateData(array $data, bool $alreadyProcessed = false): array
    {
        return DataValidator::prepare($data, 2, false);
    }

    private function prepareData(array $data, bool $sort = true, bool $alreadyProcessed = false, bool $alreadySorted = false): array
    {
        return DataValidator::prepare($data, 2, $sort && !$alreadySorted);
    }
}