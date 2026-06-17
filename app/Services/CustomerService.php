<?php

namespace App\Services;

use App\DTOs\CustomerDTO;
use App\Interfaces\CustomerRepositoryInterface;
use App\Interfaces\MercadolibreAccountRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class CustomerService
{
    public function __construct(
        private readonly CustomerRepositoryInterface           $customerRepository,
        private readonly MercadolibreAccountRepositoryInterface $accountRepository,
        private readonly MercadoLibreService                  $meliService,
    ) {}

    public function getPaginated(int $userId, array $filters, ?int $selectedAccountId = null): LengthAwarePaginator
    {
        $account = $this->accountRepository->findSelectedByUser($userId, $selectedAccountId);
        if ($account) {
            $filters['account_id'] = $account->id;
        }

        return $this->customerRepository->paginate($filters);
    }

    public function findById(int $id)
    {
        return $this->customerRepository->findById($id);
    }

    public function syncFromMeli(int $userId): array
    {
        $account = $this->accountRepository->findActiveByUserId($userId);
        if (!$account) {
            return ['synced' => 0, 'errors' => ['No hay cuenta MeLi activa']];
        }

        $synced = 0;
        $errors = [];

        try {
            $orders = $this->customerRepository->findByAccountId($account->id);
            $meliCustomerIds = $orders
                ->pluck('meli_customer_id')
                ->filter()
                ->unique()
                ->values();

            foreach ($meliCustomerIds as $meliCustomerId) {
                try {
                    $meliData = $this->meliService->getCustomer($account, $meliCustomerId);
                    $dto      = CustomerDTO::fromMeliResponse($meliData, $account->id);
                    $existing = $this->customerRepository->findByMeliCustomerId($dto->meliCustomerId);

                    if ($existing) {
                        $this->customerRepository->update($existing, $dto->toArray());
                    } else {
                        $this->customerRepository->create($dto->toArray());
                    }

                    $synced++;
                } catch (\Throwable $e) {
                    Log::error('CustomerService: error syncing customer', [
                        'meli_customer_id' => $meliCustomerId,
                        'error'            => $e->getMessage(),
                    ]);
                    $errors[] = $e->getMessage();
                }
            }
        } catch (\Throwable $e) {
            Log::error('CustomerService: MeLi API error', ['error' => $e->getMessage()]);
            $errors[] = $e->getMessage();
        }

        return compact('synced', 'errors');
    }

    public function getTopBuyers(int $userId, int $limit = 10)
    {
        $account = $this->accountRepository->findActiveByUserId($userId);
        if (!$account) {
            return collect();
        }

        return $this->customerRepository->topBuyers($account->id, $limit);
    }
}
