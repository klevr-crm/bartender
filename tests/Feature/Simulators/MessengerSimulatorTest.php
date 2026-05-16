<?php

declare(strict_types=1);

use App\Inbound\InboundDispatcher;
use App\Models\ChannelInstance;
use App\Models\Conversation;
use App\Models\Message;
use App\Simulators\Meta\MessengerSimulator;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Http::fake();
});

it('posts inbound payload with HMAC header', function (): void {
    $simulator = new MessengerSimulator;

    $conversation = new Conversation([
        'external_conversation_id' => 'psid_123',
    ]);

    $message = new Message([
        'content' => 'Hello Messenger',
        'external_message_id' => 'mid.test',
    ]);
    $message->setRelation('conversation', $conversation);

    $channel = new ChannelInstance([
        'external_id' => 'page_1',
        'provider' => 'meta_messenger',
        'config' => ['page_id' => 'page_1'],
    ]);

    $payload = $simulator->buildInboundPayload($message, $channel);

    expect($payload['object'])->toBe('page');

    $dispatcher = new InboundDispatcher;
    config()->set('bartender.inbound_mode', 'http');
    config()->set('bartender.gateway_url', 'http://gateway.test');
    config()->set('bartender.meta.app_secret', 'secret123');

    $dispatcher->send($payload, $channel, '/webhooks/meta');

    Http::assertSent(function ($request): bool {
        return $request->hasHeader('X-Hub-Signature-256')
            && $request->url() === 'http://gateway.test/webhooks/meta';
    });
});
