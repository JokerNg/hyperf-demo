<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exception\AppException;
use Hyperf\Context\Context;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class RequestLogMiddleware implements MiddlewareInterface
{
    /**
     * 不需要记录错误日志的异常类列表
     */
    protected array $ignoreExceptions = [
        HttpException::class,
        AppException::class,
        ValidationException::class,
    ];

    public function __construct(protected LoggerInterface $logger)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $startTime = microtime(true);

        $method = $request->getMethod();
        $uri = $request->getUri()->getPath();
        $query = $request->getUri()->getQuery();
        $clientIp = $this->getClientIp($request);
        $requestId = $this->getRequestId();
        $body = $this->resolveBody($request);

        // 记录请求进入
        $this->logger->info('Request started', [
            'request_id' => $requestId,
            'method' => $method,
            'uri' => $uri,
            'query' => $query ?: null,
            'body' => $body,
            'client_ip' => $clientIp,
        ]);

        try {
            $response = $handler->handle($request);
            $this->logCompletion($startTime, $requestId, $method, $uri, $clientIp, $response->getStatusCode());
            return $response;
        } catch (\Throwable $e) {
            if (! $this->shouldIgnore($e)) {
                $this->logger->error('Request exception', [
                    'request_id' => $requestId,
                    'method' => $method,
                    'uri' => $uri,
                    'exception_class' => $e::class,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            $this->logCompletion($startTime, $requestId, $method, $uri, $clientIp, $this->resolveStatusCode($e));
            throw $e;
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

    private function getRequestId(): string
    {
        return Context::getOrSet('request_id', fn () => uniqid('', true));
    }

    private function logCompletion(float $startTime, string $requestId, string $method, string $uri, string $clientIp, int $statusCode): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $logData = [
            'request_id' => $requestId,
            'method' => $method,
            'uri' => $uri,
            'status_code' => $statusCode,
            'duration_ms' => $duration,
            'client_ip' => $clientIp,
        ];

        if ($statusCode >= 500) {
            $this->logger->error('Request completed with server error', $logData);
        } elseif ($statusCode >= 400) {
            $this->logger->warning('Request completed with client error', $logData);
        } else {
            $this->logger->info('Request completed', $logData);
        }
    }

    private function resolveStatusCode(\Throwable $e): int
    {
        if ($e instanceof HttpException) {
            return $e->getStatusCode();
        }
        if ($e instanceof ValidationException) {
            return $e->status;
        }
        if ($e instanceof AppException) {
            return 200;
        }
        return 500;
    }

    private function resolveBody(ServerRequestInterface $request): array|string|null
    {
        $contentType = strtolower($request->getHeaderLine('content-type'));

        if (str_contains($contentType, 'multipart/form-data')) {
            return $this->resolveMultipartBody($request);
        }

        if (str_contains($contentType, 'application/json')) {
            return $this->resolveJsonBody($request);
        }

        if ($this->isBinaryContentType($contentType)) {
            return $this->resolveBinaryBody($request, $contentType);
        }

        return $this->resolveRawBody($request);
    }

    private function resolveMultipartBody(ServerRequestInterface $request): array
    {
        $fileInfos = [];
        foreach ($request->getUploadedFiles() as $fieldName => $file) {
            $files = is_array($file) ? $file : [$file];
            foreach ($files as $f) {
                $fileInfos[] = [
                    'field' => $fieldName,
                    'filename' => $f->getClientFilename(),
                    'size' => $f->getSize(),
                ];
            }
        }

        return [
            'type' => 'multipart',
            'fields' => $request->getParsedBody() ?? [],
            'files' => $fileInfos,
        ];
    }

    private function resolveJsonBody(ServerRequestInterface $request): array
    {
        $parsed = $request->getParsedBody();
        if ($parsed !== null) {
            return $parsed;
        }

        $raw = $request->getBody()->getContents();
        return json_decode($raw, true) ?? ['_raw' => $raw];
    }

    private function resolveBinaryBody(ServerRequestInterface $request, string $contentType): array
    {
        $raw = $request->getBody()->getContents();
        $length = strlen($raw);

        // 提取前 8 个字节的十六进制作为文件头预览（可用于识别文件类型）
        $preview = $length > 0 ? bin2hex(substr($raw, 0, min(8, $length))) : null;

        return [
            'type' => 'binary',
            'content_type' => $contentType,
            'length' => $length,
            'hex_preview' => $preview,
        ];
    }

    private function resolveRawBody(ServerRequestInterface $request): array|string|null
    {
        $parsed = $request->getParsedBody();
        if ($parsed !== null) {
            return $parsed;
        }

        $raw = $request->getBody()->getContents();
        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if ($decoded !== null) {
            return $decoded;
        }

        return [
            'type' => 'raw',
            'length' => strlen($raw),
            'content' => strlen($raw) > 2048 ? substr($raw, 0, 2048) . '...' : $raw,
        ];
    }

    private function isBinaryContentType(string $contentType): bool
    {
        $binaryTypes = [
            'application/octet-stream',
            'application/pdf',
            'application/zip',
            'application/gzip',
            'image/',
            'audio/',
            'video/',
            'font/',
        ];

        foreach ($binaryTypes as $type) {
            if (str_contains($contentType, $type)) {
                return true;
            }
        }

        return false;
    }

    private function shouldIgnore(\Throwable $e): bool
    {
        foreach ($this->ignoreExceptions as $ignoreClass) {
            if ($e instanceof $ignoreClass) {
                return true;
            }
        }
        return false;
    }
}
