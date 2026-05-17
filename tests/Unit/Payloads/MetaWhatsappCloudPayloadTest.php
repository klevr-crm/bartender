<?php

declare(strict_types=1);

use App\Models\ChannelInstance;
use App\Models\Conversation;
use App\Models\Message;
use App\Payloads\MetaWhatsappCloudPayload;

it('builds inbound text payload matching fixture shape', function (): void {
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
        'config' => ['phone_number_id' => '1234567890', 'waba_id' => 'waba_123'],
    ]);

    $payload = MetaWhatsappCloudPayload::inboundText($message, $channel);

    expect($payload['object'])->toBe('whatsapp_business_account');
    expect($payload['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'])->toBe('Hello');
    expect($payload['entry'][0]['changes'][0]['value']['messages'][0]['id'])->toBe('wamid.test');
});

it('generates HMAC header', function (): void {
    $hmac = MetaWhatsappCloudPayload::generateHmac('test-payload', 'my-secret');

    expect($hmac)->toStartWith('sha256=');
    expect(strlen($hmac))->toBeGreaterThan(7);
});
