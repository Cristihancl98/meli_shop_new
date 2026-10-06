<?php

namespace App\Interfaces;

use App\Models\Question;
use Illuminate\Pagination\LengthAwarePaginator;

interface QuestionRepositoryInterface
{
    public function paginate(int $accountId, array $filters = [], int $perPage = 20): LengthAwarePaginator;
    public function find(int $accountId, int $id): ?Question;
    public function findByMeliQuestionId(string $meliQuestionId): ?Question;
    public function create(array $data): Question;
    public function update(Question $question, array $data): Question;
}
