<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\MercadolibreAccountRepositoryInterface;
use App\Jobs\ProcessMeliNotificationJob;
use App\Jobs\SyncOrdersJob;
use App\Jobs\SyncProductsJob;
use App\Models\SyncLog;
use App\Services\TenantManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    private const NOTIFICATION_TOPICS = ['orders_v2', 'questions', 'messages'];

    public function __construct(
        private readonly MercadolibreAccountRepositoryInterface $accountRepository,
        private readonly TenantManager $tenantManager
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $topic    = (string) $request->input('topic', '');
        $userId   = (string) $request->input('user_id', '');
        $resource = (string) $request->input('resource', '');

        if (!$this->belongsToOurApplication($request)) {
            Log::warning('Webhook MeLi: application_id no coincide', ['application_id' => $request->input('application_id')]);
            return response()->json(['success' => false], 400);
        }

        if ($topic !== '' && $userId !== '' && $resource !== '') {
            $this->processInBackground($topic, $userId, $resource);
        }

        return response()->json(['success' => true], 200);
    }

    private function belongsToOurApplication(Request $request): bool
    {
        $clientId = (string) config('services.mercadolibre.client_id');

        return $clientId === '' || (string) $request->input('application_id') === $clientId;
    }

    private function processInBackground(string $topic, string $userId, string $resource): void
    {
        if (!$this->tenantManager->activateByMeliUserId($userId)) {
            Log::info("Webhook MeLi: ninguna tienda tiene vinculado el user_id {$userId}");
            return;
        }

        $account = $this->accountRepository->findByMeliUserId($userId);

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

        match (true) {
            in_array($topic, self::NOTIFICATION_TOPICS, true) => ProcessMeliNotificationJob::dispatch($account, $topic, $resource),
            in_array($topic, ['orders', 'payments'], true)    => SyncOrdersJob::dispatch($account),
            $topic === 'items'                                => SyncProductsJob::dispatch($account),
            default                                           => Log::info("Webhook MeLi: topic '{$topic}' no procesado"),
        };
    }
}
