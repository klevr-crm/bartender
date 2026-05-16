<div>
    <h1 class="text-2xl font-bold mb-4">Run Scenario: {{ $scenario->name }}</h1>

    <div class="bg-white p-4 rounded shadow mb-4">
        <p class="mb-2"><strong>Persona:</strong> {{ $scenario->persona?->name }}</p>
        <p class="mb-2"><strong>Channel:</strong> {{ $scenario->channel }}</p>
        <p class="mb-2"><strong>Description:</strong> {{ $scenario->description }}</p>
    </div>

    <div class="mb-4">
        <label class="block text-sm font-medium mb-1">Select Channel Instance</label>
        <select wire:model="selectedChannelId" class="border rounded px-3 py-2 w-full md:w-1/2">
            <option value="">-- choose --</option>
            @foreach($channels as $channel)
                <option value="{{ $channel->id }}">{{ $channel->name }} ({{ $channel->external_id }})</option>
            @endforeach
        </select>
    </div>

    <button wire:click="run" class="bg-green-600 text-white px-4 py-2 rounded">Start Conversation</button>

    <div class="mt-4" x-data="{ show: false, msg: '' }" x-on:success.window="show = true; msg = $event.detail[0]; setTimeout(() => show = false, 3000)" x-on:error.window="show = true; msg = $event.detail[0]; setTimeout(() => show = false, 3000)">
        <div x-show="show" x-text="msg" class="p-3 rounded bg-gray-100"></div>
    </div>

    <div class="mt-4">
        <a href="{{ route('scenarios.index') }}" class="text-blue-600 underline">Back to scenarios</a>
    </div>
</div>
