<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponses
{
    protected function ok(mixed $data, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $data,
            'message' => $message,
            'errors'  => [],
        ], $status);
    }

    protected function fail(string $message, int $status = 422, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data'    => null,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    protected function notFound(string $message = 'Recurso no encontrado.'): JsonResponse
    {
        return $this->fail($message, 404);
    }

    protected function noAccount(): JsonResponse
    {
        return $this->fail('No tienes una cuenta de Mercado Libre vinculada.', 422);
    }
}
