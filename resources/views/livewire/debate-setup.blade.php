<div>
    <h1 class="text-3xl font-bold mb-2">New Debate</h1>
    <p class="text-gray-400 mb-8 text-sm italic">"Think of it like Slack, but everyone's a dickhead."</p>

    {{-- Topic + Min Rounds --}}
    <div class="mb-6">
        <div class="flex items-end gap-3 mb-1">
            <label class="block text-sm font-medium text-gray-300">Debate Topic</label>
            <div class="ml-auto flex items-center gap-2">
                <label class="text-xs text-gray-400 whitespace-nowrap">Min rounds before kill switch</label>
                <input wire:model="minRounds" type="number" min="1" max="50"
                    class="w-16 bg-gray-800 border border-gray-700 rounded-lg px-2 py-1.5 text-white text-sm text-center focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <div class="flex gap-3">
            <input
                wire:model="topic"
                type="text"
                placeholder="e.g. Should billionaires exist? (leave blank for a random topic)"
                class="flex-1 bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
            <button
                wire:click="generateAgents"
                wire:loading.attr="disabled"
                wire:target="generateAgents"
                @if($generating) disabled @endif
                class="flex items-center gap-2 bg-purple-600 hover:bg-purple-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-medium px-4 py-3 rounded-lg transition whitespace-nowrap"
            >
                <span wire:loading.remove wire:target="generateAgents">✨ Generate Agents</span>
                <span wire:loading wire:target="generateAgents" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Generating…
                </span>
            </button>
        </div>
        @error('topic') <p class="mt-1 text-red-400 text-sm">{{ $message }}</p> @enderror
        @if($generateError)
        <p class="mt-1 text-red-400 text-sm">{{ $generateError }}</p>
        @endif
        @if(empty($agents))
        <p class="mt-2 text-gray-500 text-xs">Leave topic blank and hit <span class="text-purple-400">Generate Agents</span> for a random topic — or enter your own. AI casts the contestants.</p>
        @endif
    </div>

    {{-- Agent List --}}
    @if(count($agents) > 0)
    <div class="mb-6">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold text-gray-200">Contestants ({{ count($agents) }})</h2>
            <button wire:click="$set('agents', [])" class="text-xs text-gray-500 hover:text-red-400 transition">Clear all</button>
        </div>
        <div class="space-y-2">
            @foreach($agents as $i => $agent)
            <div class="flex items-center gap-3 bg-gray-800 rounded-lg px-4 py-3">
                <div
                    class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                    style="background-color: {{ $agent['color'] }}"
                >
                    {{ strtoupper(substr($agent['name'], 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-white text-sm">{{ $agent['name'] }} <span class="text-gray-400 font-normal">— {{ $agent['role'] }}</span></p>
                    <p class="text-gray-400 text-xs truncate italic">"{{ $agent['stance'] }}"</p>
                    <p class="text-gray-500 text-xs mt-0.5">{{ $agent['provider'] }} / {{ $agent['model'] }}</p>
                </div>
                <button wire:click="removeAgent({{ $i }})" class="text-gray-500 hover:text-red-400 transition ml-2 text-lg leading-none">&times;</button>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Add Agent Form --}}
    @if(count($agents) < 4)
    <div class="bg-gray-900 border border-gray-700 rounded-xl p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4 text-gray-200">Add Agent Manually</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">Name</label>
                <input wire:model="agentName" type="text" placeholder="Dr. Sarah Chen"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('agentName') <p class="mt-1 text-red-400 text-xs">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">Role</label>
                <input wire:model="agentRole" type="text" placeholder="Climate Scientist"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('agentRole') <p class="mt-1 text-red-400 text-xs">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-400 mb-1">Stance</label>
                <input wire:model="agentStance" type="text" placeholder="Renewable energy is the only viable path forward"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('agentStance') <p class="mt-1 text-red-400 text-xs">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">Provider</label>
                <select wire:model.live="agentProvider"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($providers as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">Model</label>
                <select wire:model="agentModel"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($availableModels as $model)
                    <option value="{{ $model }}">{{ $model }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">Color</label>
                <div class="flex items-center gap-2">
                    <input wire:model="agentColor" type="color"
                        class="w-10 h-9 bg-gray-800 border border-gray-700 rounded cursor-pointer">
                    <span class="text-gray-400 text-xs">{{ $agentColor }}</span>
                </div>
            </div>
        </div>
        <button wire:click="addAgent"
            class="mt-4 bg-gray-700 hover:bg-gray-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            + Add Agent
        </button>
    </div>
    @endif

    {{-- Start Debate --}}
    <div class="flex items-center gap-4">
        <button
            wire:click="startDebate"
            @if(count($agents) < 2) disabled @endif
            class="bg-blue-600 hover:bg-blue-500 disabled:opacity-40 disabled:cursor-not-allowed text-white font-semibold px-6 py-3 rounded-lg transition"
        >
            Start Debate →
        </button>
        @if(count($agents) < 2)
        <p class="text-gray-500 text-sm">Add at least 2 agents to start</p>
        @endif
    </div>
</div>
