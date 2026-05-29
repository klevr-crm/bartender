<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Conversations\ConversationJudge;
use App\Models\Conversation;
use App\Models\ConversationEvaluation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class JudgeConversationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $conversationId) {}

    public function handle(ConversationJudge $judge): void
    {
        if (! (bool) config('bartender.judge.enabled', true)) {
            return;
        }

        $conversation = Conversation::find($this->conversationId);

        if ($conversation === null) {
            return;
        }

        if (! in_array($conversation->status, ['completed', 'stalled', 'error'], true)) {
            return;
        }

        if (ConversationEvaluation::where('conversation_id', $conversation->id)->exists()) {
            return;
        }

        $judge->judge($conversation);
    }
}
