<?php

declare(strict_types=1);

namespace App\Livewire\Scenarios;

use App\Jobs\StartConversationJob;
use App\Models\ChannelInstance;
use App\Models\Scenario;
use Illuminate\View\View;
use Livewire\Component;

final class Run extends Component
{
    public Scenario $scenario;

    public ?int $selectedChannelId = null;

    public function mount(Scenario $scenario): void
    {
        $this->scenario = $scenario->load('persona');
    }

    public function run(): void
    {
        if ($this->selectedChannelId === null) {
            $this->dispatch('error', 'Select a channel first');

            return;
        }

        StartConversationJob::dispatch($this->scenario->id, $this->selectedChannelId);

        $this->dispatch('success', 'Conversation started');
    }

    public function render(): View
    {
        $channels = ChannelInstance::where('provider', $this->scenario->channel)
            ->orWhere('channel_type', $this->scenario->channel)
            ->get();

        return view('livewire.scenarios.run', [
            'channels' => $channels,
        ]);
    }
}
