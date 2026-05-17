<div>
    <h1 class="text-2xl font-bold mb-4">Conversations</h1>

    <div class="mb-4">
        <select wire:model.live="statusFilter" class="border rounded px-3 py-2">
            <option value="">All statuses</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
            <option value="error">Error</option>
        </select>
    </div>

    <table class="w-full bg-white rounded shadow">
        <thead>
            <tr class="text-left border-b">
                <th class="p-3">ID</th>
                <th class="p-3">Scenario</th>
                <th class="p-3">Persona</th>
                <th class="p-3">Channel</th>
                <th class="p-3">Status</th>
                <th class="p-3">Turns</th>
                <th class="p-3">Started</th>
            </tr>
        </thead>
        <tbody>
            @foreach($conversations as $conversation)
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-3">
                        <a href="{{ route('conversations.show', $conversation) }}" class="text-blue-600 underline">
                            #{{ $conversation->id }}
                        </a>
                    </td>
                    <td class="p-3">{{ $conversation->scenario?->name }}</td>
                    <td class="p-3">{{ $conversation->persona?->name }}</td>
                    <td class="p-3">{{ $conversation->channelInstance?->provider }}</td>
                    <td class="p-3">
                        <span class="px-2 py-1 rounded text-sm
                            {{ $conversation->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $conversation->status === 'completed' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $conversation->status === 'error' ? 'bg-red-100 text-red-800' : '' }}
                        ">{{ $conversation->status }}</span>
                    </td>
                    <td class="p-3">{{ $conversation->turn_count }}</td>
                    <td class="p-3">{{ $conversation->started_at?->diffForHumans() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $conversations->links() }}
    </div>

    <div class="mt-4">
        <a href="{{ route('dashboard') }}" class="text-blue-600 underline">Dashboard</a>
    </div>
</div>
