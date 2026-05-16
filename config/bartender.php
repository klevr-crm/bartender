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
];
