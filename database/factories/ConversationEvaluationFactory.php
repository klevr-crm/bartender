<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\ConversationEvaluation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConversationEvaluation>
 */
class ConversationEvaluationFactory extends Factory
{
    protected $model = ConversationEvaluation::class;

    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'scores' => [
                'resolution' => 8,
                'tone' => 9,
                'correct_actions' => 7,
                'hallucination' => 10,
                'constraints' => 9,
            ],
            'overall_score' => 9,
            'findings' => ['Bom tom', 'Faltou confirmar prazo'],
            'verdict' => 'pass',
            'judged_at' => now(),
        ];
    }
}
