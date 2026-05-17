<?php

declare(strict_types=1);

namespace App\Simulators\Meta;

use App\Models\ChannelInstance;
use App\Models\Message;
use App\Payloads\MetaMessengerPayload;
use App\Simulators\Contracts\ChannelSimulator;
use App\ValueObjects\NormalizedOutbound;

final class MessengerSimulator implements ChannelSimulator
{
    /** @return array<string, mixed> */
    public function buildInboundPayload(Message $msg, ChannelInstance $ch): array
    {
        return MetaMessengerPayload::inboundText($msg, $ch);
    }

    /** @return array<string, mixed> */
    public function buildDeliveryReceipt(Message $msg, ChannelInstance $ch): array
    {
        return MetaMessengerPayload::deliveryReceipt($msg, $ch);
    }

    /** @return array<string, mixed> */
    public function buildReadReceipt(Message $msg, ChannelInstance $ch): array
    {
        return MetaMessengerPayload::readReceipt($msg, $ch);
    }

    /** @param array<string, mixed> $rabbitMsg */
    public function parseOutbound(array $rabbitMsg): NormalizedOutbound
    {
        $data = $rabbitMsg['data'] ?? $rabbitMsg;

        return new NormalizedOutbound(
            externalMessageId: (string) ($data['id'] ?? uniqid()),
            externalConversationId: (string) ($data['to'] ?? ''),
            provider: 'meta',
            channelType: 'messenger',
            content: (string) ($data['text']['body'] ?? $data['content'] ?? ''),
            media: $data['media'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    public function provider(): string
    {
        return 'meta_messenger';
    }
}
