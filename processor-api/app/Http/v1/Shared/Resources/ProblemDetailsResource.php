<?php

declare(strict_types=1);

namespace App\Http\v1\Shared\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The failure body every `TypedResults::fromError` response already emits.
 *
 * It has never been described in the schema: endpoints documented their 403s
 * and 404s as `{"type": "string"}`, so generated clients typed a structured
 * body as a bare string and `client/src/api/writeFailure.ts` hand-parses
 * `{ title, status, detail }` from an undocumented shape.
 *
 * This resource is never returned by a controller - it exists so `#[Response]`
 * attributes can point at a named component schema that matches reality.
 *
 * @property array{
 *     type: string,
 *     title: string,
 *     status: int,
 *     detail: string,
 *     instance: string,
 *     timestamp: string,
 *     errorMessage: string|null
 * } $resource
 */
class ProblemDetailsResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     type: string,
     *     title: string,
     *     status: int,
     *     detail: string,
     *     instance: string,
     *     timestamp: string,
     *     errorMessage: string|null
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            /** Absolute URI identifying the problem type. */
            'type' => $this->resource['type'],
            /** Short, human-readable summary of the problem type. */
            'title' => $this->resource['title'],
            /** HTTP status code, repeated in the body. */
            'status' => $this->resource['status'],
            /** Explanation specific to this occurrence. */
            'detail' => $this->resource['detail'],
            /** Request path the problem occurred on. */
            'instance' => $this->resource['instance'],
            /** ISO 8601 timestamp of the failure. */
            'timestamp' => $this->resource['timestamp'],
            /** Present only when the error carries an operator-facing message. */
            'errorMessage' => $this->resource['errorMessage'] ?? null,
        ];
    }
}
