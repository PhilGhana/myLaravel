<?php
namespace App\Exceptions;
/**
 * 403
 * 已登入, 但請求拒絕執 (無操作權限)
 */
class ForbiddenException extends BaseException
{

    protected $statusCode = 403;

    public function __construct ($message = null, $code = 0, Exception $previous = null)
    {

        parent::__construct($message ?? __('common.forbidden'), $code);

    }

}
