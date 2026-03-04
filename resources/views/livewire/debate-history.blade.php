<div>
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold">Debate History</h1>
        <a href="{{ route('home') }}" class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            + New Debate
        </a>
    </div>

    @if($debates->isEmpty())
    <div class="text-center py-16 text-gray-500">
        <p class="text-lg mb-2">No debates yet.</p>
        <a href="{{ route('home') }}" class="text-blue-400 hover:underline text-sm">Start one →</a>
    </div>
    @else
    <div class="space-y-3">
        @foreach($debates as $debate)
        <a href="{{ route('debate.room', $debate->id) }}" class="block bg-gray-900 hover:bg-gray-800 border border-gray-800 hover:border-gray-600 rounded-xl px-5 py-4 transition group">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-white group-hover:text-blue-300 transition truncate">{{ $debate->topic }}</p>
                    <p class="text-gray-500 text-xs mt-1">
                        {{ $debate->agents_count }} agents
                        &nbsp;·&nbsp;
                        {{ $debate->messages_count }} messages
                        &nbsp;·&nbsp;
                        {{ $debate->current_round }} rounds
                        &nbsp;·&nbsp;
                        {{ $debate->created_at->diffForHumans() }}
                    </p>
                </div>
                <span class="flex-shrink-0 text-xs font-medium px-2.5 py-1 rounded-full
                    @if($debate->status === 'active') bg-green-500/20 text-green-400
                    @elseif($debate->status === 'finished') bg-gray-700 text-gray-400
                    @else bg-yellow-500/20 text-yellow-400 @endif
                ">
                    {{ $debate->status }}
                </span>
            </div>
        </a>
        @endforeach
    </div>
    @endif
</div>
