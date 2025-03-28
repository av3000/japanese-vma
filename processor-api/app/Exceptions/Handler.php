<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

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
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        // if ($exception instanceof NotFoundHttpException) {
        //     return redirect('/');
        // }

        if ($this->isHttpException($exception)) {
            if ($exception->getStatusCode() == 404) {
                $data = [
                    'success' => false,
                    'error' => 404,
                ];
                return response()->view('errors.' . '404', $data, 404);
            }
             
            if ($exception->getStatusCode() == 500) {
                $data = [
                    'success' => false,
                    'error' => 404,
                ];
                return response()->view('errors.' . '500', $data, 500);
            }
        }

        return parent::render($request, $exception);
    }
}
