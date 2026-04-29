<?php

declare(strict_types=1);

return [
    // 免认证路径白名单（支持精确匹配和尾部通配符，如 /public/*）
    'public_paths' => [
        '/index/*',
        '/auth/register',
        '/auth/login',
        '/auth/refresh',
        '/favicon.ico',
    ],
];
