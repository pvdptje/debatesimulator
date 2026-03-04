<?php

namespace App\Livewire;

use App\Models\Agent;
use App\Models\Debate;
use App\Services\AiService;
use Livewire\Component;

class DebateSetup extends Component
{
    public string $topic = '';
    public int $minRounds = 5;

    // Current agent form fields
    public string $agentName = '';
    public string $agentRole = '';
    public string $agentStance = '';
    public string $agentProvider = 'anthropic';
    public string $agentModel = '';
    public string $agentColor = '';

    public array $agents = [];
    public bool $generating = false;
    public ?string $generateError = null;

    private const DEFAULT_COLORS = ['#3B82F6', '#EF4444', '#22C55E', '#A855F7'];

    public function mount(): void
    {
        $this->agentProvider = 'anthropic';
        $this->agentModel = $this->getModelsForProvider('anthropic')[0] ?? '';
        $this->agentColor = self::DEFAULT_COLORS[0];
    }

    public function updatedAgentProvider(): void
    {
        $models = $this->getModelsForProvider($this->agentProvider);
        $this->agentModel = $models[0] ?? '';
    }

    public function getProviders(): array
    {
        $providers = config('ai_providers.providers', []);
        return array_map(fn($p) => $p['label'], $providers);
    }

    public function getModelsForProvider(string $provider): array
    {
        return config("ai_providers.providers.$provider.models", []);
    }

    public function getAvailableModelsProperty(): array
    {
        return $this->getModelsForProvider($this->agentProvider);
    }

    public function generateAgents(): void
    {
        // Pick a random topic if none provided
        if (empty(trim($this->topic))) {
            $topics = config('topics', []);
            $this->topic = $topics[array_rand($topics)];
        }

        $this->validate(['topic' => 'required|string|min:3|max:500']);

        $this->generating = true;
        $this->generateError = null;

        try {
            $aiService = app(AiService::class);
            $client = $aiService->makeClient('openai');

            $providerModelLines = '';
            foreach (config('ai_providers.providers', []) as $p => $pConfig) {
                $models = implode(', ', $pConfig['models'] ?? []);
                $providerModelLines .= "  - provider=\"$p\": models are $models\n";
            }

            $response = $client->chat()->create([
                'model' => 'gpt-4o-mini',
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => "You cast debate contestants. Always return valid JSON. No exceptions.",
                    ],
                    [
                        'role'    => 'user',
                        'content' => "Cast 2 to 4 debate contestants for this topic: \"{$this->topic}\"\n\n"
                            . "NAME RULES — this is the most important part:\n"
                            . "- Use realistic, ordinary human names. Think: 'James Whitfield', 'Sarah Okonkwo', 'Chen Wei', 'Maria Santos'.\n"
                            . "- NEVER use names like 'Dr. Vex', 'Professor Orion Bright', 'Rex Carbon', 'Maximilian Byte', 'Oracle Nix' or any other made-up villain/fantasy names.\n"
                            . "- Titles (Dr., Professor, General, Senator etc.) are fine but the surname must sound like a real person.\n\n"
                            . "OTHER RULES:\n"
                            . "- Contestants must have strongly opposing views that will create real conflict\n"
                            . "- Roles should be realistic and relevant to the topic (journalist, economist, surgeon, senator, activist etc.)\n"
                            . "- Each stance: one blunt, opinionated sentence\n"
                            . "- For 'provider' and 'model', choose from the list below. Set 'provider' to exactly one of the provider names (e.g. \"anthropic\") and 'model' to one of that provider's models (e.g. \"claude-haiku-4-5-20251001\"):\n"
                            . $providerModelLines
                            . "- Mix providers across contestants; include at least one contestant with provider=\"deepseek\" and model=\"deepseek-chat\"\n"
                            . "- Assign hex colors from: #3B82F6, #EF4444, #22C55E, #A855F7\n\n"
                            . "Return JSON: { \"contestants\": [ { \"name\", \"role\", \"stance\", \"provider\", \"model\", \"color\" } ] }",
                    ],
                ],
            ]);

            $raw = $response->choices[0]->message->content ?? '';
            \Illuminate\Support\Facades\Log::info('generateAgents raw', ['raw' => $raw]);

            // Strip markdown code fences if present
            $cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
            $cleaned = preg_replace('/\s*```$/', '', $cleaned);

            $data = json_decode($cleaned, true);

