<div
    class="debate-room"
    style="display: flex; flex-direction: column; flex: 1; min-height: 0; padding-bottom: 2rem;"
    x-data="{ started: false }"
    x-init="
        if ('{{ $debate->status }}' === 'active' && !$wire.isStreaming) {
            $nextTick(() => { if (!started) { started = true; $wire.startNextTurn(); } });
        }
    "
    @turn-complete.window="$wire.startNextTurn()"
    @debate-ended.window="document.getElementById('chat-container').scrollTop = document.getElementById('chat-container').scrollHeight"
>
    {{-- ── Header ── --}}
    <div class="debate-header" style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.25rem; flex-shrink: 0;">
        <div style="min-width: 0; flex: 1;">
            <h1 style="font-family: var(--font-display); font-size: 1.75rem; color: #fff; letter-spacing: -0.02em; line-height: 1.2;">
                {{ $debate->topic }}
            </h1>
            <div class="debate-header-meta" style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-top: 0.65rem;">
                @php $modeConfig = config('debate_modes.' . ($debate->mode ?? 'heated')); @endphp
                {{-- Mode badge --}}
                <span style="display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; background: var(--ht-surface-2); border: 1px solid var(--ht-border); border-radius: 6px; padding: 0.25rem 0.55rem; color: var(--ht-text-dim);">
                    {{ $modeConfig['emoji'] ?? '' }} {{ $modeConfig['label'] ?? 'Heated' }}
                </span>
                {{-- Round --}}
                <span style="font-size: 0.82rem; font-weight: 600; color: var(--ht-text);">
                    Round {{ $debate->current_round + 1 }}
                </span>
                <span style="color: var(--ht-text-muted); font-size: 0.7rem;">&middot;</span>
                {{-- Agent pips --}}
                @foreach($agents as $agent)
                <span style="display: inline-flex; align-items: center; gap: 0.4rem; margin-right: 0.35rem;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; display: inline-block; box-shadow: 0 0 6px {{ $agent->color }}40; background-color: {{ $agent->color }};"></span>
                    <span style="font-size: 0.78rem; color: var(--ht-text-dim);">{{ $agent->name }}</span>
                </span>
                @endforeach
            </div>
        </div>
        @if($debate->status === 'active')
        <button wire:click="stopDebate" wire:confirm="Stop the debate?"
            style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--ht-text-muted); border: 1px solid var(--ht-border); padding: 0.4rem 0.85rem; border-radius: 8px; background: none; cursor: pointer; transition: all 0.2s; white-space: nowrap;"
            onmouseover="this.style.color='#f87171'; this.style.borderColor='rgba(248,113,113,0.3)'; this.style.background='rgba(248,113,113,0.05)'"
            onmouseout="this.style.color='var(--ht-text-muted)'; this.style.borderColor='var(--ht-border)'; this.style.background='none'"
        >
            Stop Debate
        </button>
        @endif
    </div>

    {{-- ── Kill Switch / Verdict Banner ── --}}
    @php
        $killMessage = $messages->firstWhere('is_kill_switch', true);
    @endphp
    @if($debate->status === 'finished' && $killMessage)
    <div class="msg-animate" style="margin-bottom: 1.25rem; flex-shrink: 0; border-radius: 14px; border: 1px solid rgba(255, 77, 0, 0.25); background: linear-gradient(to bottom, rgba(255, 77, 0, 0.06), rgba(255, 77, 0, 0.02)); padding: 1.75rem; text-align: center;">
        <p style="color: var(--ht-accent); font-family: var(--font-display); font-size: 1.35rem; margin-bottom: 0.35rem;">
            &#9889; Debate Ended
        </p>
        <p style="color: var(--ht-text); font-size: 0.9rem; margin-bottom: 0.5rem;">
            <span style="font-weight: 600; color: #fff;">{{ $killMessage->agent->name }}</span>
            declared:
            <span style="font-weight: 700; color: var(--ht-accent);">{{ $killMessage->verdict }}</span>
        </p>
        <p style="color: var(--ht-text-dim); font-size: 0.85rem; font-style: italic;">&ldquo;{{ $killMessage->kill_switch_reason }}&rdquo;</p>
    </div>
    @elseif($debate->status === 'finished')
    <div class="msg-animate" style="margin-bottom: 1.25rem; flex-shrink: 0; border-radius: 14px; border: 1px solid var(--ht-border); background: var(--ht-surface); padding: 1.25rem; text-align: center;">
        <p style="color: var(--ht-text-dim); font-size: 0.88rem;">Debate stopped manually.</p>
    </div>
    @endif

    {{-- ── Chat container ── --}}
    <div
        id="chat-container"
        class="debate-chat"
        style="flex: 1; min-height: 0; overflow-y: scroll; padding-right: 0.75rem; scroll-behavior: smooth;"
    >
        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
        @foreach($messages as $message)
        @php
            $agent = $message->agent;
            $displayContent = $message->content ?: $message->kill_switch_reason;
        @endphp
        @if($displayContent)
        <div class="msg-animate" style="display: flex; gap: 0.85rem;">
            {{-- Avatar --}}
            <div
                style="width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.04em; flex-shrink: 0; margin-top: 0.15rem; box-shadow: 0 0 12px {{ $agent->color }}30; background-color: {{ $agent->color }};"
            >
                {{ $agent->initials }}
            </div>

            {{-- Content --}}
            <div style="flex: 1; min-width: 0;">
                {{-- Meta row --}}
                <div style="display: flex; align-items: baseline; gap: 0.5rem; margin-bottom: 0.4rem; flex-wrap: wrap;">
                    <span style="font-weight: 600; font-size: 0.9rem; color: #fff;">{{ $agent->name }}</span>
                    <span style="font-size: 0.78rem; color: var(--ht-text-muted);">{{ $agent->role }}</span>
                    <span style="color: var(--ht-text-muted); font-size: 0.6rem;">&middot;</span>
                    <span class="msg-model" style="font-family: var(--font-mono); font-size: 0.68rem; color: var(--ht-text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 14rem;">{{ $agent->model }}</span>
                    <span style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--ht-text-muted); margin-left: auto; flex-shrink: 0; font-variant-numeric: tabular-nums;">R{{ $message->round + 1 }}</span>
                </div>
                {{-- Bubble --}}
                <div style="background: var(--ht-surface); border: 1px solid var(--ht-border); border-radius: 12px; border-top-left-radius: 3px; padding: 1rem 1.15rem; font-size: 0.92rem; color: var(--ht-text); line-height: 1.65;">
                    @if($message->is_kill_switch && !$message->content)
                        <span style="font-style: italic; color: var(--ht-accent);">{{ $message->kill_switch_reason }}</span>
                    @else
                        {{ $displayContent }}
                    @endif
                </div>
            </div>
        </div>
        @endif
        @endforeach

        {{-- ── Streaming placeholder ── --}}
        @if($isStreaming && $currentAgentId)
        @php $streamingAgent = $agents->firstWhere('id', $currentAgentId); @endphp
        @if($streamingAgent)
        <div class="msg-animate" style="display: flex; gap: 0.85rem;">
            <div
                style="width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.04em; flex-shrink: 0; margin-top: 0.15rem; box-shadow: 0 0 12px {{ $streamingAgent->color }}30; background-color: {{ $streamingAgent->color }};"
            >
                {{ $streamingAgent->initials }}
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="display: flex; align-items: baseline; gap: 0.5rem; margin-bottom: 0.4rem; flex-wrap: wrap;">
                    <span style="font-weight: 600; font-size: 0.9rem; color: #fff;">{{ $streamingAgent->name }}</span>
                    <span style="font-size: 0.78rem; color: var(--ht-text-muted);">{{ $streamingAgent->role }}</span>
                    <span style="color: var(--ht-text-muted); font-size: 0.6rem;">&middot;</span>
                    <span style="font-family: var(--font-mono); font-size: 0.68rem; color: var(--ht-text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 14rem;">{{ $streamingAgent->model }}</span>
                    <span style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--ht-text-muted); margin-left: auto; flex-shrink: 0; font-variant-numeric: tabular-nums;">R{{ $debate->current_round + 1 }}</span>
                </div>
                <div
                    style="background: var(--ht-surface); border: 1px solid var(--ht-border); border-radius: 12px; border-top-left-radius: 3px; padding: 1rem 1.15rem; font-size: 0.92rem; color: var(--ht-text); line-height: 1.65; min-height: 3rem; position: relative;"
                    x-data="{ hasContent: false }"
                    x-init="
                        const obs = new MutationObserver(() => {
                            const el = $el.querySelector('[wire\\:stream]');
                            if (el && el.textContent.trim().length > 0) hasContent = true;
                        });
                        obs.observe($el, { childList: true, subtree: true, characterData: true });
                    "
                >
                    {{-- Thinking indicator --}}
                    <span x-show="!hasContent" x-transition:leave.duration.200ms style="display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.78rem; color: var(--ht-text-muted);">
                        <span style="display: flex; align-items: center; gap: 3px;">
                            <span class="thinking-dot" style="background-color: {{ $streamingAgent->color }};"></span>
                            <span class="thinking-dot" style="background-color: {{ $streamingAgent->color }};"></span>
                            <span class="thinking-dot" style="background-color: {{ $streamingAgent->color }};"></span>
                        </span>
                        <span class="shimmer-badge" style="border-radius: 4px; padding: 0.15rem 0.5rem; font-family: var(--font-mono); font-size: 0.7rem;">thinking&hellip;</span>
                    </span>

                    {{-- Streamed content + cursor --}}
                    <span wire:stream="stream-{{ $currentAgentId }}-{{ $debate->current_round }}"></span><span class="streaming-cursor" style="display: inline-block; width: 2.5px; height: 1rem; border-radius: 999px; margin-left: 2px; vertical-align: middle; background-color: {{ $streamingAgent->color }};"></span>
                </div>
            </div>
        </div>
        @endif
        @endif
        </div>
    </div>

    {{-- Auto-scroll --}}
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.hook('morph.updated', () => {
                const c = document.getElementById('chat-container');
                if (c) {
                    requestAnimationFrame(() => {
                        c.scrollTo({ top: c.scrollHeight, behavior: 'smooth' });
                    });
                }
            });
        });
    </script>
</div>