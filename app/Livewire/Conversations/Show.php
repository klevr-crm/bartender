<?php

declare(strict_types=1);

namespace App\Livewire\Conversations;

use App\Models\Conversation;
use Illuminate\View\View;
use Livewire\Component;

final class Show extends Component
{
    public Conversation $conversation;

    public function mount(Conversation $conversation): void
    {
        $this->conversation = $conversation->load(['messages', 'rawPayloads', 'scenario', 'persona', 'channelInstance']);
    }

    public function render(): View
    {
        return view('livewire.conversations.show');
    }
}
