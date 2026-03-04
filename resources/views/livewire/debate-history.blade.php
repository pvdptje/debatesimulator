<div>
    {{-- ── Header ── --}}
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2.25rem;">
        <h1 style="font-family: var(--font-display); font-size: 3.2rem; letter-spacing: -0.03em; color: #fff;">
            History
        </h1>
        <a href="{{ route('home') }}"
            style="display: inline-flex; align-items: center; gap: 0.4rem; background: var(--ht-accent); color: #fff; font-size: 0.82rem; font-weight: 600; padding: 0.6rem 1.15rem; border-radius: 9px; text-decoration: none; transition: all 0.2s; box-shadow: 0 0 16px var(--ht-accent-glow);"
            onmouseover="this.style.filter='brightness(1.15)'"
            onmouseout="this.style.filter='brightness(1)'"
        >
            + New Debate
        </a>
    </div>

    @if($debates->isEmpty())
    {{-- ── Empty state ── --}}
    <div style="text-align: center; padding: 5rem 0;">
        <p style="font-family: var(--font-display); font-size: 1.5rem; color: var(--ht-text-muted); margin-bottom: 0.6rem;">
            No debates yet.
        </p>
        <a href="{{ route('home') }}"
            style="font-family: var(--font-mono); font-size: 0.82rem; color: var(--ht-accent); text-decoration: none; transition: opacity 0.2s;"
            onmouseover="this.style.opacity='0.75'"
            onmouseout="this.style.opacity='1'"
        >
            Start one &#8594;
        </a>
    </div>
    @else
    {{-- ── Debate list ── --}}
    <div style="display: flex; flex-direction: column; gap: 0.4rem;">
        @foreach($debates as $debate)
        <a href="{{ route('debate.room', $debate->id) }}"
            style="display: block; background: var(--ht-surface); border: 1px solid var(--ht-border); border-radius: 12px; padding: 1.15rem 1.3rem; text-decoration: none; transition: all 0.2s;"
            onmouseover="this.style.borderColor='var(--ht-border-hi)'; this.style.background='var(--ht-surface-2)'"
            onmouseout="this.style.borderColor='var(--ht-border)'; this.style.background='var(--ht-surface)'"
        >
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;">
                <div style="flex: 1; min-width: 0;">
                    <p style="font-size: 0.95rem; font-weight: 600; color: #fff; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; transition: color 0.2s;">
                        {{ $debate->topic }}
                    </p>
                    <p style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--ht-text-muted); margin: 0.4rem 0 0;">
                        {{ $debate->agents_count }} agents
                        <span style="opacity: 0.35; margin: 0 0.25rem;">&middot;</span>
                        {{ $debate->messages_count }} messages
                        <span style="opacity: 0.35; margin: 0 0.25rem;">&middot;</span>
                        {{ $debate->current_round }} rounds
                        <span style="opacity: 0.35; margin: 0 0.25rem;">&middot;</span>
                        {{ $debate->created_at->diffForHumans() }}
                    </p>
                </div>
                <span style="flex-shrink: 0; font-family: var(--font-mono); font-size: 0.68rem; font-weight: 500; padding: 0.2rem 0.6rem; border-radius: 999px;
                    @if($debate->status === 'active')
                        background: rgba(34, 197, 94, 0.1); color: var(--ht-green); border: 1px solid rgba(34, 197, 94, 0.2);
                    @elseif($debate->status === 'finished')
                        background: var(--ht-surface-2); color: var(--ht-text-muted); border: 1px solid var(--ht-border);
                    @else
                        background: rgba(234, 179, 8, 0.1); color: #eab308; border: 1px solid rgba(234, 179, 8, 0.2);
                    @endif
                ">
                    {{ $debate->status }}
                </span>
            </div>
        </a>
        @endforeach
    </div>
    @endif
</div>