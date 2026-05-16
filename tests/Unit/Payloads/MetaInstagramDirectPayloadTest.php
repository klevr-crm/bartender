<?php

declare(strict_types=1);

use App\Models\ChannelInstance;
use App\Models\Conversation;
use App\Models\Message;
use App\Payloads\MetaInstagramDirectPayload;

it('builds inbound text payload matching fixture shape', function (): void {
    $conversation = new Conversation([
        'external_conversation_id' => 'ig_user_123',
    ]);

    $message = new Message([
        'content' => 'Hello',
        'external_message_id' => 'igmid.test',
    ]);
    $message->setRelation('conversation', $conversation);

    $channel = new ChannelInstance([
        'external_id' => 'ig_account_1',
        'config' => ['instagram_business_account_id' => 'ig_account_1'],
    ]);

    $payload = MetaInstagramDirectPayload::inboundText($message, $channel);

    expect($payload['object'])->toBe('instagram');
    expect($payload['entry'][0]['messaging'][0]['message']['text'])->toBe('Hello');
});

it('generates HMAC header', function (): void {
    $hmac = MetaInstagramDirectPayload::generateHmac('test-payload', 'my-secret');

    expect($hmac)->toStartWith('sha256=');
});
