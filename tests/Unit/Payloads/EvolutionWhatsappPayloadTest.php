<?php

declare(strict_types=1);

use App\Models\ChannelInstance;
use App\Models\Conversation;
use App\Models\Message;
use App\Payloads\EvolutionWhatsappPayload;

it('builds inbound text payload matching fixture shape', function (): void {
    $conversation = new Conversation([
        'external_conversation_id' => '5511999999999@s.whatsapp.net',
    ]);

    $message = new Message([
        'content' => 'Hello',
        'external_message_id' => 'evmid.test',
    ]);
    $message->setRelation('conversation', $conversation);

    $channel = new ChannelInstance([
        'external_id' => 'evo_instance_1',
        'config' => ['instance_name' => 'evo_instance_1'],
    ]);

    $payload = EvolutionWhatsappPayload::inboundText($message, $channel);

    expect($payload['event'])->toBe('messages.upsert');
    expect($payload['data']['key']['remoteJid'])->toBe('5511999999999@s.whatsapp.net');
    expect($payload['data']['message']['conversation'])->toBe('Hello');
});
