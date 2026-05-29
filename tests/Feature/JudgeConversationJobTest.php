<?php

declare(strict_types=1);

use App\Conversations\ConversationEngine;
use App\Conversations\ConversationJudge;
use App\Inbound\InboundDispatcher;
use App\Jobs\JudgeConversationJob;
use App\Models\ChannelInstance;
use App\Models\Conversation;
use App\Models\ConversationEvaluation;
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

test('ConversationEngine::runTurn despacha JudgeConversationJob quando conversa fecha', function (): void {
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

    Queue::assertPushed(JudgeConversationJob::class, function (JudgeConversationJob $job) use ($conversation): bool {
        return $job->conversationId === $conversation->id;
    });
});

test('JudgeConversationJob handle nao duplica evaluation', function (): void {
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
                'findings' => ['Bom'],
                'summary' => 'Ok',
            ],
            finishReason: FinishReason::Stop,
            usage: new Usage(0, 0),
            meta: new Meta('fake', 'fake'),
            additionalContent: [],
        ),
    ]);

    $conversation = Conversation::factory()->create([
        'status' => 'completed',
    ]);

    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'direction' => 'inbound',
        'role' => 'user',
        'content' => 'Oi',
    ]);

    $job = new JudgeConversationJob($conversation->id);
    $job->handle(app(ConversationJudge::class));

    expect(ConversationEvaluation::where('conversation_id', $conversation->id)->count())->toBe(1);

    $job->handle(app(ConversationJudge::class));

    expect(ConversationEvaluation::where('conversation_id', $conversation->id)->count())->toBe(1);
});

test('JudgeConversationJob handle nao cria evaluation quando judge.enabled=false', function (): void {
    config()->set('bartender.judge.enabled', false);

    $conversation = Conversation::factory()->create([
        'status' => 'completed',
    ]);

    $job = new JudgeConversationJob($conversation->id);
    $job->handle(app(ConversationJudge::class));

    expect(ConversationEvaluation::where('conversation_id', $conversation->id)->count())->toBe(0);
});

test('JudgeConversationJob handle nao cria evaluation quando status nao e terminal', function (): void {
    $conversation = Conversation::factory()->create([
        'status' => 'active',
    ]);

    $job = new JudgeConversationJob($conversation->id);
    $job->handle(app(ConversationJudge::class));

    expect(ConversationEvaluation::where('conversation_id', $conversation->id)->count())->toBe(0);
});
