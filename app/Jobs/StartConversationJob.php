<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Conversations\ConversationEngine;
use App\Models\ChannelInstance;
use App\Models\Scenario;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class StartConversationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $scenarioId,
        public readonly int $channelInstanceId,
    ) {}

    public function handle(ConversationEngine $engine): void
    {
        $scenario = Scenario::findOrFail($this->scenarioId);
        $channel = ChannelInstance::findOrFail($this->channelInstanceId);

        $engine->start($scenario, $channel);
    }
}
