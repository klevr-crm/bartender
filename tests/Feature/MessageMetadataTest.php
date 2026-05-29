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
use Prism\Prism\Structured\Response as StructuredResponse;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

beforeEach(function (): void {
    Http::fake();
    config()->set('bartender.ai.max_turns', 50);
});

test('runTurn persiste metadata com intent e satisfaction na inbound Message', function (): void {
    Prism::fake([
        new StructuredResponse(
            steps: collect([]),
            text: '',
            structured: [
                'message' => 'Quero saber o preço',
                'intent' => 'ask_pricing',
                'should_close' => false,
                'satisfaction' => 4,
                'reason' => '',
            ],
            finishReason: FinishReason::Stop,
            usage: new Usage(0, 0),
            meta: new Meta('fake', 'fake'),
            additionalContent: [],
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

    $createdMessage = null;

    $runner = new PersonaRunner;
    $engine = new ConversationEngine(
        $runner,
        new InboundDispatcher,
        createMessage: function (array $attrs) use (&$createdMessage, $conversation): Message {
            $message = new Message($attrs);
            $message->setRelation('conversation', $conversation);
            $createdMessage = $message;

            return $message;
        },
        createRawPayload: fn (array $attrs): RawPayload => new RawPayload($attrs),
    );

    $engine->runTurn($conversation, $channel);

    expect($createdMessage)->not->toBeNull()
        ->and($createdMessage->content)->toBe('Quero saber o preço')
        ->and($createdMessage->metadata)->toBe([
            'intent' => 'ask_pricing',
            'satisfaction' => 4,
        ]);
});

test('runTurn persiste metadata null quando intent e satisfaction são null', function (): void {
    Prism::fake([
        new StructuredResponse(
            steps: collect([]),
            text: '',
            structured: [
                'message' => 'Oi',
                'intent' => '',
                'should_close' => false,
                'satisfaction' => 3,
                'reason' => '',
            ],
            finishReason: FinishReason::Stop,
            usage: new Usage(0, 0),
            meta: new Meta('fake', 'fake'),
            additionalContent: [],
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
        'id' => 2,
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

    $createdMessage = null;

    $runner = new PersonaRunner;
    $engine = new ConversationEngine(
        $runner,
        new InboundDispatcher,
        createMessage: function (array $attrs) use (&$createdMessage, $conversation): Message {
            $message = new Message($attrs);
            $message->setRelation('conversation', $conversation);
            $createdMessage = $message;

            return $message;
        },
        createRawPayload: fn (array $attrs): RawPayload => new RawPayload($attrs),
    );

    $engine->runTurn($conversation, $channel);

    expect($createdMessage)->not->toBeNull()
        ->and($createdMessage->metadata)->toBe([
            'satisfaction' => 3,
        ]);
});
