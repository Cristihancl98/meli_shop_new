<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MeliApiException extends BusinessRuleException
{
    protected int $status = 502;

    public static function fromResponse(string $action, array $response): self
    {
        $detail = $response['message'] ?? $response['error'] ?? 'respuesta inesperada';

        Log::warning("MeLi rechazó: {$action}", ['response' => $response]);

        return new self("Mercado Libre rechazó la operación ({$action}): {$detail}", ['meli' => $response, 'action' => $action]);
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return parent::render($request);
        }

        return back()->withInput()
            ->withErrors(['general' => $this->getMessage()])
            ->with('meli_error', $this->detailForDisplay());
    }

    public function detailForDisplay(): array
    {
        $response = $this->details()['meli'] ?? [];
        $causes   = is_array($response['cause'] ?? null) ? $response['cause'] : [];

        return [
            'action'  => $this->details()['action'] ?? null,
            'status'  => $response['status'] ?? null,
            'error'   => $response['error'] ?? null,
            'message' => $response['message'] ?? null,
            'causes'  => array_values(array_map(fn ($cause) => [
                'type'       => is_array($cause) ? ($cause['type'] ?? 'error') : 'error',
                'code'       => is_array($cause) ? ($cause['code'] ?? null) : null,
                'message'    => is_array($cause) ? (string) ($cause['message'] ?? '') : (string) $cause,
                'references' => is_array($cause) ? implode(', ', (array) ($cause['references'] ?? [])) : '',
            ], array_filter($causes, fn ($cause) => is_array($cause) || is_string($cause)))),
            'raw'     => json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }
}
