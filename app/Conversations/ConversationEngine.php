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
use Closure;

final class ConversationEngine
{
    /** @var Closure(array<string, mixed>): Message */
    private readonly Closure $createMessage;

    /** @var Closure(array<string, mixed>): RawPayload */
    private readonly Closure $createRawPayload;

    public function __construct(
        private readonly PersonaRunner $personaRunner,
        private readonly InboundDispatcher $dispatcher,
        ?Closure $createMessage = null,
        ?Closure $createRawPayload = null,
    ) {
        $this->createMessage = $createMessage ?? function (array $attributes): Message {
            $message = new Message($attributes);
            $message->save();

            return $message;
        };
        $this->createRawPayload = $createRawPayload ?? fn (array $attributes): RawPayload => RawPayload::create($attributes);
    }

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

        $metadata = [];
        if ($turn->intent !== null) {
            $metadata['intent'] = $turn->intent;
        }
        if ($turn->satisfaction !== null) {
            $metadata['satisfaction'] = $turn->satisfaction;
        }

        ($this->createMessage)([
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'role' => 'user',
            'content' => $turn->text,
            'metadata' => $metadata === [] ? null : $metadata,
            'external_message_id' => 'bartender_'.uniqid(),
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $inboundMessage = new Message([
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'role' => 'user',
            'content' => $turn->text,
        ]);
        $inboundMessage->setRelation('conversation', $conversation);
        $payload = $simulator->buildInboundPayload($inboundMessage, $channel);
        $this->dispatcher->send($payload, $channel, $this->webhookPath($channel));

        ($this->createRawPayload)([
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'channel' => $simulator->provider(),
            'payload' => $payload,
        ]);

        $conversation->turn_count++;
        $channel->last_post_at = now()->toDateTimeString();

        if ($turn->closeConversation) {
            $conversation->status = 'completed';
            $conversation->ended_at = now()->toDateTimeString();
            $conversation->end_reason = $turn->endReason ?? 'resolved';

            return;
        }

        $maxTurns = (int) config('bartender.ai.max_turns', 50);

        if ($conversation->turn_count >= $maxTurns) {
            $conversation->status = 'completed';
            $conversation->ended_at = now()->toDateTimeString();
            $conversation->end_reason = 'max_turns';
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

        ($this->createRawPayload)([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'channel' => $simulator->provider(),
            'payload' => $deliveryPayload,
        ]);

        $readPayload = $simulator->buildReadReceipt($outboundMessage, $channel);
        $this->dispatcher->send($readPayload, $channel, $this->webhookPath($channel));

        ($this->createRawPayload)([
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
