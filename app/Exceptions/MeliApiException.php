<?php

namespace App\Exceptions;

use Illuminate\Support\Facades\Log;

class MeliApiException extends BusinessRuleException
{
    protected int $status = 502;

    public static function fromResponse(string $action, array $response): self
    {
        $detail = $response['message'] ?? $response['error'] ?? 'respuesta inesperada';

        Log::warning("MeLi rechazó: {$action}", ['response' => $response]);

        return new self("Mercado Libre rechazó la operación ({$action}): {$detail}", ['meli' => $response]);
    }
}
