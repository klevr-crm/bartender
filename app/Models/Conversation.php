<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;

/**
 * @property int $id
 * @property int|null $scenario_id
 * @property int|null $persona_id
 * @property int|null $channel_instance_id
 * @property string|null $external_conversation_id
 * @property string $status
 * @property int $turn_count
 * @property Carbon|null $started_at
 * @property Carbon|null $ended_at
 * @property string|null $error
 * @property string|null $end_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(ConversationFactory::class)]
final class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    protected $fillable = [
        'scenario_id',
        'persona_id',
        'channel_instance_id',
        'external_conversation_id',
        'status',
        'turn_count',
        'started_at',
        'ended_at',
        'error',
        'end_reason',
    ];

    protected function casts(): array
    {
        return [
            'turn_count' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Scenario, $this> */
    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    /** @return BelongsTo<Persona, $this> */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    /** @return BelongsTo<ChannelInstance, $this> */
    public function channelInstance(): BelongsTo
    {
        return $this->belongsTo(ChannelInstance::class);
    }

    /** @return HasMany<Message, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    /** @return HasMany<RawPayload, $this> */
    public function rawPayloads(): HasMany
    {
        return $this->hasMany(RawPayload::class);
    }

    /** @return HasMany<ConversationEvaluation, $this> */
    public function evaluations(): HasMany
    {
        return $this->hasMany(ConversationEvaluation::class);
    }

    /** @return HasOne<ConversationEvaluation, $this> */
    public function evaluation(): HasOne
    {
        return $this->hasOne(ConversationEvaluation::class);
    }

    /**
     * @return array<int, UserMessage|AssistantMessage>
     */
    public function toPrismHistory(): array
    {
        /** @var Collection<int, Message> $messages */
        $messages = $this->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at')
            ->take(20)
            ->get();

        return $messages->map(function (Message $msg): UserMessage|AssistantMessage {
            if ($msg->role === 'user') {
                return new UserMessage($msg->content ?? '');
            }

            return new AssistantMessage($msg->content ?? '');
        })->values()->all();
    }
}
