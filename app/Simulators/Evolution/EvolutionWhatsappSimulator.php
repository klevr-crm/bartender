<?php

declare(strict_types=1);

namespace App\Simulators\Evolution;

use App\Models\ChannelInstance;
use App\Models\Message;
use App\Payloads\EvolutionWhatsappPayload;
use App\Simulators\Contracts\ChannelSimulator;
use App\ValueObjects\NormalizedOutbound;

final class EvolutionWhatsappSimulator implements ChannelSimulator
{
    /** @return array<string, mixed> */
    public function buildInboundPayload(Message $msg, ChannelInstance $ch): array
    {
        return EvolutionWhatsappPayload::inboundText($msg, $ch);
    }

    /** @return array<string, mixed> */
    public function buildDeliveryReceipt(Message $msg, ChannelInstance $ch): array
    {
        return EvolutionWhatsappPayload::deliveryReceipt($msg, $ch);
    }

    /** @return array<string, mixed> */
    public function buildReadReceipt(Message $msg, ChannelInstance $ch): array
    {
        return EvolutionWhatsappPayload::readReceipt($msg, $ch);
    }

    /** @param array<string, mixed> $rabbitMsg */
    public function parseOutbound(array $rabbitMsg): NormalizedOutbound
    {
        $data = $rabbitMsg['data'] ?? $rabbitMsg;
        $key = $data['key'] ?? [];

        return new NormalizedOutbound(
            externalMessageId: (string) ($key['id'] ?? uniqid()),
            externalConversationId: (string) ($key['remoteJid'] ?? ''),
            provider: 'evolution',
            channelType: 'whatsapp_baileys',
            content: (string) ($data['message']['conversation'] ?? $data['content'] ?? ''),
            media: $data['media'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    public function provider(): string
    {
        return 'evolution_whatsapp';
    }
}
