<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Conversations\ConversationEngine;
use App\Http\Controllers\Controller;
use App\Models\ChannelInstance;
use App\Models\Scenario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ScenarioController extends Controller
{
    public function start(Request $request, Scenario $scenario, ConversationEngine $engine): JsonResponse
    {
        $validated = $request->validate([
            'channel_provider' => ['required', 'string', 'in:meta_whatsapp_cloud,meta_instagram_direct,meta_messenger,evolution_whatsapp'],
        ]);

        $channelProvider = $validated['channel_provider'];

        if ($scenario->channel !== $channelProvider) {
            return response()->json([
                'error' => 'Channel provider does not match scenario channel',
                'scenario_channel' => $scenario->channel,
                'requested_channel' => $channelProvider,
            ], 422);
        }

        $channel = ChannelInstance::where('provider', $channelProvider)->first();

        if ($channel === null) {
            return response()->json([
                'error' => 'No channel instance found for provider',
                'provider' => $channelProvider,
            ], 422);
        }

        $conversation = $engine->start($scenario, $channel);

        return response()->json([
            'conversation_id' => (string) $conversation->id,
            'external_conversation_id' => $conversation->external_conversation_id,
            'status' => $conversation->status,
        ], 201);
    }
}
