<?php

namespace App\Repositories;

use App\Interfaces\PostSaleMessageRepositoryInterface;
use App\Models\Order;
use App\Models\PostSaleMessage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PostSaleMessageRepository implements PostSaleMessageRepositoryInterface
{
    public function paginateConversations(int $accountId, int $perPage = 20): LengthAwarePaginator
    {
        return Order::with([
                'customer:id,name,nickname,meli_customer_id',
                'items:id,order_id,meli_item_id,sku,title,quantity,unit_price,thumbnail',
                'postSaleMessages' => fn ($q) => $q->orderBy('sent_at'),
            ])
            ->where('mercadolibre_account_id', $accountId)
            ->whereHas('postSaleMessages')
            ->withMax('postSaleMessages as last_message_at', 'sent_at')
            ->orderByDesc('last_message_at')
            ->paginate($perPage);
    }

    public function byPack(int $accountId, string $packId): Collection
    {
        return PostSaleMessage::where('mercadolibre_account_id', $accountId)
            ->where('pack_id', $packId)
            ->orderBy('sent_at')
            ->get();
    }

    public function existsMeliMessage(string $meliMessageId): bool
    {
        return PostSaleMessage::where('meli_message_id', $meliMessageId)->exists();
    }

    public function create(array $data): PostSaleMessage
    {
        return PostSaleMessage::create($data);
    }
}
