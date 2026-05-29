<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\ConversationEvaluation;
use App\Models\Message;
use App\Models\Scenario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('bartender:export-evals exporta avaliacoes em JSON', function (): void {
    $scenario = Scenario::factory()->create([
        'slug' => 'orcamento-servico',
        'name' => 'Orcamento de Servico',
        'script' => 'Solicitar orcamento detalhado',
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
        'metadata' => ['intent' => 'ask_pricing', 'satisfaction' => 4],
    ]);

    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'direction' => 'outbound',
        'role' => 'assistant',
        'content' => 'Claro, qual servico voce precisa?',
    ]);

    ConversationEvaluation::factory()->create([
        'conversation_id' => $conversation->id,
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
    ]);

    $outputPath = storage_path('app/test-evals.json');

    Artisan::call('bartender:export-evals', ['--output' => $outputPath]);

    expect(Artisan::output())->toContain('Exportadas');

    expect(file_exists($outputPath))->toBeTrue();

    /** @var array<int, array<string, mixed>> $dataset */
    $dataset = json_decode(file_get_contents($outputPath), true);

    expect($dataset)->toHaveCount(1)
        ->and($dataset[0]['scenario']['slug'])->toBe('orcamento-servico')
        ->and($dataset[0]['scenario']['name'])->toBe('Orcamento de Servico')
        ->and($dataset[0]['scenario']['script'])->toBe('Solicitar orcamento detalhado')
        ->and($dataset[0]['transcript'])->toHaveCount(2)
        ->and($dataset[0]['transcript'][0]['direction'])->toBe('inbound')
        ->and($dataset[0]['transcript'][0]['intent'])->toBe('ask_pricing')
        ->and($dataset[0]['evaluation']['overall_score'])->toBe(9)
        ->and($dataset[0]['evaluation']['verdict'])->toBe('pass')
        ->and($dataset[0]['evaluation']['findings'])->toBe(['Bom tom', 'Faltou confirmar prazo']);

    unlink($outputPath);
});
