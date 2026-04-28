<?php

declare(strict_types=1);
use App\Exception\Handler\AppExceptionHandler;
use App\Exception\Handler\ValidationExceptionHandler;
use Hyperf\ExceptionHandler\Handler\WhoopsExceptionHandler;
use Hyperf\HttpServer\Exception\Handler\HttpExceptionHandler;
use Whoops\Run;

/**
 * This file is part of Hyperf.
 *
 * @see     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
$handlers = [
    ValidationExceptionHandler::class,
    HttpExceptionHandler::class,
    AppExceptionHandler::class,
];

if (class_exists(Run::class)) {
    $handlers[] = WhoopsExceptionHandler::class;
}

return [
    'handler' => [
        'http' => $handlers,
    ],
];
