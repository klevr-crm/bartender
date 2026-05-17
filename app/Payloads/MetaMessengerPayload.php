<?php

declare(strict_types=1);

namespace App\Payloads;

use App\Models\ChannelInstance;
use App\Models\Message;

final class MetaMessengerPayload
{
    /** @return array<string, mixed> */
    public static function inboundText(Message $msg, ChannelInstance $ch): array
    {
        /** @var array<string, mixed> $config */
        $config = $ch->config ?? [];
        $pageId = (string) ($config['page_id'] ?? $ch->external_id);
        $from = $msg->conversation->external_conversation_id ?? 'psid_123';
        $timestamp = (string) now()->getTimestamp();

        return [
            'object' => 'page',
            'entry' => [
                [
                    'id' => $pageId,
                    'time' => (int) $timestamp * 1000,
                    'messaging' => [
                        [
                            'sender' => ['id' => $from],
                            'recipient' => ['id' => $pageId],
                            'timestamp' => (int) $timestamp * 1000,
                            'message' => [
                                'mid' => $msg->external_message_id ?? 'mid.'.uniqid(),
                                'text' => $msg->content,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function deliveryReceipt(Message $msg, ChannelInstance $ch): array
    {
        /** @var array<string, mixed> $config */
        $config = $ch->config ?? [];
        $pageId = (string) ($config['page_id'] ?? $ch->external_id);
        $from = $msg->conversation->external_conversation_id ?? 'psid_123';
        $timestamp = (string) now()->getTimestamp();

        return [
            'object' => 'page',
            'entry' => [
                [
                    'id' => $pageId,
                    'time' => (int) $timestamp * 1000,
                    'messaging' => [
                        [
                            'sender' => ['id' => $pageId],
                            'recipient' => ['id' => $from],
                            'timestamp' => (int) $timestamp * 1000,
                            'delivery' => [
                                'mids' => [$msg->external_message_id ?? 'mid.'.uniqid()],
                                'watermark' => (int) $timestamp * 1000,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function readReceipt(Message $msg, ChannelInstance $ch): array
    {
        /** @var array<string, mixed> $config */
        $config = $ch->config ?? [];
        $pageId = (string) ($config['page_id'] ?? $ch->external_id);
        $from = $msg->conversation->external_conversation_id ?? 'psid_123';
        $timestamp = (string) now()->getTimestamp();

        return [
            'object' => 'page',
            'entry' => [
                [
                    'id' => $pageId,
                    'time' => (int) $timestamp * 1000,
                    'messaging' => [
                        [
                            'sender' => ['id' => $from],
                            'recipient' => ['id' => $pageId],
                            'timestamp' => (int) $timestamp * 1000,
                            'read' => [
                                'watermark' => (int) $timestamp * 1000,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function generateHmac(string $payload, string $secret): string
    {
        return 'sha256='.hash_hmac('sha256', $payload, $secret);
    }
}
