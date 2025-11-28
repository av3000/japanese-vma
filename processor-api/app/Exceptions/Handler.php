<?php

namespace App\Exceptions;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Throwable;

use App\Shared\Enums\HttpStatus;
use Illuminate\Support\Facades\Log;

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
        if ($exception instanceof ValidationException && ($request->expectsJson() || $request->is('api/*'))) {
            return response()->json([
                'type' => HttpStatus::UNPROCESSABLE_ENTITY->getTypeUri(),
                'title' => 'Validation failed',
                'status' => HttpStatus::UNPROCESSABLE_ENTITY->value,
                'detail' => 'One or more validation errors occurred',
                'instance' => $request->path(),
                'timestamp' => now()->toIso8601String(),
                'errors' => $exception->errors()
            ], HttpStatus::UNPROCESSABLE_ENTITY->value);
        }

        if ($exception instanceof AuthenticationException && ($request->expectsJson() || $request->is('api/*'))) {
            return response()->json([
                'type' => HttpStatus::UNAUTHORIZED->getTypeUri(),
                'title' => 'Unauthenticated',
                'status' => HttpStatus::UNAUTHORIZED->value,
                'detail' => 'Authentication required',
                'instance' => $request->path(),
                'timestamp' => now()->toIso8601String()
            ], HttpStatus::UNAUTHORIZED->value);
        }

        if ($exception instanceof QueryException && ($request->expectsJson() || $request->is('api/*'))) {
            Log::error('Database query failed', [
                'sql' => $exception->getSql(),
                'bindings' => $exception->getBindings(),
                'error' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'request_url' => $request->fullUrl(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'type' => HttpStatus::INTERNAL_SERVER_ERROR->getTypeUri(),
                'title' => 'Server error',
                'status' => HttpStatus::INTERNAL_SERVER_ERROR->value,
                'detail' => 'A Server error occurred. Please try again later.',
                'instance' => $request->path(),
                'timestamp' => now()->toIso8601String()
            ], HttpStatus::INTERNAL_SERVER_ERROR->value);
        }

        if ($this->isHttpException($exception)) {
            if ($request->is('api/*')) {
                $httpStatusEnum = HttpStatus::tryFrom($exception->getStatusCode());
                $httpStatus = $httpStatusEnum?->getHttpExceptionTitle() ?? 'Error';

                return response()->json([
                    'type' => $httpStatusEnum->getTypeUri() ?? 'about:blank',
                    'title' => $httpStatus,
                    'status' => $exception->getStatusCode(),
                    'detail' => $exception->getMessage(),
                    'instance' => $request->path(),
                    'timestamp' => now()->toIso8601String()
                ], $httpStatusEnum->value);
            }

            // Web routes - return views
            if ($exception->getStatusCode() == 404) {
                return response()->view('errors.404', [
                    'success' => false,
                    'error' => 404,
                ], 404);
            }

            if ($exception->getStatusCode() == 500) {
                return response()->view('errors.500', [
                    'success' => false,
                    'error' => 500,
                ], 500);
            }
        }

        return parent::render($request, $exception);
    }

    public function register()
    {
        // Custom handling, runs after render.
    }
}
