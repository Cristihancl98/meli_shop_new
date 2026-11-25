<?php

namespace App\DTOs;

use Carbon\CarbonInterface;

final class CatalogImportStatusDTO
{
    public function __construct(
        public readonly string $state,
        public readonly string $message,
        public readonly CarbonInterface $at,
        public readonly bool $waitingTooLong = false,
    ) {}

    public function inProgress(): bool
    {
        return in_array($this->state, ['pending', 'running'], true);
    }
}
