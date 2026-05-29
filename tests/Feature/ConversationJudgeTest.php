<?php

declare(strict_types=1);

use App\Conversations\ConversationJudge;
use App\Models\Conversation;
use App\Models\ConversationEvaluation;
use App\Models\Message;
use App\Models\Scenario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Structured\Response as StructuredResponse;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

uses(RefreshDatabase::class);

test('judge avalia conversa e persiste evaluation com scores e verdict corretos', function (): void {
    Prism::fake([
        new StructuredResponse(
            steps: collect([]),
            text: '',
            structured: [
                'resolution' => 8,
                'tone' => 9,
                'correct_actions' => 7,
                'hallucination' => 10,
                'constraints' => 9,
                'findings' => ['Bom tom', 'Faltou confirmar prazo'],
                'summary' => 'Atendimento bom',
            ],
            finishReason: FinishReason::Stop,
            usage: new Usage(0, 0),
            meta: new Meta('fake', 'fake'),
            additionalContent: [],
        ),
    ]);

    $scenario = Scenario::factory()->create([
        'script' => 'Solicitar orcamento de servico',
    ]);

    $conversation = Conversation::factory()->create([
        'scenario_id' => $scenario->id,
        'status' => 'completed',
    ]);

    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'direction' => 'inbound',
        'role' => 'user',
        'content' => 'Oi, quero um orcamento.',
    ]);

    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'direction' => 'outbound',
        'role' => 'assistant',
        'content' => 'Claro, qual servico voce precisa?',
    ]);

    $evaluation = app(ConversationJudge::class)->judge($conversation);

    expect($evaluation)->toBeInstanceOf(ConversationEvaluation::class)
        ->and($evaluation->conversation_id)->toBe($conversation->id)
        ->and($evaluation->scores)->toBe([
            'resolution' => 8,
            'tone' => 9,
            'correct_actions' => 7,
            'hallucination' => 10,
            'constraints' => 9,
        ])
        ->and($evaluation->overall_score)->toBe(9)
        ->and($evaluation->verdict)->toBe('pass')
        ->and($evaluation->findings)->toBe(['Bom tom', 'Faltou confirmar prazo'])
        ->and($evaluation->judged_at)->not->toBeNull();
});

test('judge persiste evaluation com verdict=error quando Prism falha', function (): void {
    config()->set('bartender.judge.provider', 'invalid_provider');

    $scenario = Scenario::factory()->create([
        'script' => 'Solicitar orcamento de servico',
    ]);

    $conversation = Conversation::factory()->create([
        'scenario_id' => $scenario->id,
        'status' => 'completed',
    ]);

    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'direction' => 'inbound',
        'role' => 'user',
        'content' => 'Oi, quero um orcamento.',
    ]);

    $evaluation = app(ConversationJudge::class)->judge($conversation);

    expect($evaluation->verdict)->toBe('error')
        ->and($evaluation->overall_score)->toBe(0)
        ->and($evaluation->scores)->toBe([])
        ->and($evaluation->findings[0])->toStartWith('Judge LLM falhou:');
});
