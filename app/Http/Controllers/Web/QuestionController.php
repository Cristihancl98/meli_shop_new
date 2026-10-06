<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Question\AnswerQuestionRequest;
use App\Models\Question;
use App\Services\QuestionService;
use App\Traits\ResolvesMeliAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class QuestionController extends Controller
{
    use ResolvesMeliAccount;

    public function __construct(private readonly QuestionService $questionService) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Question::class);

        $account   = $this->currentAccount();
        $questions = $account
            ? $this->questionService->list($account, $request->only(['answered', 'seen']))
            : new LengthAwarePaginator([], 0, 20);

        return view('questions.index', compact('account', 'questions'));
    }

    public function seen(int $id): RedirectResponse
    {
        $question = $this->resolve($id);
        $this->questionService->markSeen($question, auth()->user());

        return back();
    }

    public function answer(AnswerQuestionRequest $request, int $id): RedirectResponse
    {
        $question = $this->resolve($id);
        $this->questionService->answer($this->currentAccount(), $question, $request->validated('answer'), $request->user());

        return back()->with('success', 'Pregunta respondida en Mercado Libre.');
    }

    private function resolve(int $id): Question
    {
        $account  = $this->currentAccount();
        $question = $account ? $this->questionService->find($account, $id) : null;
        abort_if(!$question, 404);

        $this->authorize('answer', $question);

        return $question;
    }
}
