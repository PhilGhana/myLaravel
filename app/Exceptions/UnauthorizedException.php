<?php
namespace App\Exceptions;
/**
 * 401
 * 未認證 / 未登入
 */
class UnauthorizedException extends BaseException
{

    protected $statusCode = 401;

    public function __construct ($message = 'unauthorized', $code = 0, Exception $previous = null)
    {

        parent::__construct($message, $code);

    }

}
