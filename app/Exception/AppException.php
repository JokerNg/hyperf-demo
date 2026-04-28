<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

class AppException extends Exception
{
    protected $code = 10001;
}
