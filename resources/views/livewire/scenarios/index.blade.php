<div>
    <h1 class="text-2xl font-bold mb-4">Scenarios</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($scenarios as $scenario)
            <div class="bg-white p-4 rounded shadow">
                <h2 class="text-lg font-bold">{{ $scenario->name }}</h2>
                <p class="text-sm text-gray-600 mb-2">{{ $scenario->description }}</p>
                <p class="text-sm"><strong>Persona:</strong> {{ $scenario->persona?->name }}</p>
                <p class="text-sm"><strong>Channel:</strong> {{ $scenario->channel }}</p>
                <a href="{{ route('scenarios.run', $scenario) }}" class="mt-3 inline-block bg-blue-600 text-white px-3 py-1 rounded text-sm">Run</a>
            </div>
        @endforeach
    </div>

    <div class="mt-4">
        <a href="{{ route('dashboard') }}" class="text-blue-600 underline">Dashboard</a>
    </div>
</div>
