<div>
    {{-- Cabeçalho da conversa --}}
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Conversa #{{ $conversation->id }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Persona: <span class="font-medium text-gray-700">{{ $conversation->persona?->name ?? '-' }}</span> &middot;
                    Cenário: <span class="font-medium text-gray-700">{{ $conversation->scenario?->name ?? '-' }}</span> &middot;
                    Canal: <span class="font-medium text-gray-700">{{ $conversation->channelInstance?->provider ?? '-' }}</span>
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $this->statusBadge['class'] }}">
                    {{ $this->statusBadge['label'] }}
                </span>
                <span class="text-xs text-gray-500">
                    Turnos: <span class="font-medium text-gray-700">{{ $conversation->turn_count }}</span>
                </span>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
            <span>Iniciada em: <span class="font-medium text-gray-700">{{ $conversation->started_at?->format('d/m/Y H:i') ?? '-' }}</span></span>
            @if($conversation->ended_at)
                <span>Encerrada em: <span class="font-medium text-gray-700">{{ $conversation->ended_at->format('d/m/Y H:i') }}</span></span>
            @endif
            @if($conversation->end_reason)
                <span>Motivo: <span class="font-medium text-gray-700">{{ $conversation->end_reason }}</span></span>
            @endif
            @if($conversation->error)
                <span class="text-red-600">Erro: {{ $conversation->error }}</span>
            @endif
        </div>
    </div>

    {{-- Área de chat --}}
    <div
        @if($conversation->status === 'active') wire:poll.2s="pollConversation" @endif
        class="bg-[#efeae2] rounded-lg shadow p-4 max-h-[70vh] overflow-y-auto mb-4"
    >
        @if($conversation->messages->isEmpty())
            <p class="text-center text-sm text-gray-500 py-8">Nenhuma mensagem ainda.</p>
        @else
            <div class="space-y-3">
                @foreach($conversation->messages as $message)
                    @php
                        $isInbound = $message->direction === 'inbound';
                        $time = $message->sent_at?->format('H:i') ?? $message->created_at?->format('H:i') ?? '-';
                    @endphp

                    <div class="flex {{ $isInbound ? 'justify-start' : 'justify-end' }}" wire:key="msg-{{ $message->id }}">
                        <div class="max-w-[75%] rounded-lg px-4 py-2 shadow-sm {{ $isInbound ? 'bg-white text-gray-900' : 'bg-[#d9fdd3] text-gray-900' }}">
                            <p class="text-sm whitespace-pre-wrap">{{ $message->content }}</p>

                            <div class="mt-1 flex items-center justify-end gap-1.5">
                                @if($message->metadata['intent'] ?? null)
                                    <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-medium bg-gray-100 text-gray-600">
                                        intent: {{ $message->metadata['intent'] }}
                                    </span>
                                @endif

                                @if(isset($message->metadata['satisfaction']))
                                    <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-medium bg-yellow-50 text-yellow-700">
                                        satisfação: {{ $message->metadata['satisfaction'] }}/5
                                    </span>
                                @endif

                                <span class="text-[10px] text-gray-400">{{ $time }}</span>

                                @if(! $isInbound)
                                    @if($message->read_at)
                                        <span class="text-[10px] text-blue-500" title="Lida">&#10003;&#10003;</span>
                                    @elseif($message->delivered_at)
                                        <span class="text-[10px] text-gray-400" title="Entregue">&#10003;&#10003;</span>
                                    @elseif($message->status === 'pending' || $message->status === 'sent')
                                        <span class="text-[10px] text-gray-400" title="Enviada">&#10003;</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Raw Payloads (debug) --}}
    <details class="bg-white rounded-lg shadow mb-4">
        <summary class="cursor-pointer p-4 text-sm font-semibold text-gray-700 select-none">Raw Payloads</summary>
        <div class="px-4 pb-4 space-y-2">
            @forelse($conversation->rawPayloads as $payload)
                <div class="bg-gray-50 rounded p-3" wire:key="payload-{{ $payload->id }}">
                    <p class="text-xs font-medium text-gray-600 mb-1">
                        {{ $payload->direction }} — {{ $payload->channel }} — {{ $payload->created_at }}
                    </p>
                    <pre class="text-xs overflow-auto bg-gray-100 p-2 rounded">{{ json_encode($payload->payload, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @empty
                <p class="text-sm text-gray-500">Nenhum raw payload.</p>
            @endforelse
        </div>
    </details>

    <div class="mb-6">
        <a href="{{ route('conversations.index') }}" class="text-sm text-blue-600 underline hover:text-blue-800">&larr; Voltar para conversas</a>
    </div>
</div>
