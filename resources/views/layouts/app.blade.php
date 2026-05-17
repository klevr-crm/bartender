<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bartender</title>
    @livewireStyles
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow p-4 mb-6">
        <div class="max-w-6xl mx-auto flex justify-between items-center">
            <a href="{{ route('dashboard') }}" class="text-xl font-bold">Bartender</a>
            <div class="space-x-4">
                <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-black">Dashboard</a>
                <a href="{{ route('scenarios.index') }}" class="text-gray-700 hover:text-black">Scenarios</a>
                <a href="{{ route('conversations.index') }}" class="text-gray-700 hover:text-black">Conversations</a>
            </div>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-4">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
