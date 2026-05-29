<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ConversationEvaluationFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $conversation_id
 * @property array<string, mixed> $scores
 * @property int|null $overall_score
 * @property array<int, string> $findings
 * @property string $verdict
 * @property Carbon|null $judged_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(ConversationEvaluationFactory::class)]
final class ConversationEvaluation extends Model
{
    /** @use HasFactory<ConversationEvaluationFactory> */
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'scores',
        'overall_score',
        'findings',
        'verdict',
        'judged_at',
    ];

    protected function casts(): array
    {
        return [
            'scores' => 'array',
            'findings' => 'array',
            'overall_score' => 'integer',
            'judged_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Conversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
