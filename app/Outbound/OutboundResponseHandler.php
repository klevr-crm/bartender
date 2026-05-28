<?php

declare(strict_types=1);

namespace App\Outbound;

use App\Conversations\ConversationEngine;
use App\Jobs\ContinueConversationJob;
use App\Models\ChannelInstance;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\RawPayload;
use App\Personas\HumanTimingService;
use App\Simulators\Contracts\ChannelSimulator;
use App\ValueObjects\NormalizedOutbound;
use Closure;
use Illuminate\Support\Facades\Log;

final class OutboundResponseHandler
{
    /** @var Closure(array<string, mixed>): Message */
    private readonly Closure $createMessage;

    /** @var Closure(array<string, mixed>): RawPayload */
    private readonly Closure $createRawPayload;

    /** @var Closure(Conversation): void */
    private readonly Closure $refreshConversation;

    /** @var Closure(Conversation, ChannelInstance, Message): void */
    private readonly Closure $sendAck;

    public function __construct(
        private readonly ConversationEngine $engine,
        private readonly HumanTimingService $timingService,
        ?Closure $createMessage = null,
        ?Closure $createRawPayload = null,
        ?Closure $refreshConversation = null,
        ?Closure $sendAck = null,
    ) {
        $this->createMessage = $createMessage ?? function (array $attributes): Message {
            $message = new Message($attributes);
            $message->save();

            return $message;
        };
        $this->createRawPayload = $createRawPayload ?? fn (array $attributes): RawPayload => RawPayload::create($attributes);
        $this->refreshConversation = $refreshConversation ?? function (Conversation $c): void {
            $c->refresh();
        };
        $this->sendAck = $sendAck ?? function (Conversation $conv, ChannelInstance $ch, Message $msg): void {
            $this->engine->sendAck($conv, $ch, $msg);
        };
    }

    public function handle(Conversation $conversation, NormalizedOutbound $normalized, ChannelSimulator $simulator): void
    {
        ($this->createMessage)([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'role' => 'assistant',
            'content' => $normalized->content,
            'media' => $normalized->media,
            'external_message_id' => $normalized->externalMessageId,
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        ($this->createRawPayload)([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'channel' => $simulator->provider(),
            'payload' => $normalized->metadata ?? [],
        ]);

        if ($conversation->channelInstance !== null) {
            ($this->sendAck)($conversation, $conversation->channelInstance, new Message([
                'conversation_id' => $conversation->id,
                'direction' => 'outbound',
                'role' => 'assistant',
                'content' => $normalized->content,
                'external_message_id' => $normalized->externalMessageId,
                'status' => 'delivered',
                'delivered_at' => now(),
            ]));
        }

        ($this->refreshConversation)($conversation);

        if ($conversation->status !== 'active') {
            return;
        }

        $maxTurns = (int) config('bartender.ai.max_turns', 50);

        if ($conversation->turn_count >= $maxTurns) {
            return;
        }

        $delayMs = $this->timingService->computeDelayMs($normalized->content);

        Log::info('Scheduling next turn', [
            'conversation_id' => $conversation->id,
            'delay_ms' => $delayMs,
        ]);

        ContinueConversationJob::dispatch($conversation->id)
            ->delay(now()->addMilliseconds($delayMs));
    }
}
