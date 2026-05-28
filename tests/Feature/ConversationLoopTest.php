<?php

declare(strict_types=1);

use App\Conversations\ConversationEngine;
use App\Jobs\ContinueConversationJob;
use App\Models\ChannelInstance;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\RawPayload;
use App\Outbound\OutboundResponseHandler;
use App\Personas\HumanTimingService;
use App\Simulators\Meta\WhatsappCloudSimulator;
use App\ValueObjects\NormalizedOutbound;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    Queue::fake();
    Http::fake();
    config()->set('bartender.timing.mode', 'fast');
    config()->set('bartender.timing.fast.min_ms', 1_000);
    config()->set('bartender.timing.fast.max_ms', 5_000);
    config()->set('bartender.ai.max_turns', 50);
});

test('handler despacha ContinueConversationJob ao receber outbound em conversa active', function (): void {
    $conversation = new Conversation;
    $conversation->forceFill([
        'id' => 1,
        'status' => 'active',
        'turn_count' => 1,
    ]);
    $conversation->setRelation('channelInstance', new ChannelInstance([
        'id' => 1,
        'provider' => 'meta_whatsapp_cloud',
    ]));

    $timing = new HumanTimingService(fn (): float => 0.5);

    $handler = new OutboundResponseHandler(
        app(ConversationEngine::class),
        $timing,
        createMessage: fn (array $attrs): Message => new Message($attrs),
        createRawPayload: fn (array $attrs): RawPayload => new RawPayload($attrs),
        refreshConversation: function (Conversation $c): void {},
        sendAck: function (Conversation $conv, ChannelInstance $ch, Message $msg): void {},
    );

    $normalized = new NormalizedOutbound(
        externalMessageId: 'msg-1',
        externalConversationId: 'conv-1',
        provider: 'meta',
        channelType: 'whatsapp_cloud',
        content: 'Olá, como posso ajudar?',
    );

    $handler->handle($conversation, $normalized, new WhatsappCloudSimulator);

    Queue::assertPushed(ContinueConversationJob::class, function (ContinueConversationJob $job): bool {
        return $job->conversationId === 1;
    });
});

test('handler NÃO despacha quando turn_count >= max_turns', function (): void {
    config()->set('bartender.ai.max_turns', 5);

    $conversation = new Conversation;
    $conversation->forceFill([
        'id' => 2,
        'status' => 'active',
        'turn_count' => 5,
    ]);
    $conversation->setRelation('channelInstance', new ChannelInstance([
        'id' => 1,
        'provider' => 'meta_whatsapp_cloud',
    ]));

    $timing = new HumanTimingService(fn (): float => 0.5);

    $handler = new OutboundResponseHandler(
        app(ConversationEngine::class),
        $timing,
        createMessage: fn (array $attrs): Message => new Message($attrs),
        createRawPayload: fn (array $attrs): RawPayload => new RawPayload($attrs),
        refreshConversation: function (Conversation $c): void {},
        sendAck: function (Conversation $conv, ChannelInstance $ch, Message $msg): void {},
    );

    $normalized = new NormalizedOutbound(
        externalMessageId: 'msg-1',
        externalConversationId: 'conv-2',
        provider: 'meta',
        channelType: 'whatsapp_cloud',
        content: 'Resposta do agente',
    );

    $handler->handle($conversation, $normalized, new WhatsappCloudSimulator);

    Queue::assertNotPushed(ContinueConversationJob::class);
});

test('handler NÃO despacha quando conversa não está active', function (): void {
    $conversation = new Conversation;
    $conversation->forceFill([
        'id' => 3,
        'status' => 'completed',
        'turn_count' => 2,
    ]);
    $conversation->setRelation('channelInstance', new ChannelInstance([
        'id' => 1,
        'provider' => 'meta_whatsapp_cloud',
    ]));

    $timing = new HumanTimingService(fn (): float => 0.5);

    $handler = new OutboundResponseHandler(
        app(ConversationEngine::class),
        $timing,
        createMessage: fn (array $attrs): Message => new Message($attrs),
        createRawPayload: fn (array $attrs): RawPayload => new RawPayload($attrs),
        refreshConversation: function (Conversation $c): void {},
        sendAck: function (Conversation $conv, ChannelInstance $ch, Message $msg): void {},
    );

    $normalized = new NormalizedOutbound(
        externalMessageId: 'msg-1',
        externalConversationId: 'conv-3',
        provider: 'meta',
        channelType: 'whatsapp_cloud',
        content: 'Resposta do agente',
    );

    $handler->handle($conversation, $normalized, new WhatsappCloudSimulator);

    Queue::assertNotPushed(ContinueConversationJob::class);
});
