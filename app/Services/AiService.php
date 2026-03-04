<?php

namespace App\Services;

use Anthropic\Client as AnthropicClient;
use Anthropic\Messages\InputJSONDelta;
use Anthropic\Messages\RawContentBlockDeltaEvent;
use Anthropic\Messages\RawContentBlockStartEvent;
use Anthropic\Messages\TextDelta;
use Anthropic\Messages\ToolUseBlock;
use App\Models\Agent;
use App\Models\Debate;
use Illuminate\Support\Facades\Log;
use OpenAI\Client as OpenAIClient;

class AiService
{
    private const ANTHROPIC_TOOL = [
        'name'         => 'end_debate',
        'description'  => 'Call this if you believe the debate has reached a clear conclusion, is going in circles, or if your argument has been definitively won or lost.',
        'input_schema' => [
            'type'       => 'object',
            'properties' => [
                'reason'  => [
                    'type'        => 'string',
                    'description' => 'Your closing statement explaining why the debate is over.',
                ],
                'verdict' => [
                    'type' => 'string',
                    'enum' => ['I concede', 'Debate resolved', 'No progress possible'],
                ],
            ],
            'required' => ['reason', 'verdict'],
        ],
    ];

    private const OPENAI_TOOL = [
        'type' => 'function',
        'function' => [
            'name'        => 'end_debate',
            'description' => 'Call this if you believe the debate has reached a clear conclusion, is going in circles, or if your argument has been definitively won or lost.',
            'parameters'  => [
                'type'       => 'object',
                'properties' => [
                    'reason'  => [
                        'type'        => 'string',
                        'description' => 'Your closing statement explaining why the debate is over.',
                    ],
                    'verdict' => [
                        'type' => 'string',
                        'enum' => ['I concede', 'Debate resolved', 'No progress possible'],
                    ],
                ],
                'required' => ['reason', 'verdict'],
            ],
        ],
    ];

    public function makeClient(string $provider): OpenAIClient
    {
        $config = config("ai_providers.providers.$provider");

        $factory = \OpenAI::factory()
            ->withApiKey($config['api_key'])
            ->withBaseUri($config['base_url'] ?? 'https://api.openai.com/v1/');

        foreach ($config['extra_headers'] ?? [] as $key => $value) {
            $factory = $factory->withHttpHeader($key, $value);
        }

        return $factory->make();
    }

    private function makeAnthropicClient(): AnthropicClient
    {
        return new AnthropicClient(
            apiKey: config('ai_providers.providers.anthropic.api_key'),
        );
    }

    public function streamResponse(Agent $agent, Debate $debate, callable $onToken): array
    {
        if ($agent->provider === 'anthropic') {
            return $this->streamResponseAnthropic($agent, $debate, $onToken);
        }

        return $this->streamResponseOpenAI($agent, $debate, $onToken);
    }

    private function streamResponseAnthropic(Agent $agent, Debate $debate, callable $onToken): array
    {
        $client = $this->makeAnthropicClient();
        [$systemPrompt, $messages] = $this->buildMessages($agent, $debate);

        $stream = $client->messages->createStream(
            model: $agent->model,
            maxTokens: 1024,
            system: $systemPrompt,
            messages: $messages,
            tools: [self::ANTHROPIC_TOOL],
        );

        $fullContent = '';
        $toolCalls = [];
        $currentToolIndex = -1;

        foreach ($stream as $event) {
            if ($event instanceof RawContentBlockStartEvent && $event->contentBlock instanceof ToolUseBlock) {
                $currentToolIndex++;
                $toolCalls[$currentToolIndex] = [
                    'id'       => $event->contentBlock->id,
                    'function' => ['name' => $event->contentBlock->name, 'arguments' => ''],
                ];
            } elseif ($event instanceof RawContentBlockDeltaEvent) {
                if ($event->delta instanceof TextDelta) {
                    $fullContent .= $event->delta->text;
                    $onToken($event->delta->text);
                } elseif ($event->delta instanceof InputJSONDelta && $currentToolIndex >= 0) {
                    $toolCalls[$currentToolIndex]['function']['arguments'] .= $event->delta->partialJSON;
                }
            }
        }

        return ['content' => $fullContent, 'tool_calls' => array_values($toolCalls)];
    }

