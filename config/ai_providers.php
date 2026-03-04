<?php

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
                'claude-opus-4-6',
                'claude-sonnet-4-6',
                'claude-haiku-4-5-20251001',
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
            'base_url'      => null,
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
