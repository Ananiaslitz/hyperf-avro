<?php

declare(strict_types=1);

return [
    'schema_path' => env('AVRO_SCHEMA_PATH', BASE_PATH . '/storage/avro'),

    'registry' => [
        'base_url' => env('SCHEMA_REGISTRY_URL', 'http://localhost:8081'),

        'auth' => [
            'key' => env('SCHEMA_REGISTRY_KEY'),
            'secret' => env('SCHEMA_REGISTRY_SECRET'),
            'token' => env('SCHEMA_REGISTRY_TOKEN'),
        ],

        'ssl_verify' => (bool) env('SCHEMA_REGISTRY_SSL_VERIFY', true),
        'connect_timeout' => (int) env('SCHEMA_REGISTRY_CONNECT_TIMEOUT', 5),
        'timeout' => (int) env('SCHEMA_REGISTRY_TIMEOUT', 10),
        'subject_cache_ttl' => (int) env('SCHEMA_REGISTRY_SUBJECT_CACHE_TTL', 300),
    ],
];
