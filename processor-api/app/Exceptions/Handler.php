<?php

namespace App\Exceptions;

use App\Domain\Shared\Exceptions\ValueObjectValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

use App\Shared\Enums\HttpStatus;
use function Sentry\captureException;
use function Sentry\configureScope;

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
        if ($this->shouldReport($exception) && config('sentry.dsn')) {
            configureScope(function ($scope) use ($exception) {
                $scope->setTag('app_env', app()->environment());
                $scope->setTag('app_release', (string) config('app.release'));
                $scope->setTag('exception_class', $exception::class);

                if (app()->bound('request') && !app()->runningInConsole()) {
                    $request = request();

                    $scope->setContext('request', [
                        'path' => $request->path(),
                        'method' => $request->method(),
                    ]);

                    $userId = auth('api')->id();
                    if ($userId !== null) {
                        $scope->setUser([
                            'id' => (string) $userId,
                        ]);
                    }
                }
            });

            captureException($exception);
        }

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

        if ($exception instanceof ValueObjectValidationException && ($request->expectsJson() || $request->is('api/*'))) {
            Log::warning("Value Object validation failed: {$exception->getMessage()}", $this->buildLogContext($request, $exception, [
                'errors' => $exception->getErrors(),
            ]));

            return response()->json([
                'type' => HttpStatus::UNPROCESSABLE_ENTITY->getTypeUri(),
                'title' => 'Invalid Request Parameter',
                'status' => HttpStatus::UNPROCESSABLE_ENTITY->value,
                'detail' => $exception->getMessage(),
                'instance' => $request->path(),
                'timestamp' => now()->toIso8601String(),
                'errors' => $exception->getErrors() // Include specific errors from your VO exception
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
            Log::error('Database query failed', $this->buildLogContext($request, $exception, [
                'sql' => $exception->getSql(),
                'bindings' => $exception->getBindings(),
                'error' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ]));

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
            // Some IDEs/static analyzers won't narrow types inside a ternary. Keep this explicit.
            $statusCode = (int) $exception->getCode();
            if ($exception instanceof HttpExceptionInterface) {
                $statusCode = $exception->getStatusCode();
            }

            if ($request->is('api/*')) {
                $httpStatusEnum = HttpStatus::tryFrom($statusCode);
                $httpStatus = $httpStatusEnum?->getHttpExceptionTitle() ?? 'Error';

                return response()->json([
                    'type' => $httpStatusEnum?->getTypeUri() ?? 'about:blank',
                    'title' => $httpStatus,
                    'status' => $statusCode,
                    'detail' => $exception->getMessage(),
                    'instance' => $request->path(),
                    'timestamp' => now()->toIso8601String()
                ], $httpStatusEnum?->value ?? $statusCode);
            }

            // Web routes - return views
            if ($statusCode === 404) {
                return response()->view('errors.404', [
                    'success' => false,
                    'error' => 404,
                ], 404);
            }

            if ($statusCode === 500) {
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

    private function buildLogContext(Request $request, Throwable $exception, array $context = []): array
    {
        return array_filter(array_merge([
            'request_url' => $request->fullUrl(),
            'request_path' => $request->path(),
            'request_method' => $request->method(),
            'user_id' => auth('api')->id(),
            'app_env' => app()->environment(),
            'app_release' => config('app.release'),
            'exception_class' => $exception::class,
        ], $context), static fn ($value) => $value !== null && $value !== '');
    }
}
