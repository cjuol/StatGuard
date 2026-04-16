<?php

declare(strict_types=1);

namespace Cjuol\StatGuard\Contracts;

interface ExportableInterface
{
    public function toJson(array $data, int $options = JSON_PRETTY_PRINT): string;

    public function toCsv(array $data, string $delimiter = ','): string;
}
