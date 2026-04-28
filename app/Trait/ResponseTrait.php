<?php

declare(strict_types=1);

namespace App\Trait;

use Hyperf\Context\ApplicationContext;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;
use stdClass;

trait ResponseTrait
{
    protected ?int $httpStatus = null;

    public function withStatus(int $status): static
    {
        $this->httpStatus = $status;
        return $this;
    }

    public function success(array|object|null $data = null, string $message = 'Success', int $code = 200, ?Psr7ResponseInterface $response = null): Psr7ResponseInterface
    {
        $response = $this->getResponse($response)->json([
            'code' => $code,
            'message' => $message,
            'data' => $data === null ? new stdClass() : $data,
        ]);

        if ($this->httpStatus !== null) {
            $response = $response->withStatus($this->httpStatus);
            $this->httpStatus = null;
        }

        return $response;
    }

    public function error(string $message = 'Error', int $code = 500, array|object|null $data = null, ?Psr7ResponseInterface $response = null): Psr7ResponseInterface
    {
        $response = $this->getResponse($response)->json([
            'code' => $code,
            'message' => $message,
            'data' => $data === null ? new stdClass() : $data,
        ]);

        if ($this->httpStatus !== null) {
            $response = $response->withStatus($this->httpStatus);
            $this->httpStatus = null;
        }

        return $response;
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
