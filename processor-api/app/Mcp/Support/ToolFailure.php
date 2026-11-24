<?php

declare(strict_types=1);

namespace App\Mcp\Support;

use App\Shared\Results\Result;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Response;

/**
 * Turns a failure into the error response a tool returns, so a tool never throws to the client.
 *
 * The assistant reading the response has to be able to act on it: each validation message is
 * prefixed with its field name, and a failed service call says what the service said.
 */
final class ToolFailure
{
    private const SERVER_ERROR_MESSAGE = 'The study data could not be read right now. Try again later.';

    public static function fromValidation(ValidationException $exception): Response
    {
        $lines = [];

        foreach ($exception->errors() as $field => $messages) {
            foreach ($messages as $message) {
                $lines[] = "{$field}: {$message}";
            }
        }

        return Response::error(implode("\n", $lines));
    }

    public static function fromResult(Result $failure): Response
    {
        $error = $failure->getError();

        if ($error->status->isServerError()) {
            return Response::error(self::SERVER_ERROR_MESSAGE);
        }

        return Response::error($error->errorMessage ?? $error->description);
    }
}
