<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\AuthenticationException;
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
        if ($this->isHttpException($exception)) {
            if($request->is('api/*')) {
                return response()->json([
                    'error' => [
                        'message' => 'Not found.',
                    ],
                ], 404);
            }
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

    public function register()
    {
        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHENTICATED',
                        'message' => 'Authentication required.',
                    ],
                ], 401);
            }
            return parent::render($request, $e);
        });

        // Handle database exceptions globally
        $this->renderable(function (QueryException $e, Request $request) {
            // Log the actual DB error
            Log::error('Database query failed', [
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'request_url' => $request->fullUrl(),
                'user_id' => auth()->id(),
            ]);

            // Return user-friendly response
            if ($request->expectsJson()) {
                return response()->json([
                    'type' => 'https://tools.ietf.org/html/rfc7231#section-6.6.1',
                    'title' => 'Database error',
                    'status' => 500,
                    'detail' => 'A database error occurred. Please try again later.',
                    'instance' => $request->getRequestUri(),
                    'timestamp' => now()->toISOString()
                ], 500);
            }

            return parent::render($request, $e);
        });
    }
}
