<?php

namespace App\Exceptions;

use Exception;

class CommonException extends Exception
{

    protected $args = [];

    public function __construct(
        $message = '',
        $status = 400,
        array $args = []
    ) {
        $this->args = $args;
        parent::__construct($message, $status);
    }
    /**
     * Report the exception.
     *
     * @return void
     */
    public function report()
    {
        //
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return response(
            $this->args + [ 'message' => $this->message ],
            $this->code
        );
    }

    public static function withTrans(string $transKey, $status = 400, array $args = [])
    {
        return new self(__($transKey), $status, $args);
    }
}
