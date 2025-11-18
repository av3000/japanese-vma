<?php

namespace App\Shared\Http;

use Illuminate\Http\JsonResponse;
use App\Shared\Results\Error;
use App\Shared\Enums\{HttpStatus};

class TypedResults
{
    // ========================================
    // Result Pattern Integration
    // ========================================

    public static function fromError(Error $error): JsonResponse
    {
        $status = $error->toHttpStatus();

        $body = [
            'type' => $status->getTypeUri(),
            'title' => $error->description,
            'status' => $status->value,
            'detail' => $error->detail ?? $error->description,
            'instance' => request()->path(),
            'timestamp' => now()->toIso8601String(),
        ];

        if ($error->errorMessage !== null) {
            $body['errorMessage'] = $error->errorMessage;
        }

        return response()->json($body, $status->value);
    }

    // ========================================
    // Success Responses
    // ========================================

    public static function ok(mixed $data, ?string $message = null): JsonResponse
    {
        return self::buildSuccessResponse(HttpStatus::OK, $data, $message);
    }

    public static function created(mixed $data, ?string $message = null): JsonResponse
    {
        return self::buildSuccessResponse(HttpStatus::CREATED, $data, $message);
    }

    public static function accepted(mixed $data = null, ?string $message = null): JsonResponse
    {
        return self::buildSuccessResponse(HttpStatus::ACCEPTED, $data, $message);
    }

    public static function noContent(): JsonResponse
    {
        return response()->json(null, HttpStatus::NO_CONTENT->value);
    }

    // ========================================
    // Specific Error Responses (Direct Use) - not sure if needed if individuals errors for each entity is provided.
    // ========================================

    public static function notFound(
        string $title = 'Resource not found',
        ?string $detail = null,
        ?string $errorMessage = null
    ): JsonResponse {
        return self::buildProblemResponse(HttpStatus::NOT_FOUND, $title, $detail, $errorMessage);
    }

    public static function unauthorized(
        string $title = 'Unauthorized',
        ?string $detail = null,
        ?string $errorMessage = null
    ): JsonResponse {
        return self::buildProblemResponse(HttpStatus::UNAUTHORIZED, $title, $detail, $errorMessage);
    }

    public static function forbidden(
        string $title = 'Forbidden',
        ?string $detail = null,
        ?string $errorMessage = null
    ): JsonResponse {
        return self::buildProblemResponse(HttpStatus::FORBIDDEN, $title, $detail, $errorMessage);
    }

    public static function conflict(
        string $title = 'Conflict',
        ?string $detail = null,
        ?string $errorMessage = null
    ): JsonResponse {
        return self::buildProblemResponse(HttpStatus::CONFLICT, $title, $detail, $errorMessage);
    }

    public static function badRequest(
        string $title = 'Bad request',
        ?string $detail = null,
        ?string $errorMessage = null
    ): JsonResponse {
        return self::buildProblemResponse(HttpStatus::BAD_REQUEST, $title, $detail, $errorMessage);
    }

    public static function validationProblem(
        array $errors,
        string $title = 'Validation failed',
    ): JsonResponse {
        $status = HttpStatus::UNPROCESSABLE_ENTITY;

        $body = [
            'type' => $status->getTypeUri(),
            'title' => $title,
            'status' => $status->value,
            'detail' => 'One or more validation errors occurred',
            'instance' => request()->path(),
            'timestamp' => now()->toIso8601String(),
            'errors' => $errors,
        ];


        return response()->json($body, $status->value);
    }

    public static function internalServerError(
        string $title = 'Internal server error',
        ?string $detail = null,
        ?string $errorMessage = null
    ): JsonResponse {
        return self::buildProblemResponse(HttpStatus::INTERNAL_SERVER_ERROR, $title, $detail, $errorMessage);
    }

    // ========================================
    // Private Helpers
    // ========================================

    private static function buildSuccessResponse(
        HttpStatus $status,
        mixed $data,
        ?string $message = null
    ): JsonResponse {
        $body = ['success' => true];

        if ($message !== null) {
            $body['message'] = $message;
        }

        if ($data !== null) {
            $body['data'] = $data;
        }

        return response()->json($body, $status->value);
    }

    private static function buildProblemResponse(
        HttpStatus $status,
        string $title,
        ?string $detail = null,
        ?string $errorMessage = null
    ): JsonResponse {
        $body = [
            'type' => $status->getTypeUri(),
            'title' => $title,
            'status' => $status->value,
            'detail' => $detail ?? $title,
            'instance' => request()->path(),
            'timestamp' => now()->toIso8601String(),
        ];

        if ($errorMessage !== null) {
            $body['errorMessage'] = $errorMessage;
        }

        return response()->json($body, $status->value);
    }
}
