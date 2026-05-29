<?php

declare(strict_types=1);

use App\Models\ChannelInstance;
use App\Models\Conversation;
use App\Models\ConversationEvaluation;
use App\Models\Persona;
use App\Models\Scenario;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

test('show inclui o veredito do judge quando ha avaliacao', function (): void {
    $conversation = Conversation::factory()->create([
        'persona_id' => Persona::factory()->create()->id,
        'scenario_id' => Scenario::factory()->create()->id,
        'channel_instance_id' => ChannelInstance::factory()->create()->id,
        'status' => 'completed',
        'turn_count' => 3,
        'ended_at' => now(),
    ]);

    ConversationEvaluation::factory()->create([
        'conversation_id' => $conversation->id,
        'verdict' => 'pass',
        'overall_score' => 9,
        'scores' => ['resolution' => 8, 'tone' => 9],
        'findings' => ['Bom tom', 'Faltou confirmar prazo'],
        'judged_at' => now(),
    ]);

    $response = getJson("/api/conversations/{$conversation->id}");

    $response->assertOk()
        ->assertJsonStructure([
            'id',
            'status',
            'turn_count',
            'turns',
            'evaluation' => [
                'verdict',
                'overall_score',
                'scores',
                'findings',
                'judged_at',
            ],
        ]);

    $response->assertJsonPath('evaluation.verdict', 'pass')
        ->assertJsonPath('evaluation.overall_score', 9)
        ->assertJsonPath('evaluation.findings', ['Bom tom', 'Faltou confirmar prazo']);
});

test('show serializa judged_at null quando a avaliacao ainda nao foi julgada', function (): void {
    $conversation = Conversation::factory()->create([
        'persona_id' => Persona::factory()->create()->id,
        'scenario_id' => Scenario::factory()->create()->id,
        'channel_instance_id' => ChannelInstance::factory()->create()->id,
        'status' => 'completed',
    ]);

    ConversationEvaluation::factory()->create([
        'conversation_id' => $conversation->id,
        'verdict' => 'pending',
        'judged_at' => null,
    ]);

    getJson("/api/conversations/{$conversation->id}")
        ->assertOk()
        ->assertJsonPath('evaluation.verdict', 'pending')
        ->assertJsonPath('evaluation.judged_at', null);
});

test('show retorna evaluation null quando nao ha avaliacao', function (): void {
    $conversation = Conversation::factory()->create([
        'persona_id' => Persona::factory()->create()->id,
        'scenario_id' => Scenario::factory()->create()->id,
        'channel_instance_id' => ChannelInstance::factory()->create()->id,
        'status' => 'active',
    ]);

    getJson("/api/conversations/{$conversation->id}")
        ->assertOk()
        ->assertJsonPath('evaluation', null);
});

test('show retorna 404 para conversa inexistente', function (): void {
    getJson('/api/conversations/99999')
        ->assertNotFound();
});
