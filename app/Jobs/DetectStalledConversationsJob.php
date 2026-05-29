<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class DetectStalledConversationsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $timeoutMs = (int) config('bartender.timing.inactivity_timeout_ms', 120_000);
        $threshold = now()->subMilliseconds($timeoutMs);

        $candidates = Conversation::where('status', 'active')->get();

        foreach ($candidates as $conversation) {
            $lastMessage = $conversation->messages()->latest('created_at')->first();

            if ($lastMessage === null) {
                continue;
            }

            if ($lastMessage->direction !== 'inbound') {
                continue;
            }

            if ($lastMessage->created_at === null || $lastMessage->created_at->gte($threshold)) {
                continue;
            }

            $conversation->update([
                'status' => 'stalled',
                'ended_at' => now(),
                'end_reason' => 'stalled',
                'error' => 'CRM inactivity timeout',
            ]);

            JudgeConversationJob::dispatch($conversation->id);

            Log::warning('Conversation stalled (CRM inactivity)', ['conversation_id' => $conversation->id]);
        }
    }
}
