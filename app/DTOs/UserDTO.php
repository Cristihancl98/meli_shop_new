<?php

namespace App\DTOs;

final class UserDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?string $password = null,
        public readonly ?string $role = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name:     $data['name'] ?? null,
            email:    $data['email'] ?? null,
            password: $data['password'] ?? null,
            role:     $data['role'] ?? null,
        );
    }

    public function attributes(): array
    {
        return array_filter([
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => $this->password,
        ], fn ($value) => $value !== null);
    }
}
