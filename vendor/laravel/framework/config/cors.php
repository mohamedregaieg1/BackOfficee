<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => ['http://localhost:4200'],
    'allowed_origins_patterns' => ['*'],
    'allowed_headers' => ['Content-Type', 'Authorization','X-Auth-Token','Origin','Accept-Encoding','X-Login-Origin', 'X-Requested-With', 'Access-Control-Allow-Origin'],
    'exposed_headers' => ['Content-Disposition'],
    'max_age' => 100,
    'supports_credentials' => true,
];

