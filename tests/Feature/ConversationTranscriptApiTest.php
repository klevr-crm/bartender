<?php

declare(strict_types=1);

use App\Models\ChannelInstance;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Persona;
use App\Models\Scenario;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

test('retorna transcript completo de uma conversa', function (): void {
    $persona = Persona::factory()->create();
    $scenario = Scenario::factory()->create();
    $channel = ChannelInstance::factory()->create();

    $conversation = Conversation::factory()->create([
        'persona_id' => $persona->id,
        'scenario_id' => $scenario->id,
        'channel_instance_id' => $channel->id,
        'status' => 'completed',
        'end_reason' => 'resolved',
        'turn_count' => 2,
        'started_at' => now()->subMinutes(10),
        'ended_at' => now(),
    ]);

    $inboundWithMeta = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'direction' => 'inbound',
        'role' => 'user',
        'content' => 'Quero saber o preço',
        'metadata' => ['intent' => 'ask_pricing', 'satisfaction' => 4],
        'status' => 'sent',
        'sent_at' => now()->subMinutes(9),
    ]);

    $inboundWithoutMeta = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'direction' => 'inbound',
        'role' => 'user',
        'content' => 'Oi',
        'metadata' => null,
        'status' => 'sent',
        'sent_at' => now()->subMinutes(8),
    ]);

    $outbound = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'direction' => 'outbound',
        'role' => 'assistant',
        'content' => 'Claro, nosso preço é R$ 99',
        'metadata' => null,
        'status' => 'delivered',
        'sent_at' => now()->subMinutes(7),
        'delivered_at' => now()->subMinutes(7),
    ]);

    $response = getJson("/api/conversations/{$conversation->id}/transcript");

    $response->assertOk()
        ->assertJsonStructure([
            'conversation' => [
                'id',
                'external_conversation_id',
                'status',
                'end_reason',
                'turn_count',
                'started_at',
                'ended_at',
                'error',
                'scenario' => ['slug', 'name'],
                'persona' => ['slug', 'name'],
                'channel' => ['provider', 'external_id'],
            ],
            'messages' => [
                '*' => [
                    'direction',
                    'role',
                    'content',
                    'status',
                    'intent',
                    'satisfaction',
                    'sent_at',
                    'delivered_at',
                    'read_at',
                    'created_at',
                ],
            ],
        ]);

    $response->assertJsonPath('conversation.id', $conversation->id)
        ->assertJsonPath('conversation.status', 'completed')
        ->assertJsonPath('conversation.end_reason', 'resolved')
        ->assertJsonPath('conversation.scenario.slug', $scenario->slug)
        ->assertJsonPath('conversation.persona.name', $persona->name)
        ->assertJsonPath('conversation.channel.provider', $channel->provider);

    $response->assertJsonFragment([
        'content' => 'Quero saber o preço',
        'intent' => 'ask_pricing',
        'satisfaction' => 4,
    ]);

    $response->assertJsonFragment([
        'content' => 'Oi',
        'intent' => null,
        'satisfaction' => null,
    ]);

    $response->assertJsonFragment([
        'content' => 'Claro, nosso preço é R$ 99',
        'status' => 'delivered',
    ]);

    $response->assertJsonMissing(['rawPayloads' => []]);
});

test('retorna 404 para conversa inexistente', function (): void {
    getJson('/api/conversations/99999/transcript')
        ->assertNotFound();
});
