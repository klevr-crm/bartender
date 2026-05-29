<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ConversationEvaluation;
use Illuminate\Console\Command;

final class ExportEvals extends Command
{
    protected $signature = 'bartender:export-evals {--output= : Caminho do arquivo JSON de saida}';

    protected $description = 'Exporta conversas avaliadas como dataset JSON para o eval-harness do CRM';

    public function handle(): int
    {
        $outputPath = $this->option('output') ?? storage_path('app/evals-export.json');

        $evaluations = ConversationEvaluation::with([
            'conversation.messages',
            'conversation.scenario',
        ])->get();

        $dataset = [];

        foreach ($evaluations as $evaluation) {
            $conversation = $evaluation->conversation;

            if ($conversation === null) {
                continue;
            }

            $scenario = $conversation->scenario;

            $transcript = [];
            foreach ($conversation->messages as $message) {
                $transcript[] = [
                    'direction' => $message->direction,
                    'role' => $message->role,
                    'content' => $message->content,
                    'intent' => $message->metadata['intent'] ?? null,
                    'satisfaction' => $message->metadata['satisfaction'] ?? null,
                ];
            }

            $dataset[] = [
                'scenario' => [
                    'slug' => $scenario?->slug,
                    'name' => $scenario?->name,
                    'script' => $scenario?->script,
                ],
                'transcript' => $transcript,
                'evaluation' => [
                    'scores' => $evaluation->scores,
                    'overall_score' => $evaluation->overall_score,
                    'verdict' => $evaluation->verdict,
                    'findings' => $evaluation->findings,
                ],
            ];
        }

        $json = json_encode($dataset, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            $this->error('Falha ao codificar JSON.');

            return self::FAILURE;
        }

        file_put_contents($outputPath, $json);

        $this->info("Exportadas {$evaluations->count()} avaliacoes para {$outputPath}");

        return self::SUCCESS;
    }
}
