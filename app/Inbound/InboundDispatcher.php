<?php

declare(strict_types=1);

namespace App\Inbound;

use App\Models\ChannelInstance;
use App\Payloads\MetaInstagramDirectPayload;
use App\Payloads\MetaMessengerPayload;
use App\Payloads\MetaWhatsappCloudPayload;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class InboundDispatcher
{
    /** @param array<string, mixed> $payload */
    public function send(array $payload, ChannelInstance $ch, string $path): void
    {
        $mode = config('bartender.inbound_mode', 'http');

        if ($mode === 'http') {
            $this->sendHttp($payload, $ch, $path);
        } else {
            $this->sendMq($payload, $ch);
        }
    }

    /** @param array<string, mixed> $payload */
    private function sendHttp(array $payload, ChannelInstance $ch, string $path): void
    {
        $url = rtrim((string) config('bartender.gateway_url'), '/').$path;
        $body = json_encode($payload);
        $secret = (string) config('bartender.meta.app_secret', '');

        $headers = [
            'Content-Type' => 'application/json',
        ];

        if ($secret !== '' && in_array($ch->provider, ['meta_whatsapp_cloud', 'meta_instagram_direct', 'meta_messenger'], true)) {
            $headers['X-Hub-Signature-256'] = match ($ch->provider) {
                'meta_whatsapp_cloud' => MetaWhatsappCloudPayload::generateHmac($body, $secret),
                'meta_instagram_direct' => MetaInstagramDirectPayload::generateHmac($body, $secret),
                'meta_messenger' => MetaMessengerPayload::generateHmac($body, $secret),
            };
        }

        $response = Http::withHeaders($headers)->post($url, $payload);

        if (! $response->successful()) {
            Log::warning('InboundDispatcher HTTP failed', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }

    /** @param array<string, mixed> $payload */
    private function sendMq(array $payload, ChannelInstance $ch): void
    {
        Log::info('InboundDispatcher MQ mode not yet implemented', [
            'provider' => $ch->provider,
            'payload_keys' => array_keys($payload),
        ]);
        // TODO: implement RabbitMQ publish to API inbound queue
    }
}
