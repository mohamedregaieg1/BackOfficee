<?php

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE','OPTIONS'],

    'allowed_origins' => [
        'https://www.aura-auto.tn',
        'https://aura-auto.tn',
        'http://localhost:4200'
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'Accept',
        'Origin',
        'X-Api-Version',
        'X-Api-Key',
    ],

    'exposed_headers' => [
        'Content-Length',
        'X-Response-Time',
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
    ],

    'max_age' => 86400,

    'supports_credentials' => true,

];
