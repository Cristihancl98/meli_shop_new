<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\MercadolibreAccountRepositoryInterface;
use App\Jobs\SyncOrdersJob;
use App\Jobs\SyncProductsJob;
use App\Models\SyncLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private readonly MercadolibreAccountRepositoryInterface $accountRepository
    ) {}

    public function handle(Request $request): JsonResponse
    {
        // Responder 200 inmediato — MeLi requiere respuesta en < 500ms
        $topic   = $request->input('topic');
        $userId  = $request->input('user_id');
        $resource= $request->input('resource');

        if (!$this->validateSignature($request)) {
            Log::warning('Webhook MeLi: firma inválida', ['headers' => $request->headers->all()]);
            return response()->json(['success' => false], 400);
        }

        $this->processInBackground($topic, $userId, $resource);

        return response()->json(['success' => true], 200);
    }

    private function validateSignature(Request $request): bool
    {
        $signature = $request->header('x-signature');

        if (!$signature) {
            return false;
        }

        // Extraer ts y v1 del header x-signature
        $parts = [];
        foreach (explode(',', $signature) as $part) {
            [$key, $value] = explode('=', trim($part), 2);
            $parts[$key] = $value;
        }

        if (!isset($parts['ts'], $parts['v1'])) {
            return false;
        }

        $requestId = $request->header('x-request-id', '');
        $dataId    = $request->input('data.id', '');
        $secret    = config('services.mercadolibre.client_secret');

        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$parts['ts']};";
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $parts['v1']);
    }

    private function processInBackground(string $topic, int|string $userId, string $resource): void
    {
        $account = $this->accountRepository->findByMeliUserId((string) $userId);

        if (!$account) {
            Log::info("Webhook MeLi: cuenta no encontrada para user_id {$userId}");
            return;
        }

        SyncLog::create([
            'mercadolibre_account_id' => $account->id,
            'type'                    => 'webhook',
            'status'                  => 'pending',
            'message'                 => "Webhook recibido: topic={$topic}, resource={$resource}",
            'started_at'              => now(),
        ]);

        match ($topic) {
            'orders', 'payments' => SyncOrdersJob::dispatch($account)->onQueue('default'),
            'items'              => SyncProductsJob::dispatch($account)->onQueue('default'),
            default              => Log::info("Webhook MeLi: topic '{$topic}' no procesado"),
        };
    }
}
