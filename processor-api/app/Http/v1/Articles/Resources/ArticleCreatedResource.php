<?php

declare(strict_types=1);

namespace App\Http\v1\Articles\Resources;

use App\Domain\Articles\DTOs\ArticleCreateResultDTO;
use App\Http\v1\Processing\Resources\ProcessingStatusResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 201 body for article creation: the new uuid plus the `pending` processing status opened in
 * the same transaction, so the client can render a badge before the first detail fetch.
 *
 * @property ArticleCreateResultDTO $resource
 */
class ArticleCreatedResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{uuid: string, processing_status: ProcessingStatusResource}
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->resource->article->getUid()->value(),
            'processing_status' => new ProcessingStatusResource($this->resource->processingState),
        ];
    }

    public function withResponse(Request $request, JsonResponse $response): void
    {
        $response->setStatusCode(201);
    }
}
