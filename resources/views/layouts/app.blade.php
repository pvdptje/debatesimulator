<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debate Simulator</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-gray-950 text-gray-100 font-sans antialiased">
    <nav class="border-b border-gray-800 bg-gray-900">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="{{ route('home') }}" class="text-xl font-bold text-white tracking-tight">
                💬 Debate Simulator
            </a>
            <div class="flex gap-6 text-sm text-gray-400">
                <a href="{{ route('home') }}" class="hover:text-white transition">New Debate</a>
                <a href="{{ route('debate.history') }}" class="hover:text-white transition">History</a>
            </div>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-4 py-8">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
