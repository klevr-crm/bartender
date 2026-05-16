<?php

declare(strict_types=1);

use App\Inbound\InboundDispatcher;
use App\Models\ChannelInstance;
use App\Models\Conversation;
use App\Models\Message;
use App\Simulators\Meta\WhatsappCloudSimulator;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Http::fake();
});

it('posts inbound payload with HMAC header', function (): void {
    $simulator = new WhatsappCloudSimulator;

    $conversation = new Conversation([
        'external_conversation_id' => '5511999999999',
    ]);

    $message = new Message([
        'content' => 'Hello',
        'external_message_id' => 'wamid.test',
    ]);
    $message->setRelation('conversation', $conversation);

    $channel = new ChannelInstance([
        'external_id' => '1234567890',
        'provider' => 'meta_whatsapp_cloud',
        'config' => ['phone_number_id' => '1234567890', 'waba_id' => 'waba_123'],
    ]);

    $payload = $simulator->buildInboundPayload($message, $channel);

    expect($payload['object'])->toBe('whatsapp_business_account');
    expect($payload['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'])->toBe('Hello');

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
