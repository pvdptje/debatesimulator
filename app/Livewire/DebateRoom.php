<?php

namespace App\Livewire;

use App\Models\Debate;
use App\Models\Message;
use App\Services\AiService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class DebateRoom extends Component
{
    public Debate $debate;
    public bool $isStreaming = false;
    public ?int $currentAgentId = null;
    public int $consecutiveErrors = 0;

    public function mount(Debate $debate): void
    {
        $this->debate = $debate;
    }

    public function startNextTurn(): void
    {
        if ($this->debate->status !== 'active') {
            return;
        }

        $this->debate->refresh();
        $agents = $this->debate->agents;

        if ($agents->isEmpty()) return;

        $agentIndex = $this->debate->current_round % $agents->count();
        $currentAgent = $agents[$agentIndex];

        $this->currentAgentId = $currentAgent->id;
        $this->isStreaming = true;

        set_time_limit(0);

        $aiService = app(AiService::class);
        $fullContent = '';

        try {
            $response = $aiService->streamResponse(
                $currentAgent,
                $this->debate,
                function (string $token) use (&$fullContent) {
                    $fullContent .= $token;
                    $this->stream(
                        to: 'stream-' . $this->currentAgentId . '-' . $this->debate->current_round,
                        content: $token,
                    );
                }
            );

            $killSwitch = $aiService->detectKillSwitch($response, $currentAgent->provider);

            $message = Message::create([
                'debate_id'         => $this->debate->id,
                'agent_id'          => $currentAgent->id,
                'round'             => $this->debate->current_round,
                'content'           => $fullContent,
                'is_kill_switch'    => $killSwitch !== null,
                'verdict'           => $killSwitch['verdict'] ?? null,
                'kill_switch_reason' => $killSwitch['reason'] ?? null,
                'created_at'        => now(),
            ]);

            $this->consecutiveErrors = 0;

            if ($killSwitch) {
                $this->debate->update(['status' => 'finished']);
                $this->debate->refresh();
                $this->isStreaming = false;
                $this->currentAgentId = null;
                $this->dispatch('debate-ended', messageId: $message->id);
            } else {
                $this->debate->increment('current_round');
                $this->debate->refresh();
                $this->isStreaming = false;
                $this->currentAgentId = null;
                $this->dispatch('turn-complete');
            }
        } catch (\Throwable $e) {
            Log::error('AI stream error', ['error' => $e->getMessage(), 'agent' => $currentAgent->id]);

            $this->consecutiveErrors++;

            Message::create([
                'debate_id'  => $this->debate->id,
                'agent_id'   => $currentAgent->id,
                'round'      => $this->debate->current_round,
                'content'    => '[API Error — skipping turn]',
                'is_kill_switch' => false,
                'created_at' => now(),
            ]);

            $this->debate->increment('current_round');
            $this->debate->refresh();
            $this->isStreaming = false;
            $this->currentAgentId = null;

            if ($this->consecutiveErrors >= 3) {
                $this->debate->update(['status' => 'finished']);
                $this->debate->refresh();
                $this->dispatch('debate-ended', messageId: null);
            } else {
                $this->dispatch('turn-complete');
            }
        }
    }

    public function stopDebate(): void
    {
        $this->debate->update(['status' => 'finished']);
        $this->debate->refresh();
        $this->isStreaming = false;
        $this->currentAgentId = null;
    }

    public function render()
    {
        $this->debate->refresh();
        $messages = $this->debate->messages()->with('agent')->get();

        return view('livewire.debate-room', [
            'debate'   => $this->debate,
            'messages' => $messages,
            'agents'   => $this->debate->agents,
        ])->layout('layouts.app');
    }
}
