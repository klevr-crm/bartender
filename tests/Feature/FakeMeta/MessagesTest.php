<?php

declare(strict_types=1);

use App\Jobs\ContinueConversationJob;
use App\Models\ChannelInstance;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Persona;
use App\Models\Scenario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Http::fake();
    Queue::fake();
    config()->set('bartender.fake_meta.enabled', true);
    config()->set('bartender.timing.mode', 'fast');
    config()->set('bartender.timing.fast.min_ms', 1_000);
    config()->set('bartender.timing.fast.max_ms', 5_000);
    config()->set('bartender.ai.max_turns', 50);
});

test('envia mensagem e agenda próximo turno em conversa ativa', function (): void {
    $persona = Persona::factory()->create();
    $scenario = Scenario::factory()->create();
    $channel = ChannelInstance::factory()->create([
        'external_id' => '1234567890',
        'provider' => 'meta_whatsapp_cloud',
        'config' => ['phone_number_id' => '1234567890'],
    ]);

    $conversation = Conversation::factory()->create([
        'persona_id' => $persona->id,
        'scenario_id' => $scenario->id,
        'channel_instance_id' => $channel->id,
        'status' => 'active',
        'turn_count' => 1,
        'started_at' => now(),
    ]);

    $response = postJson('/fake-meta/v23.0/1234567890/messages', [
        'messaging_product' => 'whatsapp',
        'to' => '5511999999999',
        'type' => 'text',
        'text' => ['body' => 'Olá do CRM'],
    ]);

    $response->assertOk()
        ->assertJsonPath('messaging_product', 'whatsapp')
        ->assertJsonPath('contacts.0.input', '5511999999999')
        ->assertJsonPath('contacts.0.wa_id', '5511999999999');

    $messageId = $response->json('messages.0.id');
    expect($messageId)->toStartWith('wamid.');

    $message = Message::query()
        ->where('conversation_id', $conversation->id)
        ->where('direction', 'outbound')
        ->where('role', 'assistant')
        ->first();

    expect($message)->not->toBeNull();
    expect($message->content)->toBe('Olá do CRM');
    expect($message->external_message_id)->toStartWith('wamid.');

    Queue::assertPushed(ContinueConversationJob::class, function (ContinueConversationJob $job) use ($conversation): bool {
        return $job->conversationId === $conversation->id;
    });
});

test('retorna id mas não agenda turno quando não há conversa ativa', function (): void {
    $channel = ChannelInstance::factory()->create([
        'external_id' => '1234567890',
        'provider' => 'meta_whatsapp_cloud',
        'config' => ['phone_number_id' => '1234567890'],
    ]);

    Conversation::factory()->create([
        'channel_instance_id' => $channel->id,
        'status' => 'completed',
        'turn_count' => 1,
    ]);

    $response = postJson('/fake-meta/v23.0/1234567890/messages', [
        'messaging_product' => 'whatsapp',
        'to' => '5511999999999',
        'type' => 'text',
        'text' => ['body' => 'Olá do CRM'],
    ]);

    $response->assertOk()
        ->assertJsonPath('messages.0.id', fn (string $id): bool => str_starts_with($id, 'wamid.'));

    Queue::assertNotPushed(ContinueConversationJob::class);
});
