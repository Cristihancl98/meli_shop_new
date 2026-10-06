<?php

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class BusinessRuleException extends RuntimeException implements ShouldntReport
{
    protected int $status = 422;

    public function __construct(string $message, private readonly array $details = [])
    {
        parent::__construct($message);
    }

    public function details(): array
    {
        return $this->details;
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => $this->getMessage(),
                'errors'  => $this->details,
            ], $this->status);
        }

        return back()->withInput()->withErrors(['general' => $this->getMessage()]);
    }
}
