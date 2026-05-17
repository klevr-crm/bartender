<div>
    <h1 class="text-2xl font-bold mb-4">Conversation #{{ $conversation->id }}</h1>

    <div class="bg-white p-4 rounded shadow mb-6">
        <p><strong>Scenario:</strong> {{ $conversation->scenario?->name }}</p>
        <p><strong>Persona:</strong> {{ $conversation->persona?->name }}</p>
        <p><strong>Channel:</strong> {{ $conversation->channelInstance?->provider }}</p>
        <p><strong>Status:</strong> {{ $conversation->status }}</p>
        <p><strong>Turns:</strong> {{ $conversation->turn_count }}</p>
    </div>

    <h2 class="text-lg font-semibold mb-2">Messages</h2>
    <div class="space-y-2 mb-6">
        @foreach($conversation->messages as $message)
            <div class="p-3 rounded {{ $message->direction === 'inbound' ? 'bg-green-50' : 'bg-gray-50' }}">
                <span class="text-xs text-gray-500 uppercase">{{ $message->role }} ({{ $message->direction }})</span>
                <p>{{ $message->content }}</p>
            </div>
        @endforeach
    </div>

    <h2 class="text-lg font-semibold mb-2">Raw Payloads</h2>
    <div class="space-y-2">
        @foreach($conversation->rawPayloads as $payload)
            <details class="bg-white p-3 rounded shadow">
                <summary>{{ $payload->direction }} — {{ $payload->channel }} — {{ $payload->created_at }}</summary>
                <pre class="text-xs overflow-auto mt-2 bg-gray-100 p-2 rounded">{{ json_encode($payload->payload, JSON_PRETTY_PRINT) }}</pre>
            </details>
        @endforeach
    </div>

    <div class="mt-4">
        <a href="{{ route('conversations.index') }}" class="text-blue-600 underline">Back to conversations</a>
    </div>
</div>
