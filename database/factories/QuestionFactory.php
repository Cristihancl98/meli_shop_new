<?php

namespace Database\Factories;

use App\Models\MercadolibreAccount;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'mercadolibre_account_id' => MercadolibreAccount::factory(),
            'meli_question_id'        => $this->faker->unique()->numerify('1##########'),
            'meli_item_id'            => 'MCO' . $this->faker->numerify('#########'),
            'question'                => $this->faker->sentence() . '?',
            'question_status'         => 'UNANSWERED',
            'asked_at'                => now()->subHour(),
            'buyer_meli_id'           => $this->faker->numerify('#########'),
            'buyer_nickname'          => strtoupper($this->faker->userName()),
            'seen'                    => false,
        ];
    }

    public function answered(): static
    {
        return $this->state([
            'answer'          => $this->faker->sentence(),
            'answer_status'   => 'ACTIVE',
            'answered_at'     => now(),
            'question_status' => 'ANSWERED',
        ]);
    }
}
