<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\ChannelInstance;
use App\Models\Conversation;
use Illuminate\View\View;
use Livewire\Component;

final class Dashboard extends Component
{
    public function render(): View
    {
        $activeConversations = Conversation::where('status', 'active')->count();

        $last24h = Conversation::where('created_at', '>=', now()->subDay())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $volumePerChannel = Conversation::with('channelInstance')
            ->where('created_at', '>=', now()->subDay())
            ->get()
            ->groupBy(function (Conversation $c): string {
                return $c->channelInstance instanceof ChannelInstance
                    ? $c->channelInstance->provider
                    : 'unknown';
            })
            ->map(fn ($group) => $group->count());

        $errorCount = Conversation::where('status', 'error')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return view('livewire.dashboard', [
            'activeConversations' => $activeConversations,
            'last24h' => $last24h,
            'volumePerChannel' => $volumePerChannel,
            'errorCount' => $errorCount,
        ]);
    }
}
