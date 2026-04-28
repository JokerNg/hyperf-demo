<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    // 是否启用限流
    'enable' => (bool) env('RATE_LIMIT_ENABLE', true),

    // 时间窗口内允许的最大请求数
    'max_requests' => (int) env('RATE_LIMIT_MAX_REQUESTS', 100),

    // 时间窗口大小（秒）
    'window_seconds' => (int) env('RATE_LIMIT_WINDOW_SECONDS', 60),

    // IP 白名单（支持精确 IP 或 CIDR，如 192.168.1.0/24）
    'whitelist' => [],

    // Redis key 前缀
    'key_prefix' => 'rate_limit:',

    // 触发限流时返回的 HTTP 状态码
    'response_code' => 429,

    // 触发限流时返回的错误信息
    'response_message' => '请求过于频繁，请稍后再试',
];
