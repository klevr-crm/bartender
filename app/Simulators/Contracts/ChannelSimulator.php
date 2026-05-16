<?php

declare(strict_types=1);

namespace App\Simulators\Contracts;

use App\Models\ChannelInstance;
use App\Models\Message;
use App\ValueObjects\NormalizedOutbound;

interface ChannelSimulator
{
    /** @return array<string, mixed> */
    public function buildInboundPayload(Message $msg, ChannelInstance $ch): array;

    /** @return array<string, mixed> */
    public function buildDeliveryReceipt(Message $msg, ChannelInstance $ch): array;

    /** @return array<string, mixed> */
    public function buildReadReceipt(Message $msg, ChannelInstance $ch): array;

    /** @param array<string, mixed> $rabbitMsg */
    public function parseOutbound(array $rabbitMsg): NormalizedOutbound;

    public function provider(): string;
}
