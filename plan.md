# 🗣️ Debate Simulator — Full Project Specification

> This document is a complete build spec for a Laravel + Livewire debate simulator app. Read it fully before writing any code.

> **Tagline: "Think of it like Slack, but everyone's a dickhead."**
> This is the vibe. Agents should be opinionated, combative, and entertaining. They're not here to find common ground — they're here to win.

---

## Overview

A single-user, chatroom-style debate simulator where the user configures a topic, adds AI-powered agents (each with a name, role, stance, and their own AI model/provider), and watches them argue in real-time. Debates run indefinitely in round-robin order until an agent triggers a "kill switch" tool call to end it. All debates and messages are persisted to the database for future replay.

---

## Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 11 / PHP 8.2+ |
| Frontend | Livewire 3 + `wire:stream` for real-time streaming |
| Styling | TailwindCSS (via Vite, default Laravel setup) |
| AI Client | `openai-php/client` package (used for ALL providers) |
| AI Providers | Anthropic, OpenAI, DeepSeek |
| Auth | None — single-user, no authentication |
| Database | MySQL or SQLite |

---

## Database Schema

### `debates`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| topic | string | The debate topic/question |
| status | enum | `setup`, `active`, `finished` |
| current_round | integer | Default 0 |
| created_at | timestamp | |
| updated_at | timestamp | |

### `agents`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| debate_id | FK → debates | |
| name | string | e.g. "Dr. Sarah Chen" |
| role | string | e.g. "Climate Scientist" |
| stance | string | e.g. "Renewable energy is the only viable path forward" |
| color | string | Hex color for UI avatar, e.g. `#3B82F6` |
| turn_order | integer | 0-indexed order for round-robin |
| provider | string | `anthropic`, `openai`, `deepseek` |
| model | string | e.g. `claude-opus-4-5`, `gpt-4o`, `deepseek-chat` |
| created_at | timestamp | |

### `messages`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| debate_id | FK → debates | |
| agent_id | FK → agents | |
| round | integer | Which round this message belongs to |
| content | text | Full message content (may be empty if agent only fired kill switch) |
| is_kill_switch | boolean | Default false |
| verdict | string | nullable — one of: `I concede`, `Debate resolved`, `No progress possible` |
| kill_switch_reason | text | nullable — agent's closing statement |
| created_at | timestamp | |

---

## Provider Configuration

### `config/ai_providers.php`

```php
return [
    'providers' => [
        'anthropic' => [
            'label'         => 'Anthropic',
            'base_url'      => 'https://api.anthropic.com/v1/',
            'api_key'       => env('ANTHROPIC_API_KEY'),
            'extra_headers' => [
                'anthropic-version' => '2023-06-01',
            ],
            'models'        => [
                'claude-opus-4-5',
                'claude-sonnet-4-5',
                'claude-haiku-4-5',
            ],
        ],
        'deepseek' => [
            'label'         => 'DeepSeek',
            'base_url'      => 'https://api.deepseek.com/v1/',
            'api_key'       => env('DEEPSEEK_API_KEY'),
            'extra_headers' => [],
            'models'        => [
                'deepseek-chat',
                'deepseek-reasoner',
            ],
        ],
        'openai' => [
            'label'         => 'OpenAI',
            'base_url'      => null, // uses openai-php default
            'api_key'       => env('OPENAI_API_KEY'),
            'extra_headers' => [],
            'models'        => [
                'gpt-4o',
                'gpt-4o-mini',
                'o3-mini',
            ],
        ],
    ],
];
```

All three API keys live in `.env`. The config file maps them to providers, available models, and any provider-specific headers.

> **Note on Anthropic headers**: Anthropic's OpenAI-compatible endpoint requires the `anthropic-version` header. The `openai-php/client` factory supports this via `->withHeader()` — see `makeClient()` below.

---

## File Structure

```
app/
  Livewire/
    DebateSetup.php         ← Create debate + agents form
    DebateRoom.php          ← Main debate UI with streaming
    DebateHistory.php       ← List of past debates
  Services/
    AiService.php           ← Generic streaming + kill switch detection
  Models/
    Debate.php
    Agent.php
    Message.php

resources/views/
  livewire/
    debate-setup.blade.php
    debate-room.blade.php
    debate-history.blade.php

config/
  ai_providers.php

database/
  migrations/
    create_debates_table.php
    create_agents_table.php
    create_messages_table.php
  seeders/
    DebateSeeder.php        ← Sample debate + 2 agents for fast local testing

routes/
  web.php                   ← 3 routes: setup, room, history
```

---

## Pages & Components

### 1. Debate Setup (`/`)

