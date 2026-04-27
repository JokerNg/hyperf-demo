<?php

declare(strict_types=1);

namespace App\Trait;

use Hyperf\Context\ApplicationContext;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;

trait ResponseTrait
{
    public function success(array|object|null $data = null, string $message = 'Success', int $code = 200, ?Psr7ResponseInterface $response = null): Psr7ResponseInterface
    {
        return $this->getResponse($response)->json([
            'code' => $code,
            'message' => $message,
            'data' => $data === null ? new \stdClass() : $data,
        ]);
    }

    public function error(string $message = 'Error', int $code = 500, array|object|null $data = null, ?Psr7ResponseInterface $response = null): Psr7ResponseInterface
    {
        return $this->getResponse($response)->json([
            'code' => $code,
            'message' => $message,
            'data' => $data === null ? new \stdClass() : $data,
        ]);
    }

    protected function getResponse(?Psr7ResponseInterface $response = null): ResponseInterface
    {
        if ($response instanceof ResponseInterface) {
            return $response;
        }

        if (property_exists($this, 'response') && $this->response instanceof ResponseInterface) {
            return $this->response;
        }

        return ApplicationContext::getContainer()->get(ResponseInterface::class);
    }
}