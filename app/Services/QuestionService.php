<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\MeliApiException;
use App\Interfaces\ProductRepositoryInterface;
use App\Interfaces\QuestionRepositoryInterface;
use App\Models\MercadolibreAccount;
use App\Models\Question;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class QuestionService
{
    public function __construct(
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly MercadoLibreService $meliService
    ) {}

    public function list(MercadolibreAccount $account, array $filters = []): LengthAwarePaginator
    {
        return $this->questionRepository->paginate($account->id, $filters);
    }

    public function find(MercadolibreAccount $account, int $id): ?Question
    {
        return $this->questionRepository->find($account->id, $id);
    }

    public function markSeen(Question $question, User $user): Question
    {
        return $this->questionRepository->update($question, ['seen' => true, 'handled_by' => $user->id]);
    }

    public function answer(MercadolibreAccount $account, Question $question, string $text, User $user): Question
    {
        if ($question->isAnswered()) {
            throw new BusinessRuleException('La pregunta ya fue respondida.');
        }

        $response = $this->meliService->answerQuestion($account, $question->meli_question_id, $text);

        if (!isset($response['id'], $response['answer']['status'])) {
            throw MeliApiException::fromResponse('responder pregunta', $response);
        }

        return $this->questionRepository->update($question, [
            'answer'        => $response['answer']['text'] ?? $text,
            'answer_status' => $response['answer']['status'],
            'answered_at'   => now(),
            'seen'          => true,
            'handled_by'    => $user->id,
        ]);
    }

    public function syncRecent(MercadolibreAccount $account): int
    {
        $response = $this->meliService->searchSellerQuestions($account);
        $stored   = 0;

        foreach ($response['questions'] ?? [] as $meliQuestion) {
            if (isset($meliQuestion['text']) && $this->store($account, $meliQuestion)) {
                $stored++;
            }
        }

        return $stored;
    }

    public function ingest(MercadolibreAccount $account, string $meliQuestionId): ?Question
    {
        $meliQuestion = $this->meliService->getQuestion($account, $meliQuestionId);

        if (!isset($meliQuestion['item_id'])) {
            throw MeliApiException::fromResponse('consultar pregunta', $meliQuestion);
        }

        return $this->store($account, $meliQuestion);
    }

    private function store(MercadolibreAccount $account, array $meliQuestion): ?Question
    {
        $existing = $this->questionRepository->findByMeliQuestionId((string) $meliQuestion['id']);
        $answer   = $this->answerAttributes($meliQuestion['answer'] ?? null);

        if ($existing) {
            return $existing->isAnswered() || $answer === []
                ? null
                : $this->questionRepository->update($existing, $answer);
        }

        $buyerId = (string) ($meliQuestion['from']['id'] ?? '');

        return $this->questionRepository->create(array_merge([
            'mercadolibre_account_id' => $account->id,
            'meli_question_id'        => (string) $meliQuestion['id'],
            'meli_item_id'            => $meliQuestion['item_id'],
            'product_id'              => $this->productRepository->findByMeliItemId($meliQuestion['item_id'])?->id,
            'question'                => $meliQuestion['text'],
            'question_status'         => $meliQuestion['status'] ?? null,
            'asked_at'                => isset($meliQuestion['date_created']) ? Carbon::parse($meliQuestion['date_created']) : now(),
            'buyer_meli_id'           => $buyerId ?: null,
            'buyer_nickname'          => $buyerId ? ($this->meliService->getCustomer($account, $buyerId)['nickname'] ?? null) : null,
            'seen'                    => false,
        ], $answer));
    }

    private function answerAttributes(?array $answer): array
    {
        if (!isset($answer['text'])) {
            return [];
        }

        return [
            'answer'        => $answer['text'],
            'answer_status' => $answer['status'] ?? null,
            'answered_at'   => isset($answer['date_created']) ? Carbon::parse($answer['date_created']) : now(),
        ];
    }
}
