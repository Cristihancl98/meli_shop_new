<?php

namespace Tests\Feature\Migrated;

use App\Models\Question;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QuestionControllerTest extends TestCase
{
    public function test_lists_questions_with_filters(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        Question::factory()->count(2)->create(['mercadolibre_account_id' => $account->id]);
        Question::factory()->answered()->create(['mercadolibre_account_id' => $account->id]);
        Question::factory()->create();

        $this->getJson('/api/questions', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.total', 3);

        $this->getJson('/api/questions?answered=0', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.total', 2);
    }

    public function test_marks_question_as_seen(): void
    {
        [$operator, $account] = $this->operatorWithAccount();
        $question = Question::factory()->create(['mercadolibre_account_id' => $account->id]);

        $this->patchJson("/api/questions/{$question->id}/seen", [], $this->authHeaders($operator))
            ->assertOk()
            ->assertJsonPath('data.seen', true);

        $this->assertSame($operator->id, $question->fresh()->handled_by);
    }

    public function test_answers_question_in_meli(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $question = Question::factory()->create(['mercadolibre_account_id' => $account->id, 'meli_question_id' => '777']);

        Http::fake(['api.mercadolibre.com/answers' => Http::response([
            'id'     => 777,
            'answer' => ['text' => 'Sí, disponible', 'status' => 'ACTIVE'],
        ])]);

        $this->postJson("/api/questions/{$question->id}/answer", ['answer' => 'Sí, disponible'], $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.answer', 'Sí, disponible')
            ->assertJsonPath('data.answer_status', 'ACTIVE');

        Http::assertSent(fn ($request) => $request['question_id'] === '777' && $request['text'] === 'Sí, disponible');
    }

    public function test_answer_reports_meli_error(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $question = Question::factory()->create(['mercadolibre_account_id' => $account->id]);

        Http::fake(['api.mercadolibre.com/answers' => Http::response(['message' => 'question closed'], 400)]);

        $this->postJson("/api/questions/{$question->id}/answer", ['answer' => 'Hola'], $this->authHeaders($admin))
            ->assertStatus(502);

        $this->assertNull($question->fresh()->answer);
    }

    public function test_cannot_answer_twice(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $question = Question::factory()->answered()->create(['mercadolibre_account_id' => $account->id]);
        Http::fake();

        $this->postJson("/api/questions/{$question->id}/answer", ['answer' => 'Otra'], $this->authHeaders($admin))
            ->assertUnprocessable();

        Http::assertNothingSent();
    }

    public function test_answer_requires_text(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $question = Question::factory()->create(['mercadolibre_account_id' => $account->id]);

        $this->postJson("/api/questions/{$question->id}/answer", [], $this->authHeaders($admin))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['answer']);
    }
}
