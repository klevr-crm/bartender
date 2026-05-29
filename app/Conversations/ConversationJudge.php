<?php

declare(strict_types=1);

namespace App\Conversations;

use App\Models\Conversation;
use App\Models\ConversationEvaluation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Throwable;

final class ConversationJudge
{
    public function judge(Conversation $conversation): ConversationEvaluation
    {
        $conversation->loadMissing(['messages', 'scenario', 'persona']);

        try {
            $schema = new ObjectSchema(
                name: 'conversation_evaluation',
                description: 'Avaliacao da qualidade do atendimento na conversa',
                properties: [
                    new NumberSchema(name: 'resolution', description: 'O pedido/objetivo do cliente foi resolvido? 0-10', minimum: 0, maximum: 10),
                    new NumberSchema(name: 'tone', description: 'Tom adequado, cordial e profissional? 0-10', minimum: 0, maximum: 10),
                    new NumberSchema(name: 'correct_actions', description: 'O atendente tomou acoes corretas e deu informacoes corretas? 0-10', minimum: 0, maximum: 10),
                    new NumberSchema(name: 'hallucination', description: '10 = nenhuma alucinacao/invencao; 0 = muita', minimum: 0, maximum: 10),
                    new NumberSchema(name: 'constraints', description: 'Respeitou restricoes/politicas (nao prometeu o impossivel)? 0-10', minimum: 0, maximum: 10),
                    new ArraySchema(name: 'findings', description: 'Observacoes textuais em portugues (pontos fortes e fracos)', items: new StringSchema(name: 'finding', description: 'uma observacao')),
                    new StringSchema(name: 'summary', description: 'Resumo curto da avaliacao em portugues'),
                ],
                requiredFields: ['resolution', 'tone', 'correct_actions', 'hallucination', 'constraints', 'findings', 'summary'],
            );

            $systemPrompt = $this->buildSystemPrompt();
            $transcriptPrompt = $this->buildTranscriptPrompt($conversation);

            $provider = (string) config('bartender.judge.provider', 'openai');
            $model = (string) config('bartender.judge.model', 'gpt-4o-mini');

            $response = Prism::structured()
                ->using(Provider::from($provider), $model)
                ->withSystemPrompt($systemPrompt)
                ->withPrompt($transcriptPrompt)
                ->withMaxTokens((int) config('bartender.judge.max_tokens', 800))
                ->withSchema($schema)
                ->asStructured();

            /** @var array<string, mixed> $data */
            $data = $response->structured ?? [];

            $scores = [
                'resolution' => (int) ($data['resolution'] ?? 0),
                'tone' => (int) ($data['tone'] ?? 0),
                'correct_actions' => (int) ($data['correct_actions'] ?? 0),
                'hallucination' => (int) ($data['hallucination'] ?? 0),
                'constraints' => (int) ($data['constraints'] ?? 0),
            ];

            /** @var array<int, string> $findings */
            $findings = $data['findings'] ?? [];

            $overallScore = (int) round(array_sum($scores) / count($scores));
            $verdict = $this->resolveVerdict($overallScore);

            return ConversationEvaluation::create([
                'conversation_id' => $conversation->id,
                'scores' => $scores,
                'overall_score' => $overallScore,
                'findings' => $findings,
                'verdict' => $verdict,
                'judged_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('ConversationJudge failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return ConversationEvaluation::create([
                'conversation_id' => $conversation->id,
                'scores' => [],
                'overall_score' => 0,
                'findings' => ['Judge LLM falhou: '.$e->getMessage()],
                'verdict' => 'error',
                'judged_at' => now(),
            ]);
        }
    }

    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
Voce e um avaliador especializado em qualidade de atendimento ao cliente.
Sua tarefa e avaliar a qualidade do ATENDENTE (agente de IA do CRM) com base no transcript completo da conversa.

Avalie nas seguintes dimensoes (0 a 10):
- resolution: O pedido/objetivo do cliente foi resolvido?
- tone: O tom foi adequado, cordial e profissional?
- correct_actions: O atendente tomou acoes corretas e deu informacoes corretas?
- hallucination: 10 = nenhuma alucinacao/invencao; 0 = muita.
- constraints: O atendente respeitou restricoes e politicas (nao prometeu o impossivel)?

Retorne tambem:
- findings: lista de observacoes textuais em portugues (pontos fortes e fracos).
- summary: resumo curto da avaliacao em portugues.
PROMPT;
    }

    private function buildTranscriptPrompt(Conversation $conversation): string
    {
        $scenario = $conversation->scenario;
        $objective = $scenario->script ?? $scenario->name ?? 'Objetivo nao informado';

        $lines = [];
        $lines[] = 'Objetivo do cenario: '.$objective;
        $lines[] = '';
        $lines[] = 'Transcript:';

        /** @var Collection<int, Message> $messages */
        $messages = $conversation->messages;

        foreach ($messages as $message) {
            $speaker = $message->direction === 'inbound' ? 'Cliente' : 'Atendente';
            $lines[] = $speaker.': '.($message->content ?? '');
        }

        return implode("\n", $lines);
    }

    private function resolveVerdict(int $overallScore): string
    {
        if ($overallScore >= 7) {
            return 'pass';
        }

        if ($overallScore >= 4) {
            return 'warning';
        }

        return 'fail';
    }
}
