<?php

declare(strict_types=1);

namespace App\Livewire\Conversations;

use App\Models\Conversation;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class Show extends Component
{
    public Conversation $conversation;

    public function mount(Conversation $conversation): void
    {
        $this->conversation = $conversation->load(['messages', 'rawPayloads', 'scenario', 'persona', 'channelInstance']);
    }

    public function pollConversation(): void
    {
        if ($this->conversation->status !== 'active') {
            return;
        }

        $this->conversation->refresh()->load(['messages', 'rawPayloads', 'scenario', 'persona', 'channelInstance']);
    }

    /**
     * @return array{label: string, class: string}
     */
    #[Computed]
    public function statusBadge(): array
    {
        $map = [
            'active' => ['label' => 'Ativa', 'class' => 'bg-green-100 text-green-800'],
            'completed' => ['label' => 'Concluída', 'class' => 'bg-blue-100 text-blue-800'],
            'stalled' => ['label' => 'Parada', 'class' => 'bg-yellow-100 text-yellow-800'],
            'error' => ['label' => 'Erro', 'class' => 'bg-red-100 text-red-800'],
        ];

        return $map[$this->conversation->status] ?? ['label' => ucfirst($this->conversation->status), 'class' => 'bg-gray-100 text-gray-800'];
    }

    public function render(): View
    {
        return view('livewire.conversations.show');
    }
}
