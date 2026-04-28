<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'secret' => env('JWT_SECRET', 'hyperf-demo-jwt-secret-key-change-in-production'),
    'access_ttl' => (int) env('JWT_ACCESS_TTL', 7200),
    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 604800),
    'algo' => env('JWT_ALGO', 'HS256'),
    'issuer' => env('JWT_ISSUER', 'hyperf-demo'),
    'blacklist_prefix' => env('JWT_BLACKLIST_PREFIX', 'token_blacklist:'),
];
