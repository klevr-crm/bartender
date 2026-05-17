<?php

declare(strict_types=1);

namespace App\Payloads;

use App\Models\ChannelInstance;
use App\Models\Message;

final class EvolutionWhatsappPayload
{
    /** @return array<string, mixed> */
    public static function inboundText(Message $msg, ChannelInstance $ch): array
    {
        /** @var array<string, mixed> $config */
        $config = $ch->config ?? [];
        $instanceName = (string) ($config['instance_name'] ?? $ch->external_id);
        $remoteJid = $msg->conversation->external_conversation_id ?? '5511999999999@s.whatsapp.net';
        $timestamp = now()->getTimestamp();

        return [
            'event' => 'messages.upsert',
            'instance' => $instanceName,
            'data' => [
                'key' => [
                    'remoteJid' => $remoteJid,
                    'fromMe' => false,
                    'id' => $msg->external_message_id ?? 'evmid.'.uniqid(),
                ],
                'pushName' => 'Bartender User',
                'message' => [
                    'conversation' => $msg->content,
                ],
                'messageType' => 'conversation',
                'messageTimestamp' => $timestamp,
                'source' => 'web',
                'status' => 'PENDING',
            ],
            'destination' => $instanceName,
            'date_time' => now()->toIso8601String(),
            'sender' => $remoteJid,
            'server_url' => 'https://evolution.example.com',
            'apikey' => config('bartender.evolution.api_key'),
        ];
    }

    /** @return array<string, mixed> */
    public static function deliveryReceipt(Message $msg, ChannelInstance $ch): array
    {
        /** @var array<string, mixed> $config */
        $config = $ch->config ?? [];
        $instanceName = (string) ($config['instance_name'] ?? $ch->external_id);
        $remoteJid = $msg->conversation->external_conversation_id ?? '5511999999999@s.whatsapp.net';
        $timestamp = now()->getTimestamp();

        return [
            'event' => 'messages.update',
            'instance' => $instanceName,
            'data' => [
                'key' => [
                    'remoteJid' => $remoteJid,
                    'fromMe' => true,
                    'id' => $msg->external_message_id ?? 'evmid.'.uniqid(),
                ],
                'status' => 'DELIVERY_ACK',
                'messageTimestamp' => $timestamp,
            ],
            'destination' => $instanceName,
            'date_time' => now()->toIso8601String(),
            'sender' => $remoteJid,
            'server_url' => 'https://evolution.example.com',
            'apikey' => config('bartender.evolution.api_key'),
        ];
    }

    /** @return array<string, mixed> */
    public static function readReceipt(Message $msg, ChannelInstance $ch): array
    {
        /** @var array<string, mixed> $config */
        $config = $ch->config ?? [];
        $instanceName = (string) ($config['instance_name'] ?? $ch->external_id);
        $remoteJid = $msg->conversation->external_conversation_id ?? '5511999999999@s.whatsapp.net';
        $timestamp = now()->getTimestamp();

        return [
            'event' => 'messages.update',
            'instance' => $instanceName,
            'data' => [
                'key' => [
                    'remoteJid' => $remoteJid,
                    'fromMe' => true,
                    'id' => $msg->external_message_id ?? 'evmid.'.uniqid(),
                ],
                'status' => 'READ',
                'messageTimestamp' => $timestamp,
            ],
            'destination' => $instanceName,
            'date_time' => now()->toIso8601String(),
            'sender' => $remoteJid,
            'server_url' => 'https://evolution.example.com',
            'apikey' => config('bartender.evolution.api_key'),
        ];
    }
}
