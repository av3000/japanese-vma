<?php
namespace App\Shared\Http;

use Illuminate\Http\JsonResponse;
use App\Shared\Enums\ErrorType;
use App\Shared\Results\Error;

class TypedResults
{
    public static function ok(mixed $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }

    public static function created(mixed $data, ?string $location = null): JsonResponse
    {
        $response = response()->json([
            'success' => true,
            'data' => $data
        ], 201);

        if ($location) {
            $response->header('Location', $location);
        }

        return $response;
    }

    public static function notFound(string $title = 'Resource not found', ?string $detail = null): JsonResponse
    {
        return self::problemDetails(
            title: $title,
            detail: $detail,
            status: 404,
            type: 'https://tools.ietf.org/html/rfc7231#section-6.5.4'
        );
    }

    public static function validationProblem(array $errors, string $title = 'Validation failed'): JsonResponse
    {
        return self::problemDetails(
            title: $title,
            detail: 'One or more validation errors occurred.',
            status: 422,
            type: 'https://tools.ietf.org/html/rfc4918#section-11.2',
            errors: $errors
        );
    }

    public static function forbidden(string $title = 'Access denied', ?string $detail = null): JsonResponse
    {
        return self::problemDetails(
            title: $title,
            detail: $detail,
            status: 403,
            type: 'https://tools.ietf.org/html/rfc7231#section-6.5.3'
        );
    }

    public static function conflict(string $title = 'Conflict occurred', ?string $detail = null): JsonResponse
    {
        return self::problemDetails(
            title: $title,
            detail: $detail,
            status: 409,
            type: 'https://tools.ietf.org/html/rfc7231#section-6.5.8'
        );
    }

    private static function problemDetails(
        string $title,
        ?string $detail,
        int $status,
        string $type,
        ?array $errors = null
    ): JsonResponse {
        $problem = [
            'type' => $type,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'instance' => request()->getRequestUri(),
            'timestamp' => now()->toISOString()
        ];

        if ($errors) {
            $problem['errors'] = $errors;
        }

        return response()->json($problem, $status, [
            'Content-Type' => 'application/problem+json'
        ]);
    }

    public static function fromError(Error $error): JsonResponse
    {
        return match($error->type) {
            ErrorType::NOT_FOUND => self::notFound($error->description, $error->detail),
            ErrorType::FORBIDDEN => self::forbidden($error->description, $error->detail),
            ErrorType::CONFLICT => self::conflict($error->description, $error->detail),
            ErrorType::VALIDATION => self::validationProblem([], $error->description),
            default => self::conflict($error->description, $error->detail),
        };
    }
}
