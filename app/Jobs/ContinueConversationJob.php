<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Conversations\ConversationEngine;
use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ContinueConversationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $conversationId) {}

    public function handle(ConversationEngine $engine): void
    {
        $conversation = Conversation::with('channelInstance')->find($this->conversationId);

        if ($conversation === null) {
            return;
        }

        if ($conversation->status !== 'active') {
            return;
        }

        if ($conversation->channelInstance === null) {
            return;
        }

        $engine->runTurn($conversation, $conversation->channelInstance);
    }
}
