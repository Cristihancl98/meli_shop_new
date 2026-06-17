<?php

namespace App\DTOs;

class CustomerDTO
{
    public function __construct(
        public readonly string  $meliCustomerId,
        public readonly int     $mercadolibreAccountId,
        public readonly string  $name,
        public readonly string  $nickname,
        public readonly ?string $email,
        public readonly ?string $phone,
    ) {}

    public static function fromMeliResponse(array $data, int $accountId): self
    {
        $name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

        return new self(
            meliCustomerId:          (string) $data['id'],
            mercadolibreAccountId:   $accountId,
            name:                    $name ?: ($data['nickname'] ?? ''),
            nickname:                $data['nickname'] ?? '',
            email:                   $data['email'] ?? null,
            phone:                   isset($data['phone']['number']) ? $data['phone']['number'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'meli_customer_id'          => $this->meliCustomerId,
            'mercadolibre_account_id'   => $this->mercadolibreAccountId,
            'name'                      => $this->name,
            'nickname'                  => $this->nickname,
            'email'                     => $this->email,
            'phone'                     => $this->phone,
        ];
    }
}
