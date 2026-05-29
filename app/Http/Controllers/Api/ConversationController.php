<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;

final class ConversationController extends Controller
{
    public function show(Conversation $conversation): JsonResponse
    {
        $conversation->load(['messages', 'rawPayloads', 'scenario', 'persona', 'channelInstance']);

        $turns = $conversation->messages->map(function ($message) {
            return [
                'id' => $message->id,
                'direction' => $message->direction,
                'role' => $message->role,
                'content' => $message->content,
                'status' => $message->status,
                'external_message_id' => $message->external_message_id,
                'sent_at' => $message->sent_at?->toIso8601String(),
                'delivered_at' => $message->delivered_at?->toIso8601String(),
                'read_at' => $message->read_at?->toIso8601String(),
                'created_at' => $message->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'id' => $conversation->id,
            'scenario_id' => $conversation->scenario_id,
            'external_conversation_id' => $conversation->external_conversation_id,
            'status' => $conversation->status,
            'turn_count' => $conversation->turn_count,
            'started_at' => $conversation->started_at?->toIso8601String(),
            'ended_at' => $conversation->ended_at?->toIso8601String(),
            'error' => $conversation->error,
            'turns' => $turns,
        ]);
    }
}
