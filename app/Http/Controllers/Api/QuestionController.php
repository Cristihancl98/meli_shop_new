<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Question\AnswerQuestionRequest;
use App\Models\Question;
use App\Services\QuestionService;
use App\Traits\ApiResponses;
use App\Traits\ResolvesMeliAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    use ApiResponses, ResolvesMeliAccount;

    public function __construct(private readonly QuestionService $questionService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Question::class);

        if (!$account = $this->currentAccount()) {
            return $this->noAccount();
        }

        return $this->ok($this->questionService->list($account, $request->only(['answered', 'seen'])), 'Preguntas obtenidas.');
    }

    public function markSeen(int $id): JsonResponse
    {
        if (!$account = $this->currentAccount()) {
            return $this->noAccount();
        }

        if (!$question = $this->questionService->find($account, $id)) {
            return $this->notFound('Pregunta no encontrada.');
        }

        $this->authorize('answer', $question);

        return $this->ok($this->questionService->markSeen($question, auth()->user()), 'Pregunta marcada como vista.');
    }

    public function answer(AnswerQuestionRequest $request, int $id): JsonResponse
    {
        if (!$account = $this->currentAccount()) {
            return $this->noAccount();
        }

        if (!$question = $this->questionService->find($account, $id)) {
            return $this->notFound('Pregunta no encontrada.');
        }

        $this->authorize('answer', $question);

        $answered = $this->questionService->answer($account, $question, $request->validated('answer'), $request->user());

        return $this->ok($answered, 'Pregunta respondida.');
    }
}
