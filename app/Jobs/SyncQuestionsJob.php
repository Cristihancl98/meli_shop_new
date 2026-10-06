<?php

namespace App\Jobs;

use App\Models\MercadolibreAccount;
use App\Services\QuestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncQuestionsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly MercadolibreAccount $account) {}

    public function handle(QuestionService $questionService): void
    {
        $questionService->syncRecent($this->account);
    }
}
