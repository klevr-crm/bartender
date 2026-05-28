<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\Persona;
use App\Models\Scenario;
use App\Personas\PersonaRunner;
use App\ValueObjects\TurnResult;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Structured\Response as StructuredResponse;
use Prism\Prism\Text\Response as TextResponse;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

beforeEach(function (): void {
    config()->set('bartender.ai.max_turns', 50);
    config()->set('bartender.ai.max_tokens', 400);
});

test('nextTurn retorna TurnResult com structured output completo', function (): void {
    Prism::fake([
        new StructuredResponse(
            steps: collect([]),
            text: '',
            structured: [
                'message' => 'Quanto custa o plano mensal?',
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

    $scenario = new Scenario([
        'id' => 1,
        'script' => 'Descobrir preço do CRM',
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
    $conversation->setRelation('scenario', $scenario);
    $conversation->setRelation('messages', collect([]));

    $runner = new PersonaRunner;
    $turn = $runner->nextTurn($conversation);

    expect($turn)
        ->toBeInstanceOf(TurnResult::class)
        ->and($turn->text)->toBe('Quanto custa o plano mensal?')
        ->and($turn->intent)->toBe('ask_pricing')
        ->and($turn->satisfaction)->toBe(4)
        ->and($turn->closeConversation)->toBeFalse()
        ->and($turn->endReason)->toBeNull();
});

test('should_close=true com satisfaction>=3 define endReason=resolved', function (): void {
    Prism::fake([
        new StructuredResponse(
            steps: collect([]),
            text: '',
            structured: [
                'message' => 'Perfeito, obrigado!',
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

    $runner = new PersonaRunner;
    $turn = $runner->nextTurn($conversation);

    expect($turn->closeConversation)->toBeTrue()
        ->and($turn->endReason)->toBe('resolved')
        ->and($turn->satisfaction)->toBe(5);
});

test('should_close=true com satisfaction<3 define endReason=abandoned', function (): void {
    Prism::fake([
        new StructuredResponse(
            steps: collect([]),
            text: '',
            structured: [
                'message' => 'Não vou continuar, estou frustrado.',
                'intent' => 'abandon',
                'should_close' => true,
                'satisfaction' => 2,
                'reason' => 'Não resolveram meu problema',
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
        'id' => 3,
        'scenario_id' => 1,
        'persona_id' => 1,
        'channel_instance_id' => 1,
        'status' => 'active',
        'turn_count' => 0,
    ]);
    $conversation->setRelation('persona', $persona);
    $conversation->setRelation('messages', collect([]));

    $runner = new PersonaRunner;
    $turn = $runner->nextTurn($conversation);

    expect($turn->closeConversation)->toBeTrue()
        ->and($turn->endReason)->toBe('abandoned')
        ->and($turn->satisfaction)->toBe(2);
});

test('fallback para texto puro quando structured falha', function (): void {
    // Structured vazio (message='') aciona o fallback para texto puro
    Prism::fake([
        new StructuredResponse(
            steps: collect([]),
            text: '',
            structured: [],
            finishReason: FinishReason::Stop,
            usage: new Usage(0, 0),
            meta: new Meta('fake', 'fake'),
            additionalContent: [],
        ),
        new TextResponse(
            steps: collect([]),
            text: 'Tudo certo, obrigado! [end]',
            finishReason: FinishReason::Stop,
            toolCalls: [],
            toolResults: [],
            usage: new Usage(0, 0),
            meta: new Meta('fake', 'fake'),
            messages: collect([]),
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
        'id' => 4,
        'scenario_id' => 1,
        'persona_id' => 1,
        'channel_instance_id' => 1,
        'status' => 'active',
        'turn_count' => 0,
    ]);
    $conversation->setRelation('persona', $persona);
    $conversation->setRelation('messages', collect([]));

    $runner = new PersonaRunner;
    $turn = $runner->nextTurn($conversation);

    expect($turn->text)->toBe('Tudo certo, obrigado! [end]')
        ->and($turn->closeConversation)->toBeTrue()
        ->and($turn->intent)->toBeNull()
        ->and($turn->satisfaction)->toBeNull();
});

test('max_turns atingido retorna closeConversation=true sem chamar LLM', function (): void {
    Prism::fake([]);

    $persona = new Persona([
        'id' => 1,
        'provider' => 'openai',
        'model' => 'gpt-4o-mini',
        'system_prompt' => 'Você é um cliente.',
    ]);

    $conversation = new Conversation;
    $conversation->forceFill([
        'id' => 5,
        'scenario_id' => 1,
        'persona_id' => 1,
        'channel_instance_id' => 1,
        'status' => 'active',
        'turn_count' => 50,
    ]);
    $conversation->setRelation('persona', $persona);
    $conversation->setRelation('messages', collect([]));

    $runner = new PersonaRunner;
    $turn = $runner->nextTurn($conversation);

    expect($turn->closeConversation)->toBeTrue()
        ->and($turn->text)->toBe('')
        ->and($turn->intent)->toBeNull()
        ->and($turn->satisfaction)->toBeNull();

    // Nenhuma chamada ao Prism deve ter sido feita
    $fake = Prism::fake([]);
    $fake->assertCallCount(0);
});