            // Accept {contestants:[...]} or a bare array
            if (isset($data['contestants']) && is_array($data['contestants'])) {
                $contestants = $data['contestants'];
            } elseif (is_array($data) && isset($data[0])) {
                $contestants = $data;
            } else {
                $contestants = [];
            }

            if (empty($contestants)) {
                $this->generateError = 'AI returned an empty response. Check logs for details.';
                return;
            }

            $validProviders = array_keys(config('ai_providers.providers', []));

            $this->agents = [];
            foreach (array_slice($contestants, 0, 4) as $i => $c) {
                $rawProvider = $c['provider'] ?? 'deepseek';
                $rawModel    = $c['model'] ?? 'deepseek-chat';

                // AI sometimes returns "provider/model" combined — split it
                if (str_contains($rawProvider, '/')) {
                    [$rawProvider, $rawModel] = explode('/', $rawProvider, 2);
                } elseif (str_contains($rawModel, '/')) {
                    [$rawProvider, $rawModel] = explode('/', $rawModel, 2);
                }

                // Fallback if provider is not in config
                if (!in_array($rawProvider, $validProviders)) {
                    $rawProvider = 'deepseek';
                    $rawModel    = 'deepseek-chat';
                }

                // Fallback if model is not valid for provider
                $validModels = config("ai_providers.providers.$rawProvider.models", []);
                if (!in_array($rawModel, $validModels)) {
                    $rawModel = $validModels[0] ?? 'deepseek-chat';
                }

                $this->agents[] = [
                    'name'       => $c['name'] ?? 'Agent ' . ($i + 1),
                    'role'       => $c['role'] ?? 'Debater',
                    'stance'     => $c['stance'] ?? '',
                    'provider'   => $rawProvider,
                    'model'      => $rawModel,
                    'color'      => $c['color'] ?? self::DEFAULT_COLORS[$i % 4],
                    'turn_order' => $i,
                ];
            }
        } catch (\Throwable $e) {
            $this->generateError = 'Generation failed: ' . $e->getMessage();
        } finally {
            $this->generating = false;
        }
    }

    public function addAgent(): void
    {
        $this->validate([
            'agentName'     => 'required|string|max:100',
            'agentRole'     => 'required|string|max:100',
            'agentStance'   => 'required|string|max:500',
            'agentProvider' => 'required|string',
            'agentModel'    => 'required|string',
        ]);

        if (count($this->agents) >= 4) {
            $this->addError('agentName', 'Maximum 4 agents allowed.');
            return;
        }

        $colorIndex = count($this->agents);
        $color = $this->agentColor ?: self::DEFAULT_COLORS[$colorIndex % count(self::DEFAULT_COLORS)];

        $this->agents[] = [
            'name'       => $this->agentName,
            'role'       => $this->agentRole,
            'stance'     => $this->agentStance,
            'provider'   => $this->agentProvider,
            'model'      => $this->agentModel,
            'color'      => $color,
            'turn_order' => count($this->agents),
        ];

        // Reset form
        $this->agentName = '';
        $this->agentRole = '';
        $this->agentStance = '';
        $this->agentProvider = 'anthropic';
        $this->agentModel = $this->getModelsForProvider('anthropic')[0] ?? '';
        $this->agentColor = self::DEFAULT_COLORS[count($this->agents) % count(self::DEFAULT_COLORS)];
    }

    public function removeAgent(int $index): void
    {
        array_splice($this->agents, $index, 1);
        foreach ($this->agents as $i => &$agent) {
            $agent['turn_order'] = $i;
        }
    }

    public function startDebate(): void
    {
        $this->validate([
            'topic'     => 'required|string|max:500',
            'minRounds' => 'required|integer|min:1|max:50',
        ]);

        if (count($this->agents) < 2) {
            $this->addError('topic', 'You need at least 2 agents to start a debate.');
            return;
        }

        $debate = Debate::create([
            'topic'      => $this->topic,
            'status'     => 'active',
            'min_rounds' => $this->minRounds,
        ]);

        foreach ($this->agents as $agentData) {
            Agent::create(array_merge($agentData, [
                'debate_id'  => $debate->id,
                'created_at' => now(),
            ]));
        }

        $this->redirect(route('debate.room', $debate->id));
    }

    public function render()
    {
        return view('livewire.debate-setup', [
            'providers'       => $this->getProviders(),
            'availableModels' => $this->getAvailableModelsProperty(),
        ])->layout('layouts.app');
    }
}
