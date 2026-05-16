<?php

declare(strict_types=1);

namespace App\Simulators\Meta;

use App\Models\ChannelInstance;
use App\Models\Message;
use App\Payloads\MetaWhatsappCloudPayload;
use App\Simulators\Contracts\ChannelSimulator;
use App\ValueObjects\NormalizedOutbound;

final class WhatsappCloudSimulator implements ChannelSimulator
{
    /** @return array<string, mixed> */
    public function buildInboundPayload(Message $msg, ChannelInstance $ch): array
    {
        return MetaWhatsappCloudPayload::inboundText($msg, $ch);
    }

    /** @return array<string, mixed> */
    public function buildDeliveryReceipt(Message $msg, ChannelInstance $ch): array
    {
        return MetaWhatsappCloudPayload::deliveryReceipt($msg, $ch);
    }

    /** @return array<string, mixed> */
    public function buildReadReceipt(Message $msg, ChannelInstance $ch): array
    {
        return MetaWhatsappCloudPayload::readReceipt($msg, $ch);
    }

    /** @param array<string, mixed> $rabbitMsg */
    public function parseOutbound(array $rabbitMsg): NormalizedOutbound
    {
        $data = $rabbitMsg['data'] ?? $rabbitMsg;

        return new NormalizedOutbound(
            externalMessageId: (string) ($data['id'] ?? uniqid()),
            externalConversationId: (string) ($data['to'] ?? ''),
            provider: 'meta',
            channelType: 'whatsapp_cloud',
            content: (string) ($data['text']['body'] ?? $data['content'] ?? ''),
            media: $data['media'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    public function provider(): string
    {
        return 'meta_whatsapp_cloud';
    }
}
