<?php
namespace App\Exceptions;

class BaseException extends \Exception
{
    protected $statusCode = 500;

    protected $errors = null;

    public function setErrors ($errors)
    {
        $this->errors = $errors;
        return $this;
    }

    public function setStatusCode (int $code)
    {
        $this->statusCode = intval($code);
        return $this;
    }

    public function getStatusCode ()
    {
        return $this->statusCode;
    }

    public function getErrors ()
    {
        return $this->errors;
    }

}
