<?php

declare(strict_types=1);

return [
    'gateway_url' => env('BARTENDER_GATEWAY_URL', 'http://localhost:3001'),

    'inbound_mode' => env('BARTENDER_INBOUND_MODE', 'http'),

    'rabbitmq' => [
        'dsn' => env('BARTENDER_RABBITMQ_DSN', 'amqp://guest:guest@localhost:5672/'),
        'exchange' => env('BARTENDER_RABBITMQ_EXCHANGE', 'channel-outbound'),
        'queue' => env('BARTENDER_RABBITMQ_QUEUE', 'bartender.outbound.tap'),
    ],

    'meta' => [
        'app_secret' => env('BARTENDER_META_APP_SECRET', ''),
    ],

    'evolution' => [
        'api_key' => env('BARTENDER_EVOLUTION_API_KEY', ''),
    ],

    'target_org_id' => env('BARTENDER_TARGET_ORG_ID', ''),

    'ai' => [
        'provider' => env('BARTENDER_AI_PROVIDER', 'openai'),
        'model' => env('BARTENDER_AI_MODEL', 'gpt-4o-mini'),
        'fallback_provider' => env('BARTENDER_AI_FALLBACK_PROVIDER', 'anthropic'),
        'fallback_model' => env('BARTENDER_AI_FALLBACK_MODEL', 'claude-haiku-3-5-20241022'),
        'max_tokens' => (int) env('BARTENDER_AI_MAX_TOKENS', 400),
        'max_turns' => (int) env('BARTENDER_AI_MAX_TURNS', 50),
        'max_conversations_per_day' => (int) env('BARTENDER_AI_MAX_CONVERSATIONS_PER_DAY', 100),
    ],

    'timing' => [
        'mode' => env('BARTENDER_TIMING_MODE', 'realistic'), // realistic | fast
        'realistic' => [
            'median_ms' => (int) env('BARTENDER_TIMING_MEDIAN_MS', 75_000),
            'sigma' => (float) env('BARTENDER_TIMING_SIGMA', 0.6),
            'max_ms' => (int) env('BARTENDER_TIMING_MAX_MS', 480_000),
            'read_ms_per_char' => (int) env('BARTENDER_TIMING_READ_MS_PER_CHAR', 15),
            'read_cap_ms' => (int) env('BARTENDER_TIMING_READ_CAP_MS', 20_000),
        ],
        'fast' => [
            'min_ms' => (int) env('BARTENDER_TIMING_FAST_MIN_MS', 1_000),
            'max_ms' => (int) env('BARTENDER_TIMING_FAST_MAX_MS', 5_000),
        ],
        'inactivity_timeout_ms' => (int) env('BARTENDER_INACTIVITY_TIMEOUT_MS', 120_000),
    ],
];
