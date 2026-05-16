<?php

declare(strict_types=1);

namespace App\Outbound;

use App\Conversations\ConversationEngine;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\RawPayload;
use App\Simulators\Contracts\ChannelSimulator;
use App\Simulators\Evolution\EvolutionWhatsappSimulator;
use App\Simulators\Meta\InstagramDirectSimulator;
use App\Simulators\Meta\MessengerSimulator;
use App\Simulators\Meta\WhatsappCloudSimulator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

final class ChannelOutboundConsumer extends Command
{
    protected $signature = 'bartender:consume-outbound';

    protected $description = 'Consume outbound messages from RabbitMQ channel-outbound exchange';

    public function handle(): int
    {
        $dsn = config('bartender.rabbitmq.dsn');
        $exchange = config('bartender.rabbitmq.exchange');
        $queue = config('bartender.rabbitmq.queue');

        $parsed = parse_url((string) $dsn);
        $host = $parsed['host'] ?? 'localhost';
        $port = (int) ($parsed['port'] ?? 5672);
        $user = $parsed['user'] ?? 'guest';
        $pass = $parsed['pass'] ?? 'guest';
        $vhost = $parsed['path'] ?? '/';
        $vhost = $vhost === '' ? '/' : $vhost;

        $connection = new AMQPStreamConnection($host, $port, $user, $pass, $vhost);
        $channel = $connection->channel();

        $channel->queue_declare($queue, false, true, false, false);
        $channel->exchange_declare($exchange, 'topic', false, true, false);
        $channel->queue_bind($queue, $exchange, '#');

        $this->info("Waiting for messages on {$queue}...");

        $callback = function (AMQPMessage $msg): void {
            try {
                $data = json_decode($msg->getBody(), true);
                if (! is_array($data)) {
                    Log::warning('ChannelOutboundConsumer: invalid JSON', ['body' => $msg->getBody()]);
                    $msg->nack(false);

                    return;
                }

                $this->process($data);
                $msg->ack();
            } catch (\Throwable $e) {
                Log::error('ChannelOutboundConsumer: processing failed', ['error' => $e->getMessage()]);
                $msg->nack(false, false);
            }
        };

        $channel->basic_consume($queue, '', false, false, false, false, $callback);

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $data */
    private function process(array $data): void
    {
        $provider = $data['provider'] ?? $data['channel'] ?? '';
        $simulator = $this->resolveSimulator((string) $provider);

        if ($simulator === null) {
            Log::warning('ChannelOutboundConsumer: unknown provider', ['provider' => $provider]);

            return;
        }

        $normalized = $simulator->parseOutbound($data);

        $conversation = Conversation::with('channelInstance')
            ->where('external_conversation_id', $normalized->externalConversationId)
            ->where('status', 'active')
            ->first();

        if ($conversation === null) {
            Log::info('ChannelOutboundConsumer: no active conversation', [
                'external_conversation_id' => $normalized->externalConversationId,
            ]);

            return;
        }

        $message = new Message([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'role' => 'assistant',
            'content' => $normalized->content,
            'media' => $normalized->media,
            'external_message_id' => $normalized->externalMessageId,
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
        $message->save();

        RawPayload::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'channel' => $simulator->provider(),
            'payload' => $data,
        ]);

        if ($conversation->channelInstance !== null) {
            $engine = app(ConversationEngine::class);
            $engine->sendAck($conversation, $conversation->channelInstance, $message);
        }
    }

    private function resolveSimulator(string $provider): ?ChannelSimulator
    {
        return match ($provider) {
            'meta', 'meta_whatsapp_cloud' => new WhatsappCloudSimulator,
            'meta_instagram_direct' => new InstagramDirectSimulator,
            'meta_messenger' => new MessengerSimulator,
            'evolution', 'evolution_whatsapp', 'whatsapp_baileys' => new EvolutionWhatsappSimulator,
            default => null,
        };
    }
}
