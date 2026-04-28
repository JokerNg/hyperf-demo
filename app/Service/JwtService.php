<?php

declare(strict_types=1);

namespace App\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\Redis;
use stdClass;

class JwtService
{
    protected string $secret;

    protected string $algo;

    protected string $issuer;

    protected int $accessTtl;

    protected int $refreshTtl;

    protected string $blacklistPrefix;

    public function __construct(
        ConfigInterface $config,
        protected Redis $redis,
    ) {
        $this->secret = $config->get('jwt.secret', '');
        $this->algo = $config->get('jwt.algo', 'HS256');
        $this->issuer = $config->get('jwt.issuer', 'hyperf-demo');
        $this->accessTtl = (int) $config->get('jwt.access_ttl', 7200);
        $this->refreshTtl = (int) $config->get('jwt.refresh_ttl', 604800);
        $this->blacklistPrefix = (string) $config->get('jwt.blacklist_prefix', 'token_blacklist:');
    }

    public function getAccessTtl(): int
    {
        return $this->accessTtl;
    }

    public function getRemainingTtl(string $token): int
    {
        $payload = $this->parseToken($token);
        return max(0, (int) $payload->exp - time());
    }

    public function generateAccessToken(int $userId): string
    {
        return $this->generateToken($userId, 'access', $this->accessTtl);
    }

    public function generateRefreshToken(int $userId): string
    {
        return $this->generateToken($userId, 'refresh', $this->refreshTtl);
    }

    public function parseAccessToken(string $token): stdClass
    {
        $payload = $this->parseToken($token);
        if ($payload->type !== 'access') {
            throw new \InvalidArgumentException('Invalid token type');
        }
        return $payload;
    }

    public function parseRefreshToken(string $token): stdClass
    {
        $payload = $this->parseToken($token);
        if ($payload->type !== 'refresh') {
            throw new \InvalidArgumentException('Invalid token type');
        }
        return $payload;
    }

    private function generateToken(int $userId, string $type, int $ttl): string
    {
        $now = time();
        $payload = [
            'iss' => $this->issuer,
            'iat' => $now,
            'exp' => $now + $ttl,
            'sub' => $userId,
            'type' => $type,
            'jti' => uniqid('', true),
        ];

        return JWT::encode($payload, $this->secret, $this->algo);
    }

    private function parseToken(string $token): stdClass
    {
        return JWT::decode($token, new Key($this->secret, $this->algo));
    }

    public function addToBlacklist(string $token, int $ttl): void
    {
        $this->redis->setex($this->blacklistPrefix . md5($token), max(1, $ttl), '1');
    }

    public function isBlacklisted(string $token): bool
    {
        return (bool) $this->redis->exists($this->blacklistPrefix . md5($token));
    }
}
