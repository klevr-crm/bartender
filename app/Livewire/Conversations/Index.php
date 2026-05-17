<?php

declare(strict_types=1);

namespace App\Livewire\Conversations;

use App\Models\Conversation;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

final class Index extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public function render(): View
    {
        $query = Conversation::with(['scenario', 'persona', 'channelInstance'])
            ->orderByDesc('created_at');

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.conversations.index', [
            'conversations' => $query->paginate(20),
        ]);
    }
}
