<?php

namespace App\Repositories;

use App\Interfaces\QuestionRepositoryInterface;
use App\Models\Question;
use Illuminate\Pagination\LengthAwarePaginator;

class QuestionRepository implements QuestionRepositoryInterface
{
    public function paginate(int $accountId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Question::with('product:id,title,price,sku,meli_item_id,thumbnail,pictures')
            ->where('mercadolibre_account_id', $accountId);

        if (($filters['answered'] ?? null) === '0') {
            $query->whereNull('answer');
        } elseif (($filters['answered'] ?? null) === '1') {
            $query->whereNotNull('answer');
        }

        if (isset($filters['seen']) && $filters['seen'] !== '') {
            $query->where('seen', (bool) $filters['seen']);
        }

        return $query->orderByDesc('asked_at')->paginate($perPage)->withQueryString();
    }

    public function find(int $accountId, int $id): ?Question
    {
        return Question::where('mercadolibre_account_id', $accountId)->find($id);
    }

    public function findByMeliQuestionId(string $meliQuestionId): ?Question
    {
        return Question::where('meli_question_id', $meliQuestionId)->first();
    }

    public function create(array $data): Question
    {
        return Question::create($data);
    }

    public function update(Question $question, array $data): Question
    {
        $question->update($data);
        return $question->fresh(['product']);
    }
}
