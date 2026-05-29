<?php

declare(strict_types=1);

use App\Livewire\Conversations\Show;
use App\Models\ChannelInstance;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Persona;
use App\Models\Scenario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('renderiza chat com mensagens inbound e outbound', function (): void {
    $persona = Persona::factory()->create();
    $scenario = Scenario::factory()->create();
    $channel = ChannelInstance::factory()->create();

    $conversation = Conversation::factory()->create([
        'persona_id' => $persona->id,
        'scenario_id' => $scenario->id,
        'channel_instance_id' => $channel->id,
        'status' => 'active',
    ]);

    $inbound = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'direction' => 'inbound',
        'role' => 'user',
        'content' => 'Olá, gostaria de ajuda',
    ]);

    $outbound = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'direction' => 'outbound',
        'role' => 'assistant',
        'content' => 'Claro, em que posso ajudar?',
        'status' => 'delivered',
        'delivered_at' => now(),
    ]);

    Livewire::test(Show::class, ['conversation' => $conversation])
        ->assertOk()
        ->assertSee($inbound->content)
        ->assertSee($outbound->content);
});

test('mostra badge de status e dados do cabeçalho', function (): void {
    $persona = Persona::factory()->create();
    $scenario = Scenario::factory()->create();
    $channel = ChannelInstance::factory()->create();

    $conversation = Conversation::factory()->create([
        'persona_id' => $persona->id,
        'scenario_id' => $scenario->id,
        'channel_instance_id' => $channel->id,
        'status' => 'completed',
        'end_reason' => 'resolved',
        'turn_count' => 3,
    ]);

    Livewire::test(Show::class, ['conversation' => $conversation])
        ->assertOk()
        ->assertSee('Concluída')
        ->assertSee($persona->name)
        ->assertSee($scenario->name)
        ->assertSee((string) $conversation->turn_count)
        ->assertSee('resolved');
});

test('mostra intent e satisfaction quando metadata presente', function (): void {
    $conversation = Conversation::factory()->create([
        'status' => 'active',
    ]);

    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'direction' => 'inbound',
        'role' => 'user',
        'content' => 'Qual o preço?',
        'metadata' => ['intent' => 'ask_pricing', 'satisfaction' => 4],
    ]);

    Livewire::test(Show::class, ['conversation' => $conversation])
        ->assertOk()
        ->assertSee('intent: ask_pricing')
        ->assertSee('satisfação: 4/5');
});

test('wire:poll está presente quando conversa está ativa', function (): void {
    $conversation = Conversation::factory()->create([
        'status' => 'active',
    ]);

    Livewire::test(Show::class, ['conversation' => $conversation])
        ->assertOk()
        ->assertSee('wire:poll.2s="pollConversation"', false);
});

test('wire:poll não está presente quando conversa está concluída', function (): void {
    $conversation = Conversation::factory()->create([
        'status' => 'completed',
    ]);

    Livewire::test(Show::class, ['conversation' => $conversation])
        ->assertOk()
        ->assertDontSee('wire:poll');
});
