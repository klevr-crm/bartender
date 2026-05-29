<?php

declare(strict_types=1);

namespace App\Personas;

use App\Models\Conversation;
use App\Models\Persona;
use App\ValueObjects\TurnResult;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\BooleanSchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
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
            return $this->runStructured($conversation, $persona);
        } catch (\Throwable $structuredError) {
            Log::warning('PersonaRunner structured output failed, falling back to text', [
                'error' => $structuredError->getMessage(),
                'conversation_id' => $conversation->id,
            ]);

            try {
                return $this->runTextFallback($conversation, $persona);
            } catch (\Throwable $textError) {
                Log::error('PersonaRunner fallback also failed', [
                    'error' => $textError->getMessage(),
                    'conversation_id' => $conversation->id,
                ]);

                return new TurnResult(
                    text: 'Desculpe, tive um problema.',
                    media: null,
                    typingDelayMs: 0,
                    closeConversation: false,
                );
            }
        }
    }

    private function runStructured(Conversation $conversation, Persona $persona): TurnResult
    {
        $history = $this->buildHistory($conversation);
        $systemPrompt = $this->buildSystemPrompt($conversation, $persona);

        $schema = new ObjectSchema(
            name: 'persona_response',
            description: 'Resposta estruturada da persona simulando um cliente no WhatsApp',
            properties: [
                new StringSchema(
                    name: 'message',
                    description: 'Texto da mensagem que o cliente enviaria no WhatsApp. Curto, natural, em português do Brasil.',
                ),
                new StringSchema(
                    name: 'intent',
                    description: 'Intenção do cliente neste turno (ex: ask_pricing, complain, thank_and_close, ask_support, greeting)',
                ),
                new BooleanSchema(
                    name: 'should_close',
                    description: 'true quando o objetivo do cenário foi atingido (ex: conseguiu o preço, problema resolvido) ou quando desistir por frustração',
                ),
                new NumberSchema(
                    name: 'satisfaction',
                    description: 'Nível de satisfação do cliente de 1 a 5',
                    minimum: 1,
                    maximum: 5,
                ),
                new StringSchema(
                    name: 'reason',
                    description: 'Motivo curto do fechamento quando should_close=true; vazio caso contrário',
                ),
            ],
            requiredFields: ['message', 'intent', 'should_close', 'satisfaction', 'reason'],
        );

        $response = Prism::structured()
            ->using(Provider::from($persona->provider), $persona->model)
            ->withSystemPrompt($systemPrompt)
            ->withMessages($history)
            ->withMaxTokens(config('bartender.ai.max_tokens', 400))
            ->withSchema($schema)
            ->asStructured();

        /** @var array<string, mixed> $structured */
        $structured = $response->structured ?? [];

        $message = (string) ($structured['message'] ?? '');

        if ($message === '') {
            throw new \RuntimeException('Structured output returned empty message');
        }

        $intent = (string) ($structured['intent'] ?? '');
        $shouldClose = (bool) ($structured['should_close'] ?? false);
        $satisfaction = (int) ($structured['satisfaction'] ?? 3);
        $reason = (string) ($structured['reason'] ?? '');

        $endReason = null;
        if ($shouldClose) {
            $endReason = $satisfaction >= 3 ? 'resolved' : 'abandoned';
        }

        return new TurnResult(
            text: $message,
            media: null,
            typingDelayMs: 500,
            closeConversation: $shouldClose,
            intent: $intent !== '' ? $intent : null,
            satisfaction: $satisfaction,
            endReason: $endReason,
        );
    }

    private function runTextFallback(Conversation $conversation, Persona $persona): TurnResult
    {
        $history = $this->buildHistory($conversation);
        $systemPrompt = $this->buildSystemPrompt($conversation, $persona);

        $fallbackProvider = (string) config('bartender.ai.fallback_provider', 'anthropic');
        $fallbackModel = (string) config('bartender.ai.fallback_model', $persona->model);

        $response = Prism::text()
            ->using(Provider::from($fallbackProvider), $fallbackModel)
            ->withSystemPrompt($systemPrompt)
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
    }

    /**
     * @return array<int, UserMessage|AssistantMessage>
     */
    private function buildHistory(Conversation $conversation): array
    {
        if ($conversation->relationLoaded('messages')) {
            $messages = $conversation->getRelation('messages')
                ->whereIn('role', ['user', 'assistant'])
                ->sortBy('created_at')
                ->take(20)
                ->values();
        } else {
            $messages = $conversation->messages()
                ->whereIn('role', ['user', 'assistant'])
                ->orderBy('created_at')
                ->take(20)
                ->get();
        }

        return $messages->map(function ($msg): UserMessage|AssistantMessage {
            if ($msg->role === 'user') {
                return new UserMessage($msg->content ?? '');
            }

            return new AssistantMessage($msg->content ?? '');
        })->values()->all();
    }

    private function buildSystemPrompt(Conversation $conversation, Persona $persona): string
    {
        $base = $persona->system_prompt;

        $scenario = $conversation->relationLoaded('scenario')
            ? $conversation->getRelation('scenario')
            : null;

        if ($scenario !== null && ! empty($scenario->script)) {
            $base .= "\n\nObjetivo desta conversa: ".$scenario->script;
        }

        $base .= <<<'JSON_INSTRUCTIONS'

Você deve responder como um cliente real no WhatsApp. Mensagens curtas, naturais, em português do Brasil. Reaja à última mensagem do atendente.
Retorne APENAS o JSON estruturado solicitado, sem markdown ou texto extra.
JSON_INSTRUCTIONS;

        return $base;
    }
}
