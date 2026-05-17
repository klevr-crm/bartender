<?php

declare(strict_types=1);

namespace App\Personas;

use App\Models\Conversation;
use App\Models\Persona;
use App\ValueObjects\TurnResult;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;

final class PersonaRunner
{
    public function nextTurn(Conversation $conversation): TurnResult
    {
        /** @var Persona $persona */
        $persona = $conversation->persona;

        if ($conversation->turn_count >= config('bartender.ai.max_turns', 50)) {
            return new TurnResult(
                text: '',
                media: null,
                typingDelayMs: 0,
                closeConversation: true,
            );
        }

        try {
            $history = $this->buildHistory($conversation);

            $response = Prism::text()
                ->using(Provider::from($persona->provider), $persona->model)
                ->withSystemPrompt($persona->system_prompt)
                ->withMessages($history)
                ->withMaxTokens(config('bartender.ai.max_tokens', 400))
                ->asText();

            $text = $response->text;

            $closeConversation = str_contains(strtolower($text), '[end]')
                || str_contains(strtolower($text), 'obrigado');

            return new TurnResult(
                text: $text,
                media: null,
                typingDelayMs: 500,
                closeConversation: $closeConversation,
            );
        } catch (\Throwable $e) {
            Log::error('PersonaRunner failed', ['error' => $e->getMessage(), 'conversation_id' => $conversation->id]);

            return new TurnResult(
                text: 'Desculpe, tive um problema.',
                media: null,
                typingDelayMs: 0,
                closeConversation: false,
            );
        }
    }

    /**
     * @return array<int, UserMessage|AssistantMessage>
     */
    private function buildHistory(Conversation $conversation): array
    {
        $messages = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at')
            ->take(20)
            ->get();

        return $messages->map(function ($msg): UserMessage|AssistantMessage {
            if ($msg->role === 'user') {
                return new UserMessage($msg->content ?? '');
            }

            return new AssistantMessage($msg->content ?? '');
        })->values()->all();
    }
}
