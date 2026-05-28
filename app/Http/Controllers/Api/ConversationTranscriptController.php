<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;

final class ConversationTranscriptController extends Controller
{
    public function __invoke(Conversation $conversation): JsonResponse
    {
        $conversation->load(['scenario', 'persona', 'channelInstance', 'messages']);

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'external_conversation_id' => $conversation->external_conversation_id,
                'status' => $conversation->status,
                'end_reason' => $conversation->end_reason,
                'turn_count' => $conversation->turn_count,
                'started_at' => $conversation->started_at?->toIso8601String(),
                'ended_at' => $conversation->ended_at?->toIso8601String(),
                'error' => $conversation->error,
                'scenario' => [
                    'slug' => $conversation->scenario?->slug,
                    'name' => $conversation->scenario?->name,
                ],
                'persona' => [
                    'slug' => $conversation->persona?->slug,
                    'name' => $conversation->persona?->name,
                ],
                'channel' => [
                    'provider' => $conversation->channelInstance?->provider,
                    'external_id' => $conversation->channelInstance?->external_id,
                ],
            ],
            'messages' => $conversation->messages->map(fn (Message $message): array => [
                'direction' => $message->direction,
                'role' => $message->role,
                'content' => $message->content,
                'status' => $message->status,
                'intent' => $message->metadata['intent'] ?? null,
                'satisfaction' => $message->metadata['satisfaction'] ?? null,
                'sent_at' => $message->sent_at?->toIso8601String(),
                'delivered_at' => $message->delivered_at?->toIso8601String(),
                'read_at' => $message->read_at?->toIso8601String(),
                'created_at' => $message->created_at?->toIso8601String(),
            ])->values()->all(),
        ]);
    }
}
