<?php

namespace App\Interfaces;

use App\Models\MercadolibreAccount;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

interface MercadolibreAccountRepositoryInterface
{
    public function findByUserId(int $userId): Collection;
    public function findByMeliUserId(string $meliUserId): ?MercadolibreAccount;
    public function findActiveByUserId(int $userId): ?MercadolibreAccount;
    public function findSelectedByUser(int $userId, ?int $selectedId): ?MercadolibreAccount;
    public function upsertTokens(int $userId, array $data): MercadolibreAccount;
    public function updateTokens(MercadolibreAccount $account, string $accessToken, string $refreshToken, Carbon $expiresAt): MercadolibreAccount;
    public function find(int $id): ?MercadolibreAccount;
    public function deactivate(int $id, int $userId): bool;
}
