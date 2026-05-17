<?php

declare(strict_types=1);

use App\Inbound\InboundDispatcher;
use App\Models\ChannelInstance;
use App\Models\Conversation;
use App\Models\Message;
use App\Simulators\Meta\InstagramDirectSimulator;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Http::fake();
});

it('posts inbound payload with HMAC header', function (): void {
    $simulator = new InstagramDirectSimulator;

    $conversation = new Conversation([
        'external_conversation_id' => 'ig_user_123',
    ]);

    $message = new Message([
        'content' => 'Hello IG',
        'external_message_id' => 'igmid.test',
    ]);
    $message->setRelation('conversation', $conversation);

    $channel = new ChannelInstance([
        'external_id' => 'ig_account_1',
        'provider' => 'meta_instagram_direct',
        'config' => ['instagram_business_account_id' => 'ig_account_1'],
    ]);

    $payload = $simulator->buildInboundPayload($message, $channel);

    expect($payload['object'])->toBe('instagram');

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
