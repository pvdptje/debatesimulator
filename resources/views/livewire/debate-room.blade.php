<div
    x-data="{ started: false }"
    x-init="
        if ('{{ $debate->status }}' === 'active' && !$wire.isStreaming) {
            $nextTick(() => { if (!started) { started = true; $wire.startNextTurn(); } });
        }
    "
    @turn-complete.window="$wire.startNextTurn()"
    @debate-ended.window="document.getElementById('chat-container').scrollTop = document.getElementById('chat-container').scrollHeight"
>
    {{-- Header --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-white leading-tight">{{ $debate->topic }}</h1>
            <p class="text-gray-400 text-sm mt-1">
                <span class="font-medium">Round {{ $debate->current_round + 1 }}</span>
                &nbsp;·&nbsp;
                @foreach($agents as $agent)
                <span class="inline-flex items-center gap-1 mr-2">
                    <span class="w-2 h-2 rounded-full inline-block" style="background-color: {{ $agent->color }}"></span>
                    <span class="text-xs">{{ $agent->name }}</span>
                </span>
                @endforeach
            </p>
        </div>
        @if($debate->status === 'active')
        <button wire:click="stopDebate" wire:confirm="Stop the debate?"
            class="text-xs text-gray-500 hover:text-red-400 border border-gray-700 hover:border-red-500 px-3 py-1.5 rounded-lg transition">
            Stop Debate
        </button>
        @endif
    </div>

    {{-- Kill Switch Verdict Banner --}}
    @php
        $killMessage = $messages->firstWhere('is_kill_switch', true);
    @endphp
    @if($debate->status === 'finished' && $killMessage)
    <div class="mb-6 rounded-xl border border-yellow-500/50 bg-yellow-500/10 p-6 text-center">
        <p class="text-yellow-400 font-bold text-lg mb-1">⚡ Debate Ended</p>
        <p class="text-gray-300 text-sm mb-2">
            <span class="font-medium text-white">{{ $killMessage->agent->name }}</span>
            declared:
            <span class="font-semibold text-yellow-300">{{ $killMessage->verdict }}</span>
        </p>
        <p class="text-gray-400 text-sm italic">"{{ $killMessage->kill_switch_reason }}"</p>
    </div>
    @elseif($debate->status === 'finished')
    <div class="mb-6 rounded-xl border border-gray-700 bg-gray-800/50 p-4 text-center">
        <p class="text-gray-400 text-sm">Debate stopped manually.</p>
    </div>
    @endif

    {{-- Chat container --}}
    <div id="chat-container" class="space-y-4 overflow-y-auto max-h-[60vh] pr-2 mb-6">
        @foreach($messages as $message)
        @php
            $agent = $message->agent;
            $displayContent = $message->content ?: $message->kill_switch_reason;
        @endphp
        @if($displayContent)
        <div class="flex gap-3">
            <div
                class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0 mt-0.5"
                style="background-color: {{ $agent->color }}"
            >
                {{ $agent->initials }}
            </div>
            <div class="flex-1">
                <div class="flex items-baseline gap-2 mb-1">
                    <span class="font-semibold text-sm text-white">{{ $agent->name }}</span>
                    <span class="text-xs text-gray-500">{{ $agent->role }}</span>
                    <span class="text-xs text-gray-600 ml-auto">Round {{ $message->round + 1 }}</span>
                </div>
                <div class="bg-gray-800 rounded-xl rounded-tl-sm px-4 py-3 text-sm text-gray-200 leading-relaxed">
                    @if($message->is_kill_switch && !$message->content)
                        <span class="italic text-yellow-400">{{ $message->kill_switch_reason }}</span>
                    @else
                        {{ $displayContent }}
                    @endif
                </div>
            </div>
        </div>
        @endif
        @endforeach

        {{-- Streaming placeholder --}}
        @if($isStreaming && $currentAgentId)
        @php $streamingAgent = $agents->firstWhere('id', $currentAgentId); @endphp
        @if($streamingAgent)
        <div class="flex gap-3">
            <div
                class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0 mt-0.5"
                style="background-color: {{ $streamingAgent->color }}"
            >
                {{ $streamingAgent->initials }}
            </div>
            <div class="flex-1">
                <div class="flex items-baseline gap-2 mb-1">
                    <span class="font-semibold text-sm text-white">{{ $streamingAgent->name }}</span>
                    <span class="text-xs text-gray-500">{{ $streamingAgent->role }}</span>
                    <span class="text-xs text-gray-600 ml-auto">Round {{ $debate->current_round + 1 }}</span>
                </div>
                <div class="bg-gray-800 rounded-xl rounded-tl-sm px-4 py-3 text-sm text-gray-200 leading-relaxed min-h-[2.5rem]">
                    <span wire:stream="stream-{{ $currentAgentId }}-{{ $debate->current_round }}"></span><span class="inline-block w-1.5 h-4 bg-blue-400 animate-pulse ml-0.5 align-middle"></span>
                </div>
            </div>
        </div>
        @endif
        @endif
    </div>

    {{-- Auto-scroll on stream updates --}}
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.hook('morph.updated', () => {
                const c = document.getElementById('chat-container');
                if (c) c.scrollTop = c.scrollHeight;
            });
        });
    </script>
</div>
