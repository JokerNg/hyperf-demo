<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Service\JwtService;
use App\Trait\ResponseTrait;
use Hyperf\Context\Context;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthMiddleware implements MiddlewareInterface
{
    use ResponseTrait;
    public const string AUTH_USER_ID = 'auth_user_id';

    public function __construct(
        protected JwtService $jwtService,
        protected ResponseInterface $response,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): Psr7ResponseInterface
    {
        $token = $this->extractBearerToken($request);
        if ($token === null) {
            return $this->unauthorized('缺少认证令牌');
        }

        if ($this->jwtService->isBlacklisted($token)) {
            return $this->unauthorized('认证令牌已注销');
        }

        try {
            $payload = $this->jwtService->parseAccessToken($token);
        } catch (\Throwable $e) {
            return $this->unauthorized('认证令牌无效或已过期');
        }

        Context::set(self::AUTH_USER_ID, (int) $payload->sub);

        return $handler->handle($request);
    }

    private function extractBearerToken(ServerRequestInterface $request): ?string
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (! str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }
        return substr($authHeader, 7);
    }

    private function unauthorized(string $message): Psr7ResponseInterface
    {
        return $this->error($message, 401, response: $this->response)->withStatus(401);
    }
}