- Input field for the **debate topic**
- **Add agent** form with fields:
  - Name
  - Role
  - Stance
  - Provider (dropdown: Anthropic / OpenAI / DeepSeek)
  - Model (dropdown: dynamically populated based on selected provider via Livewire)
  - Color picker or preset color selection (defaults from palette below)
- List of added agents (min 2, suggested max 4), with ability to remove
- "Start Debate" button → creates `Debate` + `Agent` records, redirects to debate room

### 2. Debate Room (`/debate/{id}`)

- Chatroom-style UI — messages appear in a vertical feed
- Each message shows:
  - Agent color avatar with initials
  - Agent name + role
  - Message content (streams in live via `wire:stream`)
  - Round number badge
- Round counter displayed at top (e.g. "Round 3")
- Streaming indicator ("thinking" spinner) while waiting for first token from current agent
- **Auto-scroll**: chat container scrolls to bottom as new tokens arrive
- When kill switch fires: full-screen verdict banner appears showing:
  - Which agent ended it
  - The verdict type (`I concede` / `Debate resolved` / `No progress possible`)
  - The agent's closing reason (from `kill_switch_reason`)
  - If the agent produced no message content before firing the kill switch, the `kill_switch_reason` is also displayed as the message bubble content
- "Stop Debate" manual button (marks as finished without verdict)
- Read-only mode when `debate.status === 'finished'`

### 3. Debate History (`/history`)

- List of all past debates, newest first
- Shows: topic, number of agents, round count, status, created date
- Click any debate → opens debate room in **read-only mode** (full transcript)

---

## AI Service — `AiService.php`

### Method Signatures

```php
// Build a provider-specific OpenAI client
public function makeClient(string $provider): \OpenAI\Client

// Stream one agent turn. Calls $onToken for each token as it arrives.
// Returns the raw completed response object for kill switch detection.
public function streamResponse(Agent $agent, Debate $debate, callable $onToken): mixed

// Returns null if no kill switch fired, or ['verdict' => '...', 'reason' => '...'] if triggered.
public function detectKillSwitch(mixed $response, string $provider): ?array
```

### Client Factory

```php
public function makeClient(string $provider): \OpenAI\Client
{
    $config = config("ai_providers.providers.$provider");

    $factory = \OpenAI::factory()
        ->withApiKey($config['api_key'])
        ->withBaseUri($config['base_url'] ?? 'https://api.openai.com/v1/');

    foreach ($config['extra_headers'] ?? [] as $key => $value) {
        $factory = $factory->withHeader($key, $value);
    }

    return $factory->make();
}
```

### Kill Switch Tool Definition

This tool is passed with every API call, for every provider:

```php
[
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
]
```

### Kill Switch Detection — Provider Differences ⚠️

This is the most important cross-provider difference to handle carefully:

| Provider | How tool call is signaled |
|---|---|
| OpenAI / DeepSeek | `finish_reason === "tool_calls"` → parse `response.choices[0].message.tool_calls[]` |
| Anthropic (via OpenAI-compatible endpoint) | `finish_reason === "tool_calls"` should also work — but verify; fallback: check `stop_reason === "tool_use"` and parse `content[]` blocks for `type: "tool_use"` |

`detectKillSwitch()` branches internally per provider. The rest of the app calls this single method and never needs to know about provider differences.

### Agent System Prompt

```
You are {name}, a {role}.
You firmly believe: {stance}
The debate topic is: {topic}
You are in a live, heated debate. You are opinionated, combative, and here to WIN — not to find common ground. Be blunt, dismissive of opposing views, and go straight for the weakest point in the last argument. Keep your response to 3-5 sentences. Do not hedge, do not be polite.
You may call end_debate if you've clearly won, if the opposition has embarrassed themselves beyond recovery, or if continuing would be beneath you.
```

### Conversation History Format

Each API call is structured as:

```
[ system ]  ← agent's persona + instructions
[ user ]    ← other agents' message(s)
[ assistant ] ← this agent's own response
[ user ]    ← other agents' message(s)
[ assistant ] ← this agent's own response
...
[ user ]    ← "It is now your turn. Respond." (final trigger)
```

**Rules for building the messages array:**

1. **System message** — always first, contains the agent system prompt (persona, stance, topic, instructions).
2. **History** — iterate the last N saved messages in order:
   - If the message belongs to **this agent** → `assistant` role, plain content (no name prefix).
   - If the message belongs to **any other agent** → `user` role, content prefixed with `[AgentName]: `.
3. **Consecutive "other agent" messages must be merged into one `user` block** — providers require strictly alternating `user`/`assistant`. If two or more non-self agents spoke back-to-back (common in 3+ agent debates), concatenate them into a single `user` message separated by a blank line.
4. **Final trigger** — append a closing `user` message: `"It is now your turn. Respond."` This ensures the model knows to reply even if the last history entry was already a `user` message.

