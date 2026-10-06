<?php

namespace App\Services;

use App\Enums\SettingKey;
use App\Exceptions\MeliApiException;
use App\Interfaces\OrderRepositoryInterface;
use App\Interfaces\PostSaleMessageRepositoryInterface;
use App\Models\MercadolibreAccount;
use App\Models\Order;
use App\Models\PostSaleMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class PostSaleService
{
    public function __construct(
        private readonly PostSaleMessageRepositoryInterface $messageRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly MercadoLibreService $meliService,
        private readonly SettingService $settingService
    ) {}

    public function conversations(MercadolibreAccount $account): LengthAwarePaginator
    {
        return $this->messageRepository->paginateConversations($account->id);
    }

    public function reply(MercadolibreAccount $account, Order $order, string $text, ?User $user): PostSaleMessage
    {
        $buyerId  = (string) $order->customer->meli_customer_id;
        $response = $this->meliService->sendPostSaleMessage($account, $order->conversationId(), $buyerId, $text);

        if (!isset($response['id'])) {
            throw MeliApiException::fromResponse('enviar mensaje posventa', $response);
        }

        return $this->messageRepository->create([
            'mercadolibre_account_id' => $account->id,
            'order_id'                => $order->id,
            'pack_id'                 => $order->conversationId(),
            'meli_message_id'         => (string) $response['id'],
            'text'                    => $text,
            'from_seller'             => true,
            'status'                  => $response['status'] ?? 'available',
            'sent_at'                 => now(),
            'seen'                    => true,
            'sent_by'                 => $user?->id,
        ]);
    }

    public function sendSaleMessage(MercadolibreAccount $account, Order $order): ?PostSaleMessage
    {
        $message = (string) $this->settingService->get(SettingKey::SaleMessage);

        if ($message === '' || !$this->settingService->isEnabled(SettingKey::SaleMessage)) {
            return null;
        }

        try {
            return $this->reply($account, $order->loadMissing('customer'), $message, null);
        } catch (MeliApiException $e) {
            Log::warning('No se pudo enviar el mensaje de venta', ['order' => $order->meli_order_id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function syncUnread(MercadolibreAccount $account): int
    {
        $imported = 0;

        foreach ($this->meliService->getUnreadPostSaleMessages($account)['results'] ?? [] as $pending) {
            if (preg_match('#/packs/(\d+)/sellers#', $pending['resource'] ?? '', $matches)) {
                $order     = $this->orderRepository->findByConversation($account->id, $matches[1]);
                $imported += $this->importConversation($account, $matches[1], $order);
            }
        }

        return $imported;
    }

    public function downloadAll(MercadolibreAccount $account): int
    {
        $imported = 0;

        foreach ($this->orderRepository->findByAccountId($account->id) as $order) {
            $imported += $this->importConversation($account, $order->conversationId(), $order);
        }

        return $imported;
    }

    public function ingest(MercadolibreAccount $account, string $meliMessageId): ?PostSaleMessage
    {
        if ($this->messageRepository->existsMeliMessage($meliMessageId)) {
            return null;
        }

        $message = $this->meliService->getMessage($account, $meliMessageId)['messages'][0] ?? null;

        if (!$message) {
            return null;
        }

        $packId = collect($message['message_resources'] ?? [])->firstWhere('name', 'packs')['id'] ?? null;

        if (!$packId) {
            return null;
        }

        $order = $this->orderRepository->findByConversation($account->id, (string) $packId);

        return $this->storeMessage($account, (string) $packId, $order, $message);
    }

    private function importConversation(MercadolibreAccount $account, string $packId, ?Order $order): int
    {
        $imported = 0;

        foreach ($this->meliService->getPackMessages($account, $packId)['messages'] ?? [] as $message) {
            if ($this->storeMessage($account, $packId, $order, $message)) {
                $imported++;
            }
        }

        return $imported;
    }

    private function storeMessage(MercadolibreAccount $account, string $packId, ?Order $order, array $message): ?PostSaleMessage
    {
        if (!isset($message['id']) || $this->messageRepository->existsMeliMessage((string) $message['id'])) {
            return null;
        }

        $sentAt = $message['message_date']['created'] ?? null;

        return $this->messageRepository->create([
            'mercadolibre_account_id' => $account->id,
            'order_id'                => $order?->id,
            'pack_id'                 => $packId,
            'meli_message_id'         => (string) $message['id'],
            'text'                    => $message['text'] ?? '',
            'from_seller'             => (string) ($message['from']['user_id'] ?? '') === (string) $account->meli_user_id,
            'status'                  => $message['status'] ?? null,
            'sent_at'                 => $sentAt ? Carbon::parse($sentAt) : now(),
            'seen'                    => false,
        ]);
    }
}
