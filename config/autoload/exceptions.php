<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

$handlers = [
    App\Exception\Handler\ValidationExceptionHandler::class,
    Hyperf\HttpServer\Exception\Handler\HttpExceptionHandler::class,
    App\Exception\Handler\AppExceptionHandler::class,
];

if (class_exists(\Whoops\Run::class)) {
    $handlers[] = \Hyperf\ExceptionHandler\Handler\WhoopsExceptionHandler::class;
}

return [
    'handler' => [
        'http' => $handlers,
    ],
];
