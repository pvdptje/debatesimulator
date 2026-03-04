<div>
    {{-- ── Header ── --}}
    <div style="margin-bottom: 2.5rem;">
        <h1 style="font-family: var(--font-display); font-size: 3.2rem; letter-spacing: -0.03em; line-height: 1.1; color: #fff; margin-bottom: 0.5rem;">
            New Debate
        </h1>
        <p style="font-family: var(--font-mono); font-size: 0.78rem; color: var(--ht-text-muted); letter-spacing: 0.02em;">
            // think of it like Slack, but everyone's a dickhead
        </p>
    </div>

    {{-- ── Topic + Min Rounds ── --}}
    <div style="margin-bottom: 2.25rem;">
        <div style="display: flex; align-items: flex-end; gap: 0.75rem; margin-bottom: 0.6rem;">
            <label style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: var(--ht-text-dim);">
                Topic
            </label>
            <div style="margin-left: auto; display: flex; align-items: center; gap: 0.5rem;">
                <label style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--ht-text-muted); white-space: nowrap;">
                    min_rounds
                </label>
                <input wire:model="minRounds" type="number" min="1" max="50"
                    style="width: 3.8rem; background: var(--ht-surface); border: 1px solid var(--ht-border-hi); border-radius: 8px; padding: 0.45rem 0.5rem; color: #fff; font-family: var(--font-mono); font-size: 0.85rem; text-align: center; outline: none; transition: border-color 0.2s, box-shadow 0.2s;"
                    onfocus="this.style.borderColor='var(--ht-accent)'; this.style.boxShadow='0 0 0 3px var(--ht-accent-soft)'"
                    onblur="this.style.borderColor='var(--ht-border-hi)'; this.style.boxShadow='none'"
                >
            </div>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <input
                wire:model="topic"
                type="text"
                placeholder="e.g. Should billionaires exist?"
                style="flex: 1; background: var(--ht-surface); border: 1px solid var(--ht-border-hi); border-radius: 10px; padding: 0.95rem 1.2rem; color: #fff; font-size: 0.95rem; outline: none; transition: border-color 0.2s, box-shadow 0.2s;"
                onfocus="this.style.borderColor='var(--ht-accent)'; this.style.boxShadow='0 0 0 3px var(--ht-accent-soft)'"
                onblur="this.style.borderColor='var(--ht-border-hi)'; this.style.boxShadow='none'"
            >
            @if(count($agents) >= 2)
            <button
                wire:click="startDebate"
                wire:loading.attr="disabled"
                wire:target="startDebate"
                style="display: inline-flex; align-items: center; gap: 0.5rem; background: var(--ht-accent); color: #fff; font-size: 0.85rem; font-weight: 600; padding: 0.95rem 1.4rem; border-radius: 10px; border: none; cursor: pointer; white-space: nowrap; transition: all 0.2s; letter-spacing: 0.01em; box-shadow: 0 0 20px var(--ht-accent-glow);"
                onmouseover="this.style.filter='brightness(1.15)'"
                onmouseout="this.style.filter='brightness(1)'"
            >
                <span wire:loading.remove wire:target="startDebate">Start Debate &#8594;</span>
                <span wire:loading.flex wire:target="startDebate" style="align-items: center; gap: 0.5rem;">
                    <svg class="animate-spin" style="width: 15px; height: 15px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Starting&hellip;
                </span>
            </button>
            @else
            <button
                wire:click="generateAgents"
                wire:loading.attr="disabled"
                wire:target="generateAgents"
                @if($generating) disabled @endif
                style="display: inline-flex; align-items: center; gap: 0.5rem; background: var(--ht-purple); color: #fff; font-size: 0.85rem; font-weight: 600; padding: 0.95rem 1.4rem; border-radius: 10px; border: none; cursor: pointer; white-space: nowrap; transition: all 0.2s; letter-spacing: 0.01em; box-shadow: 0 0 20px var(--ht-purple-soft);"
                onmouseover="this.style.filter='brightness(1.15)'"
                onmouseout="this.style.filter='brightness(1)'"
            >
                <span wire:loading.remove wire:target="generateAgents">&#10024; Generate Agents</span>
                <span wire:loading.flex wire:target="generateAgents" style="align-items: center; gap: 0.5rem;">
                    <svg class="animate-spin" style="width: 15px; height: 15px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Generating&hellip;
                </span>
            </button>
            @endif
        </div>
        @error('topic') <p style="margin-top: 0.4rem; font-size: 0.82rem; color: #f87171;">{{ $message }}</p> @enderror
        @if($generateError)
        <p style="margin-top: 0.4rem; font-size: 0.82rem; color: #f87171;">{{ $generateError }}</p>
        @endif
        @if(empty($agents))
        <p style="margin-top: 0.7rem; font-family: var(--font-mono); font-size: 0.74rem; color: var(--ht-text-muted); line-height: 1.6;">
            Leave topic blank &amp; hit <span style="color: var(--ht-purple);">Generate Agents</span> for a random topic — or type your own. AI casts the contestants.
        </p>
        @endif
    </div>

    {{-- ── Debate Mode ── --}}
    <div style="margin-bottom: 2.25rem;">
        <label style="display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: var(--ht-text-dim); margin-bottom: 0.85rem;">
            Mode
        </label>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 0.5rem;">
            @foreach($modes as $key => $modeConfig)
            <button
                wire:click="$set('mode', '{{ $key }}')"
                type="button"
                style="text-align: left; padding: 1rem 1.1rem; border-radius: 10px; border: 1px solid {{ $mode === $key ? 'var(--ht-accent)' : 'var(--ht-border-hi)' }}; background: {{ $mode === $key ? 'var(--ht-accent-soft)' : 'var(--ht-surface)' }}; color: {{ $mode === $key ? '#fff' : 'var(--ht-text-dim)' }}; cursor: pointer; transition: all 0.2s;"
                onmouseover="@if($mode !== $key) this.style.borderColor='var(--ht-border-hi)'; this.style.background='var(--ht-surface-2)'; this.style.color='var(--ht-text)' @endif"
                onmouseout="@if($mode !== $key) this.style.borderColor='var(--ht-border-hi)'; this.style.background='var(--ht-surface)'; this.style.color='var(--ht-text-dim)' @endif"
            >
                <div style="font-size: 1.35rem; line-height: 1; margin-bottom: 0.4rem;">{{ $modeConfig['emoji'] }}</div>
                <div style="font-size: 0.85rem; font-weight: 600;">{{ $modeConfig['label'] }}</div>
                <div style="font-size: 0.72rem; margin-top: 0.2rem; opacity: 0.6; font-family: var(--font-mono);">{{ $modeConfig['description'] }}</div>
            </button>
            @endforeach
        </div>
    </div>

    {{-- ── Contestants ── --}}
    @if(count($agents) > 0)
    <div style="margin-bottom: 2.25rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.85rem;">
            <h2 style="font-family: var(--font-display); font-size: 1.6rem; color: #fff; letter-spacing: -0.02em;">
                Contestants
                <span style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--ht-text-muted); margin-left: 0.4rem;">
                    {{ count($agents) }}/4
                </span>
            </h2>
            <button wire:click="$set('agents', [])"
                style="font-family: var(--font-mono); font-size: 0.7rem; color: var(--ht-text-muted); background: none; border: none; cursor: pointer; padding: 0.35rem 0.6rem; border-radius: 4px; transition: all 0.2s;"
                onmouseover="this.style.color='#f87171'; this.style.background='rgba(248,113,113,0.08)'"
                onmouseout="this.style.color='var(--ht-text-muted)'; this.style.background='none'"
            >
                clear all
            </button>
        </div>
        <div style="display: flex; flex-direction: column; gap: 0.45rem;">
            @foreach($agents as $i => $agent)
            @if($editingIndex === $i)
            {{-- ── Inline Edit Form ── --}}
            <div style="background: var(--ht-surface); border: 1px solid var(--ht-accent); border-radius: 12px; padding: 1.1rem 1.15rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem;">
                    <div>
                        <label style="display: block; font-family: var(--font-mono); font-size: 0.65rem; color: var(--ht-text-muted); margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.06em;">Name</label>
                        <input wire:model="editName" type="text"
                            style="width: 100%; background: var(--ht-bg); border: 1px solid var(--ht-border-hi); border-radius: 7px; padding: 0.5rem 0.75rem; color: #fff; font-size: 0.85rem; outline: none;"
                            onfocus="this.style.borderColor='var(--ht-accent)'" onblur="this.style.borderColor='var(--ht-border-hi)'">
                        @error('editName') <p style="margin-top: 0.2rem; font-size: 0.72rem; color: #f87171;">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label style="display: block; font-family: var(--font-mono); font-size: 0.65rem; color: var(--ht-text-muted); margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.06em;">Role</label>
                        <input wire:model="editRole" type="text"
                            style="width: 100%; background: var(--ht-bg); border: 1px solid var(--ht-border-hi); border-radius: 7px; padding: 0.5rem 0.75rem; color: #fff; font-size: 0.85rem; outline: none;"
                            onfocus="this.style.borderColor='var(--ht-accent)'" onblur="this.style.borderColor='var(--ht-border-hi)'">
                        @error('editRole') <p style="margin-top: 0.2rem; font-size: 0.72rem; color: #f87171;">{{ $message }}</p> @enderror
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label style="display: block; font-family: var(--font-mono); font-size: 0.65rem; color: var(--ht-text-muted); margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.06em;">Stance</label>
                        <input wire:model="editStance" type="text"
                            style="width: 100%; background: var(--ht-bg); border: 1px solid var(--ht-border-hi); border-radius: 7px; padding: 0.5rem 0.75rem; color: #fff; font-size: 0.85rem; outline: none;"
                            onfocus="this.style.borderColor='var(--ht-accent)'" onblur="this.style.borderColor='var(--ht-border-hi)'">
                        @error('editStance') <p style="margin-top: 0.2rem; font-size: 0.72rem; color: #f87171;">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label style="display: block; font-family: var(--font-mono); font-size: 0.65rem; color: var(--ht-text-muted); margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.06em;">Provider</label>
                        <select wire:model.live="editProvider"
                            style="width: 100%; background: var(--ht-bg); border: 1px solid var(--ht-border-hi); border-radius: 7px; padding: 0.5rem 0.75rem; color: #fff; font-size: 0.85rem; outline: none; cursor: pointer;">
                            @foreach($providers as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-family: var(--font-mono); font-size: 0.65rem; color: var(--ht-text-muted); margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.06em;">Model</label>
                        <select wire:model="editModel"
                            style="width: 100%; background: var(--ht-bg); border: 1px solid var(--ht-border-hi); border-radius: 7px; padding: 0.5rem 0.75rem; color: #fff; font-size: 0.85rem; outline: none; cursor: pointer;">
                            @foreach($this->getModelsForProvider($editProvider) as $m)
                            <option value="{{ $m }}">{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <label style="font-family: var(--font-mono); font-size: 0.65rem; color: var(--ht-text-muted); text-transform: uppercase; letter-spacing: 0.06em;">Color</label>
                        <input wire:model="editColor" type="color"
                            style="width: 36px; height: 30px; background: var(--ht-bg); border: 1px solid var(--ht-border-hi); border-radius: 6px; cursor: pointer; padding: 2px;">
                        <span style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--ht-text-muted);">{{ $editColor }}</span>
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem; margin-top: 0.85rem;">
                    <button wire:click="saveAgent"
                        style="background: var(--ht-accent); color: #fff; font-size: 0.78rem; font-weight: 600; padding: 0.45rem 1rem; border-radius: 7px; border: none; cursor: pointer; transition: filter 0.2s;"
                        onmouseover="this.style.filter='brightness(1.15)'" onmouseout="this.style.filter='brightness(1)'">
                        Save
                    </button>
                    <button wire:click="cancelEdit"
                        style="background: none; color: var(--ht-text-muted); font-size: 0.78rem; font-weight: 500; padding: 0.45rem 0.85rem; border-radius: 7px; border: 1px solid var(--ht-border-hi); cursor: pointer; transition: all 0.2s;"
                        onmouseover="this.style.color='#fff'; this.style.borderColor='var(--ht-border-hi)'" onmouseout="this.style.color='var(--ht-text-muted)'">
                        Cancel
                    </button>
                </div>
            </div>
            @else
            {{-- ── Read-only Row ── --}}
            <div style="display: flex; align-items: center; gap: 0.9rem; background: var(--ht-surface); border: 1px solid var(--ht-border); border-radius: 12px; padding: 1rem 1.15rem; transition: border-color 0.2s;"
                 onmouseover="this.style.borderColor='var(--ht-border-hi)'"
                 onmouseout="this.style.borderColor='var(--ht-border)'"
            >
                <div style="width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; flex-shrink: 0; background-color: {{ $agent['color'] }};">
                    {{ strtoupper(substr($agent['name'], 0, 2)) }}
                </div>
                <div style="flex: 1; min-width: 0;">
                    <p style="font-size: 0.9rem; font-weight: 600; color: #fff; margin: 0;">
                        {{ $agent['name'] }}
                        <span style="font-weight: 400; color: var(--ht-text-dim); margin-left: 0.3rem;">&mdash; {{ $agent['role'] }}</span>
                    </p>
                    <p style="font-size: 0.78rem; color: var(--ht-text-muted); font-style: italic; margin: 0.2rem 0 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        &ldquo;{{ $agent['stance'] }}&rdquo;
                    </p>
                    <p style="font-family: var(--font-mono); font-size: 0.68rem; color: var(--ht-text-muted); margin: 0.3rem 0 0;">
                        {{ $agent['provider'] }} <span style="opacity: 0.4;">/</span> {{ $agent['model'] }}
                    </p>
                </div>
                <button wire:click="editAgent({{ $i }})"
                    style="background: none; border: none; color: var(--ht-text-muted); font-size: 0.95rem; cursor: pointer; padding: 0.35rem; border-radius: 6px; transition: all 0.2s; line-height: 1;"
                    onmouseover="this.style.color='#fff'; this.style.background='rgba(255,255,255,0.07)'"
                    onmouseout="this.style.color='var(--ht-text-muted)'; this.style.background='none'"
                    title="Edit">&#9998;</button>
                <button wire:click="removeAgent({{ $i }})"
                    style="background: none; border: none; color: var(--ht-text-muted); font-size: 1.2rem; cursor: pointer; padding: 0.35rem; border-radius: 6px; transition: all 0.2s; line-height: 1;"
                    onmouseover="this.style.color='#f87171'; this.style.background='rgba(248,113,113,0.08)'"
                    onmouseout="this.style.color='var(--ht-text-muted)'; this.style.background='none'"
                >&times;</button>
            </div>
            @endif
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Add Agent Form ── --}}
    @if(count($agents) < 4)
    <div style="background: var(--ht-surface); border: 1px solid var(--ht-border); border-radius: 14px; padding: 1.85rem; margin-bottom: 2rem;">
        <h2 style="font-family: var(--font-display); font-size: 1.45rem; color: #fff; letter-spacing: -0.02em; margin-bottom: 1.35rem;">
            Add Agent
            <span style="font-family: var(--font-mono); font-size: 0.68rem; color: var(--ht-text-muted); margin-left: 0.4rem; font-style: normal;">manual</span>
        </h2>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem;">
            <div>
                <label style="display: block; font-family: var(--font-mono); font-size: 0.68rem; font-weight: 500; color: var(--ht-text-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.06em;">
                    Name
                </label>
                <input wire:model="agentName" type="text" placeholder="Dr. Sarah Chen"
                    style="width: 100%; background: var(--ht-bg); border: 1px solid var(--ht-border-hi); border-radius: 8px; padding: 0.6rem 0.85rem; color: #fff; font-size: 0.88rem; outline: none; transition: border-color 0.2s, box-shadow 0.2s;"
                    onfocus="this.style.borderColor='var(--ht-accent)'; this.style.boxShadow='0 0 0 3px var(--ht-accent-soft)'"
                    onblur="this.style.borderColor='var(--ht-border-hi)'; this.style.boxShadow='none'"
                >
                @error('agentName') <p style="margin-top: 0.3rem; font-size: 0.75rem; color: #f87171;">{{ $message }}</p> @enderror
            </div>
            <div>
                <label style="display: block; font-family: var(--font-mono); font-size: 0.68rem; font-weight: 500; color: var(--ht-text-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.06em;">
                    Role
                </label>
                <input wire:model="agentRole" type="text" placeholder="Climate Scientist"
                    style="width: 100%; background: var(--ht-bg); border: 1px solid var(--ht-border-hi); border-radius: 8px; padding: 0.6rem 0.85rem; color: #fff; font-size: 0.88rem; outline: none; transition: border-color 0.2s, box-shadow 0.2s;"
                    onfocus="this.style.borderColor='var(--ht-accent)'; this.style.boxShadow='0 0 0 3px var(--ht-accent-soft)'"
                    onblur="this.style.borderColor='var(--ht-border-hi)'; this.style.boxShadow='none'"
                >
                @error('agentRole') <p style="margin-top: 0.3rem; font-size: 0.75rem; color: #f87171;">{{ $message }}</p> @enderror
            </div>
            <div style="grid-column: 1 / -1;">
                <label style="display: block; font-family: var(--font-mono); font-size: 0.68rem; font-weight: 500; color: var(--ht-text-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.06em;">
                    Stance
                </label>
                <input wire:model="agentStance" type="text" placeholder="Renewable energy is the only viable path forward"
                    style="width: 100%; background: var(--ht-bg); border: 1px solid var(--ht-border-hi); border-radius: 8px; padding: 0.6rem 0.85rem; color: #fff; font-size: 0.88rem; outline: none; transition: border-color 0.2s, box-shadow 0.2s;"
                    onfocus="this.style.borderColor='var(--ht-accent)'; this.style.boxShadow='0 0 0 3px var(--ht-accent-soft)'"
                    onblur="this.style.borderColor='var(--ht-border-hi)'; this.style.boxShadow='none'"
                >
                @error('agentStance') <p style="margin-top: 0.3rem; font-size: 0.75rem; color: #f87171;">{{ $message }}</p> @enderror
            </div>
            <div>
                <label style="display: block; font-family: var(--font-mono); font-size: 0.68rem; font-weight: 500; color: var(--ht-text-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.06em;">
                    Provider
                </label>
                <select wire:model.live="agentProvider"
                    style="width: 100%; background: var(--ht-bg); border: 1px solid var(--ht-border-hi); border-radius: 8px; padding: 0.6rem 0.85rem; color: #fff; font-size: 0.88rem; outline: none; cursor: pointer; transition: border-color 0.2s;"
                    onfocus="this.style.borderColor='var(--ht-accent)'"
                    onblur="this.style.borderColor='var(--ht-border-hi)'"
                >
                    @foreach($providers as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display: block; font-family: var(--font-mono); font-size: 0.68rem; font-weight: 500; color: var(--ht-text-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.06em;">
                    Model
                </label>
                <select wire:model="agentModel"
                    style="width: 100%; background: var(--ht-bg); border: 1px solid var(--ht-border-hi); border-radius: 8px; padding: 0.6rem 0.85rem; color: #fff; font-size: 0.88rem; outline: none; cursor: pointer; transition: border-color 0.2s;"
                    onfocus="this.style.borderColor='var(--ht-accent)'"
                    onblur="this.style.borderColor='var(--ht-border-hi)'"
                >
                    @foreach($availableModels as $model)
                    <option value="{{ $model }}">{{ $model }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display: block; font-family: var(--font-mono); font-size: 0.68rem; font-weight: 500; color: var(--ht-text-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.06em;">
                    Color
                </label>
                <div style="display: flex; align-items: center; gap: 0.65rem;">
                    <input wire:model="agentColor" type="color"
                        style="width: 40px; height: 36px; background: var(--ht-bg); border: 1px solid var(--ht-border-hi); border-radius: 8px; cursor: pointer; padding: 2px;">
                    <span style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--ht-text-muted);">{{ $agentColor }}</span>
                </div>
            </div>
        </div>

        <button wire:click="addAgent"
            style="margin-top: 1.35rem; background: var(--ht-surface-2); border: 1px solid var(--ht-border-hi); color: #fff; font-size: 0.82rem; font-weight: 600; padding: 0.65rem 1.2rem; border-radius: 8px; cursor: pointer; transition: all 0.2s;"
            onmouseover="this.style.background='rgba(255,255,255,0.08)'; this.style.borderColor='rgba(255,255,255,0.15)'"
            onmouseout="this.style.background='var(--ht-surface-2)'; this.style.borderColor='var(--ht-border-hi)'"
        >
            + Add Agent
        </button>
    </div>
    @endif
</div>