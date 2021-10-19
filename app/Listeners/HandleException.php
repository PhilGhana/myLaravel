<?php

namespace App\Listeners;

use App\Events\ThrowException;
use App\Exceptions\BaseException;
use App\Models\LogException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HandleException
{
    public function handle(ThrowException $event)
    {
        if (! config('app.log.exception')) {
            return;
        }
        $exception = $event->exception;
        $basePath  = base_path();
        $traces    = array_map(function ($trace) use ($basePath) {

            // $vendorPath = implode(DIRECTORY_SEPARATOR, [$basePath, 'vendor']);
            list(
                'function' => $func,
                'class'    => $class,
                'type'     => $type,
                'args'     => $args,
                'file'     => $file,
                'line'     => $line) = array_merge([
                'function' => null,
                'class'    => null,
                'type'     => null,
                'args'     => null,
                'file'     => null,
                'line'     => null,
            ], $trace);

            $arr = array_map(function ($value) {
                if (is_object($value)) {
                    return get_class($value);
                } elseif (is_array($value)) {
                    return 'array()';
                } else {
                    return strtolower(gettype($value));
                }
            }, $args ?: []);

            return [
                'line'  => $line ?: null,
                'file'  => $file ?: null,
                'func'  => $func ?: null,
                'type'  => $type ?: null,
                'class' => $class ?: null,
                'args'  => $arr,
            ];
        }, $exception->getTrace());

        $message    = substr($exception->getMessage(), 0, 1000);
        $code       = $exception;
        $statusCode = is_a($exception, BaseException::class) ? $exception->getStatusCode() : 500;
        if (is_a($exception, NotFoundHttpException::class)) {
            $statusCode = 404;
            $message    = 'Not Found ';
        // return;
        } elseif (is_a($exception, MethodNotAllowedHttpException::class)) {
            $statusCode = 404;
            $message    = 'Method Not Allowed';
        }

        $log              = new LogException();
        $log->site        = LogException::SITE_AGENT;
        $log->status_code = $statusCode;
        $log->code        = $exception->getCode();
        $log->class_name  = get_class($exception);
        $log->file        = $exception->getFile();
        $log->line        = $exception->getLine();
        $log->url         = substr(request()->url(), 0, 200);
        $log->message     = $message ?: 'unknown error';
        $log->traces      = $traces;
        $log->ip          = request()->ip();
        $log->save();
    }
}
