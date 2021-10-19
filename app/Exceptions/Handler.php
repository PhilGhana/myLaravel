<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use App\Events\ThrowException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, \Exception $exception)
    {

        # 發出 Exception 的事件
        try {

            event(new ThrowException($exception));

        } catch (Exception $err) {
            return parent::render($request, $exception);
        }

        if (is_a($exception, BaseException::class)) {
            $data = ['message' => $exception->getMessage()];
            if ($exception->getErrors()) {
                $data['errors'] = $exception->getErrors();
            }
            return response($data, $exception->getStatusCode());
        }

        $statusCode = 500;
        $message = $exception->getMessage() ?: ("Error: " . get_class($exception));
        switch (get_class($exception)) {
            case NotFoundHttpException::class:
                $statusCode = 404;
                $message = "Not Found";
                break;
            case MethodNotAllowedHttpException::class:
                $statusCode = 405;
                $message = "Method Not Allowed";
                break;
        }

        return response(['message' => $message], $statusCode);

        // return parent::render($request, $exception);  // 太多錯誤網頁會load太久，return error message 就好
    }
}
