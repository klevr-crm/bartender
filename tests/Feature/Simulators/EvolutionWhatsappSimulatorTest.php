<?php

declare(strict_types=1);

use App\Inbound\InboundDispatcher;
use App\Models\ChannelInstance;
use App\Models\Conversation;
use App\Models\Message;
use App\Simulators\Evolution\EvolutionWhatsappSimulator;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Http::fake();
});

it('posts inbound payload to evolution webhook path', function (): void {
    $simulator = new EvolutionWhatsappSimulator;

    $conversation = new Conversation([
        'external_conversation_id' => '5511999999999@s.whatsapp.net',
    ]);

    $message = new Message([
        'content' => 'Hello Evolution',
        'external_message_id' => 'evmid.test',
    ]);
    $message->setRelation('conversation', $conversation);

    $channel = new ChannelInstance([
        'external_id' => 'evo_instance_1',
        'provider' => 'evolution_whatsapp',
        'config' => ['instance_name' => 'evo_instance_1'],
    ]);

    $payload = $simulator->buildInboundPayload($message, $channel);

    expect($payload['event'])->toBe('messages.upsert');

    $dispatcher = new InboundDispatcher;
    config()->set('bartender.inbound_mode', 'http');
    config()->set('bartender.gateway_url', 'http://gateway.test');

    $dispatcher->send($payload, $channel, '/webhooks/evolution/evo_instance_1');

    Http::assertSent(function ($request): bool {
        return $request->url() === 'http://gateway.test/webhooks/evolution/evo_instance_1';
    });
});