    private function streamResponseOpenAI(Agent $agent, Debate $debate, callable $onToken): array
    {
        $client = $this->makeClient($agent->provider);
        [$systemPrompt, $messages] = $this->buildMessages($agent, $debate);

        $allMessages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $messages
        );

        $stream = $client->chat()->createStreamed([
            'model'    => $agent->model,
            'messages' => $allMessages,
            'tools'    => [self::OPENAI_TOOL],
        ]);

        $fullContent = '';
        $toolCallsAccumulator = [];

        foreach ($stream as $response) {
            $choice = $response->choices[0] ?? null;
            if (!$choice) continue;

            $delta = $choice->delta;

            if (!empty($delta->content)) {
                $fullContent .= $delta->content;
                $onToken($delta->content);
            }

            if (!empty($delta->toolCalls)) {
                foreach ($delta->toolCalls as $toolCallDelta) {
                    $index = $toolCallDelta->index ?? 0;
                    if (!isset($toolCallsAccumulator[$index])) {
                        $toolCallsAccumulator[$index] = [
                            'id'       => $toolCallDelta->id ?? '',
                            'type'     => $toolCallDelta->type ?? 'function',
                            'function' => ['name' => '', 'arguments' => ''],
                        ];
                    }
                    if (!empty($toolCallDelta->function->name)) {
                        $toolCallsAccumulator[$index]['function']['name'] .= $toolCallDelta->function->name;
                    }
                    if (!empty($toolCallDelta->function->arguments)) {
                        $toolCallsAccumulator[$index]['function']['arguments'] .= $toolCallDelta->function->arguments;
                    }
                }
            }
        }

        return ['content' => $fullContent, 'tool_calls' => array_values($toolCallsAccumulator)];
    }

    public function detectKillSwitch(mixed $response, string $provider): ?array
    {
        foreach ($response['tool_calls'] ?? [] as $toolCall) {
            if (($toolCall['function']['name'] ?? null) !== 'end_debate') continue;

            $args = json_decode($toolCall['function']['arguments'] ?? '{}', true);
            return [
                'verdict' => $args['verdict'] ?? 'Debate resolved',
                'reason'  => $args['reason'] ?? '',
            ];
        }

        return null;
    }

    /**
     * Returns [$systemPrompt, $chatMessages] — system is separate so Anthropic can use it as a top-level param.
     */
    private function buildMessages(Agent $agent, Debate $debate): array
    {
        $minRounds = $debate->min_rounds ?? 5;
        $killSwitchLine = $debate->current_round < $minRounds
            ? "You CANNOT call end_debate yet. The debate requires at least {$minRounds} rounds and only {$debate->current_round} have completed. Keep attacking."
            : "You may call end_debate if you've clearly won, the opposition has embarrassed themselves, or continuing is beneath you.";

        $mode = $debate->mode ?? 'heated';
        $modePrompt = config("debate_modes.{$mode}.prompt", config('debate_modes.heated.prompt'));

        $systemPrompt = "You are {$agent->name}, a {$agent->role}.\n"
            . "You firmly believe: {$agent->stance}\n"
            . "The debate topic is: {$debate->topic}\n"
            . $modePrompt . "\n"
            . $killSwitchLine;

        $history = $debate->messages()
            ->with('agent')
            ->latest('id')
            ->take(30)
            ->get()
            ->reverse()
            ->values();

        $merged = [];
        foreach ($history as $msg) {
            $isOwn = $msg->agent_id === $agent->id;
            $content = $msg->content ?: $msg->kill_switch_reason ?: '';

            if ($isOwn) {
                $merged[] = ['role' => 'assistant', 'content' => $content];
            } else {
                $prefix = "[{$msg->agent->name}]: ";
                if (!empty($merged) && $merged[count($merged) - 1]['role'] === 'user') {
                    $merged[count($merged) - 1]['content'] .= "\n\n" . $prefix . $content;
                } else {
                    $merged[] = ['role' => 'user', 'content' => $prefix . $content];
                }
            }
        }

        $merged[] = ['role' => 'user', 'content' => 'It is now your turn. Respond.'];

        return [$systemPrompt, $merged];
    }
}