**Example** (Agent A's turn, 3-agent debate):

```php
[
    ['role' => 'system',    'content' => 'You are Dr. Sarah Chen, a Climate Scientist...'],
    ['role' => 'user',      'content' => "[Marcus Holt]: Solar is a fantasy.\n\n[Gov. Reid]: The economy depends on fossil fuels."],  // ← two others merged
    ['role' => 'assistant', 'content' => 'That is embarrassingly short-sighted. The data is unambiguous.'],
    ['role' => 'user',      'content' => '[Marcus Holt]: Show me one grid that runs purely on renewables.'],
    ['role' => 'assistant', 'content' => 'Denmark. Portugal. Next question.'],
    ['role' => 'user',      'content' => "[Gov. Reid]: Those are tiny countries.\n\n[Marcus Holt]: Apples to oranges."],  // ← merged
    ['role' => 'user',      'content' => 'It is now your turn. Respond.'],
]
```

**Context window limit**: Send at most the last **30 messages** from the DB before building this array. Drop oldest first. This prevents token limit errors on long debates.

---

## Debate Flow (Round-Robin)

1. User starts debate → `debate.status` set to `active`
2. Livewire `DebateRoom` component initiates first turn
3. Current agent is determined by: `current_round % agent_count` → `turn_order`
4. `AiService::streamResponse()` is called
5. Response streams token-by-token into the chat via `wire:stream`
6. Once stream completes:
   - `detectKillSwitch()` is called on the full response
   - If kill switch → save message with `is_kill_switch: true` (content may be empty), set `debate.status = finished`, emit event to show verdict banner
   - If no kill switch → save message normally, increment `current_round`, trigger next agent's turn automatically
7. Loop continues indefinitely until kill switch or manual stop

**On API error**: If `streamResponse()` throws, display an error bubble in the chat (e.g. "[API Error — skipping turn]"), log the exception, and advance to the next agent's turn. Do not crash the debate.

---

## Key Implementation Notes

- **wire:stream**: Use Livewire 3's `wire:stream` to pipe streamed tokens directly into the chat bubble as they arrive. Each agent's response should stream into its own message container.
- **PHP timeout**: The debate loop runs as a long-lived HTTP request. Call `set_time_limit(0)` at the start of the streaming action in `DebateRoom` to prevent PHP from timing out mid-debate.
- **No auth**: No `Auth` middleware, no user_id on any model.
- **Agent colors — default palette**: `['#3B82F6', '#EF4444', '#22C55E', '#A855F7']` (blue, red, green, purple). Assign sequentially if the user doesn't pick a color.
- **Minimum agents**: Enforce at least 2 agents before allowing debate to start.
- **Message saving**: Save the full message content to DB only after the stream completes (not token by token).
- **Kill switch with no content**: An agent may fire `end_debate` without producing any message text. In this case, save the message with empty `content` and display the `kill_switch_reason` as the bubble text in the UI.
- **openai-php package**: `composer require openai-php/client` — this single package handles all three providers via base URL + header switching.

---

## Environment Variables (`.env`)

```
ANTHROPIC_API_KEY=sk-ant-...
OPENAI_API_KEY=sk-...
DEEPSEEK_API_KEY=sk-...
```

---

## Build Order (Recommended)

1. **Migrations** — create all three tables
2. **Models** — `Debate`, `Agent`, `Message` with relationships
3. **`config/ai_providers.php`** — provider registry
4. **`AiService`** — streaming client factory + kill switch detection
5. **`DebateSeeder`** — seed a sample debate with 2 agents for fast local testing
6. **`DebateSetup` Livewire component** — topic + agent creation form with dynamic model dropdown
7. **`DebateRoom` Livewire component** — the main event: round-robin streaming + kill switch UI
8. **`DebateHistory` Livewire component** — list + read-only replay
9. **Routes + layouts** — wire it all together

---

## Summary

| Decision | Choice |
|---|---|
| Debate flow | Round-robin, infinite rounds |
| UI style | Chatroom / messaging app |
| Auth | None, single-user |
| Providers | Anthropic, OpenAI, DeepSeek |
| AI client package | `openai-php/client` for all providers |
| Kill switch | Any single agent can end unilaterally |
| Kill switch mechanism | Tool call (`end_debate`) with verdict + reason |
| Kill switch parsing | Provider-aware, abstracted in `AiService` |
| Agent model selection | Each agent independently picks provider + model |
| Conversation history | `user` role for all, prefixed with `[AgentName]:`, last 30 messages max |
| DB persistence | Full debate + agents + messages stored |
| History | Read-only replay of any past debate |
| Error handling | API failures log + show error bubble, debate continues |
| PHP timeout | `set_time_limit(0)` in streaming action |
