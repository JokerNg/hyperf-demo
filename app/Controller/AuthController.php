<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Model\User;
use App\Request\Auth\LoginRequest;
use App\Request\Auth\RegisterRequest;
use App\Service\JwtService;
use Hyperf\Context\Context;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\Validation\Annotation\Scene;

#[Controller('/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        protected JwtService $jwtService,
    ) {
    }

    #[RequestMapping(path: '/register', methods: ['post']), Scene('register')]
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->input('name'),
            'phone' => $request->input('phone'),
            'password' => password_hash($request->input('password'), PASSWORD_BCRYPT),
        ]);

        return $this->success(['id' => $user->id]);
    }

    #[RequestMapping(path: '/login', methods: ['post']), Scene('login')]
    public function login(LoginRequest $request)
    {
        $user = User::where('phone', $request->input('phone'))->first();

        if (! $user || ! password_verify($request->input('password'), $user->password)) {
            return $this->error('手机号或密码错误', 401);
        }

        return $this->success([
            'access_token' => $this->jwtService->generateAccessToken($user->id),
            'refresh_token' => $this->jwtService->generateRefreshToken($user->id),
            'token_type' => 'Bearer',
            'expires_in' => $this->jwtService->getAccessTtl(),
        ]);
    }

    #[RequestMapping(path: '/refresh', methods: ['post'])]
    public function refresh()
    {
        $refreshToken = $this->extractBearerToken();
        if (! $refreshToken) {
            return $this->error('缺少刷新令牌', 401);
        }

        try {
            $payload = $this->jwtService->parseRefreshToken($refreshToken);
        } catch (\Throwable $e) {
            return $this->error('刷新令牌无效或已过期', 401);
        }

        return $this->success([
            'access_token' => $this->jwtService->generateAccessToken((int) $payload->sub),
            'refresh_token' => $this->jwtService->generateRefreshToken((int) $payload->sub),
            'token_type' => 'Bearer',
            'expires_in' => $this->jwtService->getAccessTtl(),
        ]);
    }

    #[RequestMapping(path: '/logout', methods: ['post'])]
    #[Middleware(AuthMiddleware::class)]
    public function logout()
    {
        $token = $this->extractBearerToken();
        if ($token) {
            $ttl = $this->jwtService->getRemainingTtl($token);
            $this->jwtService->addToBlacklist($token, $ttl);
        }

        return $this->success(null, '注销成功');
    }

    #[RequestMapping(path: '/me', methods: ['get'])]
    #[Middleware(AuthMiddleware::class)]
    public function me()
    {
        $userId = Context::get(AuthMiddleware::AUTH_USER_ID);
        $user = User::find($userId);

        if (! $user) {
            return $this->error('用户不存在', 404);
        }

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
        ]);
    }

    private function extractBearerToken(): ?string
    {
        $authHeader = $this->request->header('Authorization');
        if (! $authHeader || ! str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }
        return substr($authHeader, 7);
    }
}
