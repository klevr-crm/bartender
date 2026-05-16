<?php

declare(strict_types=1);

namespace App\Payloads;

use App\Models\ChannelInstance;
use App\Models\Message;

final class MetaWhatsappCloudPayload
{
    /** @return array<string, mixed> */
    public static function inboundText(Message $msg, ChannelInstance $ch): array
    {
        /** @var array<string, mixed> $config */
        $config = $ch->config ?? [];
        $phoneNumberId = (string) ($config['phone_number_id'] ?? $ch->external_id);
        $from = $msg->conversation->external_conversation_id ?? '5511999999999';
        $timestamp = (string) now()->getTimestamp();

        return [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => (string) ($config['waba_id'] ?? 'waba_123'),
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => $phoneNumberId,
                                    'phone_number_id' => $phoneNumberId,
                                ],
                                'contacts' => [
                                    [
                                        'profile' => ['name' => 'Bartender User'],
                                        'wa_id' => $from,
                                    ],
                                ],
                                'messages' => [
                                    [
                                        'from' => $from,
                                        'id' => $msg->external_message_id ?? 'wamid.'.uniqid(),
                                        'timestamp' => $timestamp,
                                        'text' => ['body' => $msg->content],
                                        'type' => 'text',
                                    ],
                                ],
                            ],
                            'field' => 'messages',
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
        $phoneNumberId = (string) ($config['phone_number_id'] ?? $ch->external_id);
        $from = $msg->conversation->external_conversation_id ?? '5511999999999';
        $timestamp = (string) now()->getTimestamp();

        return [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => (string) ($config['waba_id'] ?? 'waba_123'),
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => $phoneNumberId,
                                    'phone_number_id' => $phoneNumberId,
                                ],
                                'statuses' => [
                                    [
                                        'id' => $msg->external_message_id ?? 'wamid.'.uniqid(),
                                        'recipient_id' => $from,
                                        'status' => 'delivered',
                                        'timestamp' => $timestamp,
                                        'conversation' => [
                                            'id' => 'conv_'.$msg->conversation_id,
                                            'origin' => ['type' => 'user_initiated'],
                                        ],
                                    ],
                                ],
                            ],
                            'field' => 'messages',
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
        $phoneNumberId = (string) ($config['phone_number_id'] ?? $ch->external_id);
        $from = $msg->conversation->external_conversation_id ?? '5511999999999';
        $timestamp = (string) now()->getTimestamp();

        return [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => (string) ($config['waba_id'] ?? 'waba_123'),
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => $phoneNumberId,
                                    'phone_number_id' => $phoneNumberId,
                                ],
                                'statuses' => [
                                    [
                                        'id' => $msg->external_message_id ?? 'wamid.'.uniqid(),
                                        'recipient_id' => $from,
                                        'status' => 'read',
                                        'timestamp' => $timestamp,
                                    ],
                                ],
                            ],
                            'field' => 'messages',
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
