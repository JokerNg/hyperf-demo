<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use App\Exception\AppException;
use App\Trait\ResponseTrait;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class AppExceptionHandler extends ExceptionHandler
{
    use ResponseTrait;

    protected ContainerInterface $container;

    public function __construct(protected StdoutLoggerInterface $logger)
    {
        $this->container = ApplicationContext::getContainer();
    }

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        if ($throwable instanceof AppException) {
            return $this->error($throwable->getMessage(), $throwable->getCode(), null, $response);
        }
        $this->logger->error(sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));
        $this->logger->error($throwable->getTraceAsString());
        if ($this->isJsonRequest()) {
            return $this->error('Internal Server Error.', 500, null, $response);
        }
        return $response->withHeader('Server', 'Hyperf')->withStatus(500)
            ->withBody(new SwooleStream('Internal Server Error.'));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }

    private function isJsonRequest(): bool
    {
        $request = $this->container->get(RequestInterface::class);
        $accept = $request->getHeaderLine('Accept');
        $contentType = $request->getHeaderLine('Content-Type');
        return str_contains($accept, 'application/json') || str_contains($contentType, 'application/json');
    }
}
