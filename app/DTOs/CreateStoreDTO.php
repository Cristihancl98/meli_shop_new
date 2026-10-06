<?php

namespace App\DTOs;

final class CreateStoreDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $connectionCode,
        public readonly string $database,
        public readonly string $adminName,
        public readonly string $adminEmail,
        public readonly string $adminPassword,
        public readonly ?string $ownerName = null,
        public readonly ?string $ownerEmail = null,
        public readonly ?string $ownerPhone = null,
    ) {}
}
