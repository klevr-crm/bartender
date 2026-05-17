<?php

declare(strict_types=1);

use App\Models\ChannelInstance;
use App\Models\Conversation;
use App\Models\Message;
use App\Payloads\MetaMessengerPayload;

it('builds inbound text payload matching fixture shape', function (): void {
    $conversation = new Conversation([
        'external_conversation_id' => 'psid_123',
    ]);

    $message = new Message([
        'content' => 'Hello',
        'external_message_id' => 'mid.test',
    ]);
    $message->setRelation('conversation', $conversation);

    $channel = new ChannelInstance([
        'external_id' => 'page_1',
        'config' => ['page_id' => 'page_1'],
    ]);

    $payload = MetaMessengerPayload::inboundText($message, $channel);

    expect($payload['object'])->toBe('page');
    expect($payload['entry'][0]['messaging'][0]['message']['text'])->toBe('Hello');
});

it('generates HMAC header', function (): void {
    $hmac = MetaMessengerPayload::generateHmac('test-payload', 'my-secret');

    expect($hmac)->toStartWith('sha256=');
});
