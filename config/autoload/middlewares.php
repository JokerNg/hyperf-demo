<?php

declare(strict_types=1);
use App\Middleware\CleanXssMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\RequestLogMiddleware;
use Hyperf\Validation\Middleware\ValidationMiddleware;

return [
    'http' => [
        RateLimitMiddleware::class,
        RequestLogMiddleware::class,
        CorsMiddleware::class,
        CleanXssMiddleware::class,
        ValidationMiddleware::class,
    ],
];
