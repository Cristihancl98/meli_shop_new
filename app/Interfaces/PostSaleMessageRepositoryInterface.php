<?php

namespace App\Interfaces;

use App\Models\PostSaleMessage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PostSaleMessageRepositoryInterface
{
    public function paginateConversations(int $accountId, int $perPage = 20): LengthAwarePaginator;
    public function byPack(int $accountId, string $packId): Collection;
    public function existsMeliMessage(string $meliMessageId): bool;
    public function create(array $data): PostSaleMessage;
}
