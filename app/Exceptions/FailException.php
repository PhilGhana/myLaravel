<?php
namespace App\Exceptions;

class FailException extends BaseException
{

    protected $statusCode = 400;

    public function __construct ($message = 'fail', $code = 0, Exception $previous = null)
    {

        parent::__construct($message, $code);

    }

}