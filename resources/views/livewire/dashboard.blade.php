<div>
    <h1 class="text-2xl font-bold mb-4">Bartender Dashboard</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-4 rounded shadow">
            <h2 class="text-sm text-gray-500">Active Conversations</h2>
            <p class="text-3xl font-bold">{{ $activeConversations }}</p>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <h2 class="text-sm text-gray-500">Errors (24h)</h2>
            <p class="text-3xl font-bold text-red-600">{{ $errorCount }}</p>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <h2 class="text-sm text-gray-500">Total (24h)</h2>
            <p class="text-3xl font-bold">{{ $last24h->sum() }}</p>
        </div>
    </div>

    <h2 class="text-lg font-semibold mb-2">Volume per Channel (24h)</h2>
    <ul class="space-y-1">
        @forelse($volumePerChannel as $channel => $count)
            <li class="bg-white p-2 rounded shadow flex justify-between">
                <span>{{ $channel }}</span>
                <span class="font-bold">{{ $count }}</span>
            </li>
        @empty
            <li class="text-gray-500">No conversations in the last 24h.</li>
        @endforelse
    </ul>

    <div class="mt-6 space-x-2">
        <a href="{{ route('scenarios.index') }}" class="inline-block bg-blue-600 text-white px-4 py-2 rounded">Scenarios</a>
        <a href="{{ route('conversations.index') }}" class="inline-block bg-gray-700 text-white px-4 py-2 rounded">Conversations</a>
    </div>
</div>
