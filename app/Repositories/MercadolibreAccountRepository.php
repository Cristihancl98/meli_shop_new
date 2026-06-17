<?php

namespace App\Repositories;

use App\Interfaces\MercadolibreAccountRepositoryInterface;
use App\Models\MercadolibreAccount;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class MercadolibreAccountRepository implements MercadolibreAccountRepositoryInterface
{
    public function findByUserId(int $userId): Collection
    {
        return MercadolibreAccount::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('nickname')
            ->get();
    }

    public function findByMeliUserId(string $meliUserId): ?MercadolibreAccount
    {
        return MercadolibreAccount::where('meli_user_id', $meliUserId)->first();
    }

    public function findActiveByUserId(int $userId): ?MercadolibreAccount
    {
        return MercadolibreAccount::where('user_id', $userId)
            ->where('is_active', true)
            ->latest()
            ->first();
    }

    public function findSelectedByUser(int $userId, ?int $selectedId): ?MercadolibreAccount
    {
        if ($selectedId) {
            $account = MercadolibreAccount::where('id', $selectedId)
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->first();

            if ($account) {
                return $account;
            }
        }

        return $this->findActiveByUserId($userId);
    }

    public function upsertTokens(int $userId, array $data): MercadolibreAccount
    {
        return MercadolibreAccount::updateOrCreate(
            ['meli_user_id' => $data['meli_user_id']],
            array_merge($data, ['user_id' => $userId])
        );
    }

    public function updateTokens(MercadolibreAccount $account, string $accessToken, string $refreshToken, Carbon $expiresAt): MercadolibreAccount
    {
        $account->update([
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at'    => $expiresAt,
        ]);

        return $account->fresh();
    }

    public function find(int $id): ?MercadolibreAccount
    {
        return MercadolibreAccount::find($id);
    }

    public function deactivate(int $id, int $userId): bool
    {
        return (bool) MercadolibreAccount::where('id', $id)
            ->where('user_id', $userId)
            ->update(['is_active' => false]);
    }
}
