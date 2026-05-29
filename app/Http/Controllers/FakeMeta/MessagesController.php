<?php

declare(strict_types=1);

namespace App\Http\Controllers\FakeMeta;

use App\Http\Controllers\Controller;
use App\Models\ChannelInstance;
use App\Models\Conversation;
use App\Outbound\OutboundResponseHandler;
use App\Simulators\Meta\WhatsappCloudSimulator;
use App\ValueObjects\NormalizedOutbound;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MessagesController extends Controller
{
    public function __invoke(Request $request, string $phoneId): JsonResponse
    {
        $body = $request->all();
        $to = (string) ($body['to'] ?? '');
        $type = (string) ($body['type'] ?? 'text');

        $externalMessageId = 'wamid.'.uniqid();

        $conversation = $this->resolveConversation($phoneId);

        if ($conversation !== null) {
            $normalized = $this->buildNormalizedOutbound($body, $type, $externalMessageId);
            $simulator = app(WhatsappCloudSimulator::class);
            $handler = app(OutboundResponseHandler::class);
            $handler->handle($conversation, $normalized, $simulator);
        }

        return response()->json([
            'messaging_product' => 'whatsapp',
            'contacts' => [
                [
                    'input' => $to,
                    'wa_id' => $to,
                ],
            ],
            'messages' => [
                [
                    'id' => $externalMessageId,
                ],
            ],
        ]);
    }

    private function resolveConversation(string $phoneId): ?Conversation
    {
        $channel = ChannelInstance::query()
            ->where('external_id', $phoneId)
            ->orWhereRaw("config->>'phone_number_id' = ?", [$phoneId])
            ->first();

        if ($channel === null) {
            return null;
        }

        return Conversation::query()
            ->where('channel_instance_id', $channel->id)
            ->where('status', 'active')
            ->latest('started_at')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function buildNormalizedOutbound(array $body, string $type, string $externalMessageId): NormalizedOutbound
    {
        $to = (string) ($body['to'] ?? '');

        $content = '';
        $media = null;

        if ($type === 'text') {
            $content = (string) ($body['text']['body'] ?? '');
        } elseif (in_array($type, ['image', 'audio', 'video', 'document'], true)) {
            $media = $body[$type] ?? null;
            $content = (string) ($media['caption'] ?? '');
        } elseif ($type === 'interactive') {
            $content = (string) ($body['interactive']['body']['text'] ?? '');
        } elseif ($type === 'template') {
            $content = (string) ($body['template']['name'] ?? '');
        } else {
            $content = (string) ($body['body'] ?? '');
        }

        return new NormalizedOutbound(
            externalMessageId: $externalMessageId,
            externalConversationId: $to,
            provider: 'meta',
            channelType: 'whatsapp_cloud',
            content: $content,
            media: is_array($media) ? $media : null,
            metadata: $body,
        );
    }
}
