<?php

declare(strict_types=1);

use App\Conversations\ConversationEngine;
use App\Inbound\InboundDispatcher;
use App\Jobs\JudgeConversationJob;
use App\Models\ChannelInstance;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Persona;
use App\Models\RawPayload;
use App\Models\Scenario;
use App\Personas\PersonaRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Structured\Response as StructuredResponse;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Http::fake();
    Queue::fake();
    config()->set('bartender.ai.max_turns', 50);
});

test('runTurn com closeConversation=true grava end_reason=resolved e despacha JudgeConversationJob', function (): void {
    Prism::fake([
        new StructuredResponse(
            steps: collect([]),
            text: '',
            structured: [
                'message' => 'Perfeito, muito obrigado!',
                'intent' => 'thank_and_close',
                'should_close' => true,
                'satisfaction' => 5,
                'reason' => 'Problema resolvido',
            ],
            finishReason: FinishReason::Stop,
            usage: new Usage(0, 0),
            meta: new Meta('fake', 'fake'),
            additionalContent: [],
        ),
    ]);

    $persona = Persona::factory()->create([
        'provider' => 'openai',
        'model' => 'gpt-4o-mini',
        'system_prompt' => 'Voce e um cliente.',
    ]);

    $scenario = Scenario::factory()->create([
        'persona_id' => $persona->id,
        'script' => 'Solicitar suporte tecnico',
    ]);

    $channel = ChannelInstance::factory()->create();

    $conversation = Conversation::factory()->create([
        'scenario_id' => $scenario->id,
        'persona_id' => $persona->id,
        'channel_instance_id' => $channel->id,
        'status' => 'active',
        'turn_count' => 0,
    ]);

    $runner = new PersonaRunner;
    $engine = new ConversationEngine(
        $runner,
        new InboundDispatcher,
        createMessage: function (array $attrs) use ($conversation): Message {
            $message = new Message($attrs);
            $message->setRelation('conversation', $conversation);

            return $message;
        },
        createRawPayload: fn (array $attrs): RawPayload => new RawPayload($attrs),
    );
    $engine->runTurn($conversation, $channel);

    $conversation->refresh();

    expect($conversation->status)->toBe('completed')
        ->and($conversation->end_reason)->toBe('resolved')
        ->and($conversation->ended_at)->not->toBeNull();

    Queue::assertPushed(JudgeConversationJob::class, function (JudgeConversationJob $job) use ($conversation): bool {
        return $job->conversationId === $conversation->id;
    });
});

test('runTurn atingindo max_turns grava end_reason=max_turns e despacha JudgeConversationJob', function (): void {
    config()->set('bartender.ai.max_turns', 3);

    $persona = Persona::factory()->create([
        'provider' => 'openai',
        'model' => 'gpt-4o-mini',
        'system_prompt' => 'Voce e um cliente.',
    ]);

    $scenario = Scenario::factory()->create([
        'persona_id' => $persona->id,
        'script' => 'Solicitar suporte tecnico',
    ]);

    $channel = ChannelInstance::factory()->create();

    $conversation = Conversation::factory()->create([
        'scenario_id' => $scenario->id,
        'persona_id' => $persona->id,
        'channel_instance_id' => $channel->id,
        'status' => 'active',
        'turn_count' => 2,
    ]);

    $runner = new PersonaRunner;
    $engine = new ConversationEngine(
        $runner,
        new InboundDispatcher,
        createMessage: function (array $attrs) use ($conversation): Message {
            $message = new Message($attrs);
            $message->setRelation('conversation', $conversation);

            return $message;
        },
        createRawPayload: fn (array $attrs): RawPayload => new RawPayload($attrs),
    );
    $engine->runTurn($conversation, $channel);

    $conversation->refresh();

    expect($conversation->status)->toBe('completed')
        ->and($conversation->end_reason)->toBe('max_turns')
        ->and($conversation->ended_at)->not->toBeNull()
        ->and($conversation->turn_count)->toBe(3);

    Queue::assertPushed(JudgeConversationJob::class, function (JudgeConversationJob $job) use ($conversation): bool {
        return $job->conversationId === $conversation->id;
    });
});
