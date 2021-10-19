<?php

namespace App\Exceptions;

use Exception;

class ErrorException extends BaseException
{
    public function __construct($message = 'error', $code = 0, Exception $previous = null)
    {
        parent::__construct($message, 500);
    }
}
