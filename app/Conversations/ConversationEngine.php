<?php

declare(strict_types=1);

namespace App\Conversations;

use App\Inbound\InboundDispatcher;
use App\Models\ChannelInstance;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\RawPayload;
use App\Models\Scenario;
use App\Personas\PersonaRunner;
use App\Simulators\Contracts\ChannelSimulator;
use App\Simulators\Evolution\EvolutionWhatsappSimulator;
use App\Simulators\Meta\InstagramDirectSimulator;
use App\Simulators\Meta\MessengerSimulator;
use App\Simulators\Meta\WhatsappCloudSimulator;

final class ConversationEngine
{
    public function __construct(
        private readonly PersonaRunner $personaRunner,
        private readonly InboundDispatcher $dispatcher,
    ) {}

    public function start(Scenario $scenario, ChannelInstance $channel): Conversation
    {
        $conversation = new Conversation([
            'scenario_id' => $scenario->id,
            'persona_id' => $scenario->persona_id,
            'channel_instance_id' => $channel->id,
            'external_conversation_id' => $this->generateExternalId($channel),
            'status' => 'active',
            'turn_count' => 0,
            'started_at' => now(),
        ]);
        $conversation->save();

        $this->runTurn($conversation, $channel);

        return $conversation;
    }

    public function runTurn(Conversation $conversation, ChannelInstance $channel): void
    {
        $simulator = $this->resolveSimulator($channel);

        if ($simulator === null) {
            $conversation->update(['status' => 'error', 'error' => 'Unknown channel simulator']);

            return;
        }

        $turn = $this->personaRunner->nextTurn($conversation);

        $message = new Message([
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'role' => 'user',
            'content' => $turn->text,
            'external_message_id' => 'bartender_'.uniqid(),
            'status' => 'sent',
            'sent_at' => now(),
        ]);
        $message->save();

        $payload = $simulator->buildInboundPayload($message, $channel);
        $this->dispatcher->send($payload, $channel, $this->webhookPath($channel));

        RawPayload::create([
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'channel' => $simulator->provider(),
            'payload' => $payload,
        ]);

        $conversation->increment('turn_count');
        $channel->update(['last_post_at' => now()]);

        if ($turn->closeConversation) {
            $conversation->update(['status' => 'completed', 'ended_at' => now()]);
        }
    }

    public function sendAck(Conversation $conversation, ChannelInstance $channel, Message $outboundMessage): void
    {
        $simulator = $this->resolveSimulator($channel);

        if ($simulator === null) {
            return;
        }

        $deliveryPayload = $simulator->buildDeliveryReceipt($outboundMessage, $channel);
        $this->dispatcher->send($deliveryPayload, $channel, $this->webhookPath($channel));

        RawPayload::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'channel' => $simulator->provider(),
            'payload' => $deliveryPayload,
        ]);

        $readPayload = $simulator->buildReadReceipt($outboundMessage, $channel);
        $this->dispatcher->send($readPayload, $channel, $this->webhookPath($channel));

        RawPayload::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'channel' => $simulator->provider(),
            'payload' => $readPayload,
        ]);
    }

    private function resolveSimulator(ChannelInstance $channel): ?ChannelSimulator
    {
        return match ($channel->provider) {
            'meta_whatsapp_cloud' => new WhatsappCloudSimulator,
            'meta_instagram_direct' => new InstagramDirectSimulator,
            'meta_messenger' => new MessengerSimulator,
            'evolution_whatsapp' => new EvolutionWhatsappSimulator,
            default => null,
        };
    }

    private function webhookPath(ChannelInstance $channel): string
    {
        /** @var array<string, mixed> $config */
        $config = $channel->config ?? [];

        return match ($channel->provider) {
            'meta_whatsapp_cloud', 'meta_instagram_direct', 'meta_messenger' => '/webhooks/meta',
            'evolution_whatsapp' => '/webhooks/evolution/'.((string) ($config['instance_name'] ?? $channel->external_id)),
            default => '/webhooks/'.$channel->provider,
        };
    }

    private function generateExternalId(ChannelInstance $channel): string
    {
        return match ($channel->provider) {
            'meta_whatsapp_cloud' => '5511'.random_int(10000000, 99999999),
            'meta_instagram_direct' => 'ig_user_'.uniqid(),
            'meta_messenger' => 'psid_'.uniqid(),
            'evolution_whatsapp' => '5511'.random_int(10000000, 99999999).'@s.whatsapp.net',
            default => 'ext_'.uniqid(),
        };
    }
}
