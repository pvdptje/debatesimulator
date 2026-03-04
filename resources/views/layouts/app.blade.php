<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HotTake — AI Debate Arena</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300..700;1,9..40,300..700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <style>
        :root {
            /* ── Palette ── */
            --ht-bg:         #0a0a0b;
            --ht-surface:    #111113;
            --ht-surface-2:  #19191d;
            --ht-border:     rgba(255, 255, 255, 0.06);
            --ht-border-hi:  rgba(255, 255, 255, 0.10);
            --ht-text:       #e8e6e3;
            --ht-text-dim:   #7a7a80;
            --ht-text-muted: #4a4a50;
            --ht-accent:     #ff4d00;
            --ht-accent-soft: rgba(255, 77, 0, 0.12);
            --ht-accent-glow: rgba(255, 77, 0, 0.25);
            --ht-purple:     #a855f7;
            --ht-purple-soft: rgba(168, 85, 247, 0.12);
            --ht-green:      #22c55e;

            /* ── Typography ── */
            --font-display: 'Instrument Serif', Georgia, serif;
            --font-body:    'DM Sans', system-ui, sans-serif;
            --font-mono:    'JetBrains Mono', monospace;
        }

        * {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.06) transparent;
        }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.06);
            border-radius: 999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.12);
        }

        body {
            font-family: var(--font-body);
            background: var(--ht-bg);
            color: var(--ht-text);
        }

        /* ── Noise overlay ── */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            z-index: 9999;
            pointer-events: none;
            opacity: 0.025;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
            background-repeat: repeat;
            background-size: 256px 256px;
        }

        /* ── Ambient glow on page ── */
        .ht-ambient {
            position: fixed;
            top: -40%;
            left: 50%;
            transform: translateX(-50%);
            width: 80vw;
            height: 60vh;
            background: radial-gradient(ellipse at center, var(--ht-accent-soft) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
            opacity: 0.4;
        }

        /* ── Nav ── */
        .ht-nav {
            background: rgba(10, 10, 11, 0.85);
            backdrop-filter: blur(20px) saturate(1.3);
            -webkit-backdrop-filter: blur(20px) saturate(1.3);
            border-bottom: 1px solid var(--ht-border);
        }
        .ht-logo {
            font-family: var(--font-display);
            font-size: 1.55rem;
            letter-spacing: -0.02em;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .ht-logo-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--ht-accent);
            color: #fff;
            font-family: var(--font-body);
            font-weight: 700;
            font-size: 0.7rem;
            letter-spacing: 0.04em;
            line-height: 1;
            flex-shrink: 0;
            box-shadow: 0 0 20px var(--ht-accent-glow), 0 0 4px var(--ht-accent-glow);
        }
        .ht-nav-link {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--ht-text-dim);
            text-decoration: none;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            transition: all 0.2s ease;
            letter-spacing: 0.01em;
        }
        .ht-nav-link:hover {
            color: var(--ht-text);
            background: rgba(255, 255, 255, 0.04);
        }
        .ht-nav-link--active {
            color: var(--ht-text);
            background: rgba(255, 255, 255, 0.06);
        }
        .ht-nav-sep {
            width: 1px;
            height: 16px;
            background: var(--ht-border);
            align-self: center;
        }
        .ht-nav-pill {
            font-family: var(--font-mono);
            font-size: 0.65rem;
            font-weight: 500;
            color: var(--ht-accent);
            background: var(--ht-accent-soft);
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            letter-spacing: 0.03em;
        }

        /* ── Main ── */
        .ht-main {
            position: relative;
            z-index: 1;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            max-width: 56rem;
            width: 100%;
            margin: 0 auto;
            padding: 2.5rem 1.25rem 3rem;
        }

        /* ── Streaming cursor ── */
        @keyframes cursor-blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }
        .streaming-cursor {
            animation: cursor-blink 0.8s ease-in-out infinite;
        }

        /* ── Thinking dots ── */
        @keyframes dot-bounce {
            0%, 80%, 100% { transform: translateY(0); opacity: 0.4; }
            40% { transform: translateY(-5px); opacity: 1; }
        }
        .thinking-dot {
            display: inline-block;
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: var(--ht-text-dim);
            animation: dot-bounce 1.4s ease-in-out infinite;
        }
        .thinking-dot:nth-child(2) { animation-delay: 0.16s; }
        .thinking-dot:nth-child(3) { animation-delay: 0.32s; }

        /* ── Message enter ── */
        @keyframes msg-enter {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .msg-animate {
            animation: msg-enter 0.4s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        /* ── Shimmer ── */
        @keyframes shimmer {
            0%   { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        .shimmer-badge {
            background: linear-gradient(90deg, transparent 25%, rgba(255,255,255,0.04) 50%, transparent 75%);
            background-size: 200% 100%;
            animation: shimmer 2.5s ease-in-out infinite;
        }

        /* ── Page load entrance ── */
        @keyframes fade-up {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .ht-main > * {
            animation: fade-up 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        .ht-main > *:nth-child(1) { animation-delay: 0.05s; }
        .ht-main > *:nth-child(2) { animation-delay: 0.1s; }
        .ht-main > *:nth-child(3) { animation-delay: 0.15s; }
        .ht-main > *:nth-child(4) { animation-delay: 0.2s; }

        /* ── Footer ── */
        .ht-footer {
            border-top: 1px solid var(--ht-border);
            padding: 1rem 0;
            text-align: center;
            font-size: 0.7rem;
            color: var(--ht-text-muted);
            letter-spacing: 0.02em;
        }
        .ht-footer a {
            color: var(--ht-text-dim);
            text-decoration: none;
            transition: color 0.2s;
        }
        .ht-footer a:hover {
            color: var(--ht-accent);
        }

        /* ── Kbd tag ── */
        kbd {
            font-family: var(--font-mono);
            font-size: 0.65rem;
            background: var(--ht-surface-2);
            border: 1px solid var(--ht-border-hi);
            border-radius: 4px;
            padding: 0.1rem 0.4rem;
            color: var(--ht-text-dim);
        }
    </style>
</head>
<body class="h-full flex flex-col antialiased">
    {{-- Ambient glow --}}
    <div class="ht-ambient" aria-hidden="true"></div>

    {{-- Navigation --}}
    <nav class="ht-nav sticky top-0 z-50">
        <div class="max-w-[56rem] mx-auto px-5 py-3 flex items-center justify-between">
            <a href="{{ route('home') }}" class="ht-logo">
                <span class="ht-logo-mark">HT</span>
                <span>HotTake</span>
            </a>

            <div class="flex items-center gap-1">
                <a href="{{ route('home') }}"
                   class="ht-nav-link {{ request()->routeIs('home') ? 'ht-nav-link--active' : '' }}">
                    New
                </a>
                <a href="{{ route('debate.history') }}"
                   class="ht-nav-link {{ request()->routeIs('debate.history') ? 'ht-nav-link--active' : '' }}">
                    History
                </a>
                <div class="ht-nav-sep mx-2"></div>
                <span class="ht-nav-pill">BETA</span>
            </div>
        </div>
    </nav>

    {{-- Main content --}}
    <main class="ht-main">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="ht-footer">
        <div class="max-w-[56rem] mx-auto px-5">
            HotTake — where AI agents fight so you don't have to.
        </div>
    </footer>

    @livewireScripts
</body>
</html>