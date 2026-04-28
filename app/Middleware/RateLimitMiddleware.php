<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Service\JwtService;
use App\Trait\ResponseTrait;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Redis\Redis;
use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RateLimitMiddleware implements MiddlewareInterface
{
    use ResponseTrait;
    protected bool $enable;

    protected int $maxRequests;

    protected int $windowSeconds;

    protected array $whitelist;

    protected string $keyPrefix;

    protected int $responseCode;

    protected string $responseMessage;

    public function __construct(
        protected Redis $redis,
        protected ConfigInterface $config,
        protected ResponseInterface $response,
        protected JwtService $jwtService,
    ) {
        $this->enable = (bool) $this->config->get('rate_limit.enable', true);
        $this->maxRequests = (int) $this->config->get('rate_limit.max_requests', 100);
        $this->windowSeconds = (int) $this->config->get('rate_limit.window_seconds', 60);
        $this->whitelist = (array) $this->config->get('rate_limit.whitelist', []);
        $this->keyPrefix = (string) $this->config->get('rate_limit.key_prefix', 'rate_limit:');
        $this->responseCode = (int) $this->config->get('rate_limit.response_code', 429);
        $this->responseMessage = (string) $this->config->get('rate_limit.response_message', 'Too Many Requests');
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): Psr7ResponseInterface
    {
        if (! $this->enable) {
            return $handler->handle($request);
        }

        $userId = $this->resolveUserId($request);

        if ($userId !== null) {
            $key = $this->keyPrefix . 'user:' . $userId;
        } else {
            $clientIp = $this->getClientIp($request);
            if ($this->isWhitelisted($clientIp)) {
                return $handler->handle($request);
            }
            $key = $this->keyPrefix . 'ip:' . $clientIp;
        }

        if (! $this->allowRequest($key)) {
            return $this->error($this->responseMessage, $this->responseCode, response: $this->response)
                ->withStatus($this->responseCode);
        }

        return $handler->handle($request);
    }

    private function allowRequest(string $key): bool
    {
        $window = $this->windowSeconds;
        $now = microtime(true);
        $maxRequests = $this->maxRequests;
        $member = uniqid('', true);

        $script = <<<'LUA'
local key = KEYS[1]
local window = tonumber(ARGV[1])
local now = tonumber(ARGV[2])
local maxRequests = tonumber(ARGV[3])
local member = ARGV[4]

redis.call('ZREMRANGEBYSCORE', key, '-inf', now - window)
local count = redis.call('ZCARD', key)

if count < maxRequests then
    redis.call('ZADD', key, now, member)
    redis.call('EXPIRE', key, math.ceil(window))
    return 1
else
    return 0
end
LUA;

        $result = $this->redis->eval($script, [$key, $window, $now, $maxRequests, $member], 1);

        return (int) $result === 1;
    }

    private function resolveUserId(ServerRequestInterface $request): ?int
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (! str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = substr($authHeader, 7);
        try {
            $payload = $this->jwtService->parseAccessToken($token);
            return (int) $payload->sub;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function getClientIp(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();
        $headers = $request->getHeaders();

        if (! empty($headers['x-forwarded-for'][0])) {
            return $headers['x-forwarded-for'][0];
        }

        if (! empty($headers['x-real-ip'][0])) {
            return $headers['x-real-ip'][0];
        }

        return $serverParams['remote_addr'] ?? 'unknown';
    }

    private function isWhitelisted(string $ip): bool
    {
        foreach ($this->whitelist as $item) {
            if (str_contains($item, '/')) {
                if ($this->ipInCidr($ip, $item)) {
                    return true;
                }
            } elseif ($ip === $item) {
                return true;
            }
        }

        return false;
    }

    private function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);
        $mask = (int) $mask;

        $ipBits = ip2long($ip);
        $subnetBits = ip2long($subnet);

        if ($ipBits === false || $subnetBits === false) {
            return false;
        }

        $maskBits = -1 << (32 - $mask);

        return ($ipBits & $maskBits) === ($subnetBits & $maskBits);
    }
}
