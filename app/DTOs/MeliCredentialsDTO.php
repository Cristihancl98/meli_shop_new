<?php

namespace App\DTOs;

final class MeliCredentialsDTO
{
    public function __construct(
        public readonly string $clientId,
        public readonly string $clientSecret,
        public readonly string $redirectUri,
    ) {}

    public function isComplete(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '' && $this->redirectUri !== '';
    }

    public function toPublicArray(): array
    {
        return [
            'meli_client_id'    => $this->clientId,
            'meli_redirect_uri' => $this->redirectUri,
            'has_client_secret' => $this->clientSecret !== '',
            'is_complete'       => $this->isComplete(),
        ];
    }
}
