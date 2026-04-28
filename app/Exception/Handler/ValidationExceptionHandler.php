<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use App\Trait\ResponseTrait;
use Hyperf\Context\ApplicationContext;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\ValidationException;
use Psr\Container\ContainerInterface;
use Swow\Psr7\Message\ResponsePlusInterface;
use Throwable;

class ValidationExceptionHandler extends \Hyperf\Validation\ValidationExceptionHandler
{
    use ResponseTrait;

    protected ContainerInterface $container;

    public function __construct()
    {
        $this->container = ApplicationContext::getContainer();
    }

    public function handle(Throwable $throwable, ResponsePlusInterface $response)
    {
        $this->stopPropagation();
        /** @var ValidationException $throwable */
        $body = $throwable->validator->errors()->first();
        if ($this->isJsonRequest()) {
            if (!$response->hasHeader('content-type')) {
                $response = $response->addHeader('content-type', 'application/json; charset=utf-8');
            }
            return $this->error($body, $throwable->status, null, $response);
        }
        if (!$response->hasHeader('content-type')) {
            $response = $response->addHeader('content-type', 'text/plain; charset=utf-8');
        }
        return $response->setStatus($throwable->status)->setBody(new SwooleStream($body));
    }

    private function isJsonRequest(): bool
    {
        $request = $this->container->get(RequestInterface::class);
        $accept = $request->getHeaderLine('Accept');
        $contentType = $request->getHeaderLine('Content-Type');
        return str_contains($accept, 'application/json') || str_contains($contentType, 'application/json');
    }
}
