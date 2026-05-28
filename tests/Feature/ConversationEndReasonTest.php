<?php

declare(strict_types=1);

use App\Conversations\ConversationEngine;
use App\Inbound\InboundDispatcher;
use App\Models\ChannelInstance;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Persona;
use App\Models\RawPayload;
use App\Personas\PersonaRunner;
use Illuminate\Support\Facades\Http;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\Response as TextResponse;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

beforeEach(function (): void {
    Http::fake();
    config()->set('bartender.ai.max_turns', 50);
});

test('runTurn com closeConversation=true grava end_reason=resolved', function (): void {
    Prism::fake([
        new TextResponse(
            steps: collect([]),
            text: 'Tudo certo, obrigado! [end]',
            finishReason: FinishReason::Stop,
            toolCalls: [],
            toolResults: [],
            usage: new Usage(0, 0),
            meta: new Meta('fake', 'fake'),
            messages: collect([]),
        ),
    ]);

    $persona = new Persona([
        'id' => 1,
        'provider' => 'openai',
        'model' => 'gpt-4o-mini',
        'system_prompt' => 'Você é um cliente.',
    ]);

    $conversation = new Conversation;
    $conversation->forceFill([
        'id' => 1,
        'scenario_id' => 1,
        'persona_id' => 1,
        'channel_instance_id' => 1,
        'status' => 'active',
        'turn_count' => 0,
    ]);
    $conversation->setRelation('persona', $persona);
    $conversation->setRelation('messages', collect([]));

    $channel = new ChannelInstance([
        'id' => 1,
        'provider' => 'meta_whatsapp_cloud',
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

    expect($conversation->status)->toBe('completed')
        ->and($conversation->end_reason)->toBe('resolved')
        ->and($conversation->ended_at)->not->toBeNull();
});

test('runTurn atingindo max_turns grava end_reason=max_turns', function (): void {
    config()->set('bartender.ai.max_turns', 3);

    $persona = new Persona([
        'id' => 1,
        'provider' => 'openai',
        'model' => 'gpt-4o-mini',
        'system_prompt' => 'Você é um cliente.',
    ]);

    $conversation = new Conversation;
    $conversation->forceFill([
        'id' => 2,
        'scenario_id' => 1,
        'persona_id' => 1,
        'channel_instance_id' => 1,
        'status' => 'active',
        'turn_count' => 2,
    ]);
    $conversation->setRelation('persona', $persona);
    $conversation->setRelation('messages', collect([]));

    $channel = new ChannelInstance([
        'id' => 1,
        'provider' => 'meta_whatsapp_cloud',
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

    expect($conversation->status)->toBe('completed')
        ->and($conversation->end_reason)->toBe('max_turns')
        ->and($conversation->ended_at)->not->toBeNull()
        ->and($conversation->turn_count)->toBe(3);
});
