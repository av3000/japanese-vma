<?php

declare(strict_types=1);

namespace App\Http\v1\LastOperations\Resources;

use App\Application\Processing\Presenters\ProcessingStatePayload;
use App\Domain\Articles\DTOs\ArticleProcessingStateDTO;
use App\Domain\Shared\Enums\LastOperationStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public `processing_status` field. Thin HTTP wrapper over the Application presenter so the
 * shape is defined once (issue #259).
 *
 * @property ArticleProcessingStateDTO $resource
 */
class ProcessingStatusResource extends JsonResource
{
    public function __construct(ArticleProcessingStateDTO $state)
    {
        parent::__construct($state);
    }

    /**
     * @return array{
     *     id: int,
     *     type: string,
     *     status: LastOperationStatus,
     *     metadata: object,
     *     created_at: ?string,
     *     updated_at: ?string
     * }
     */
    public function toArray(Request $request): array
    {
        $payload = ProcessingStatePayload::fromDto($this->resource);

        // The enum keeps the OpenAPI schema (and the generated client type) documented as
        // an enumeration; JSON output is the same string either way.
        return [
            'id' => $payload['id'],
            'type' => $payload['type'],
            'status' => LastOperationStatus::from($payload['status']),
            'metadata' => $payload['metadata'],
            'created_at' => $payload['created_at'],
            'updated_at' => $payload['updated_at'],
        ];
    }
}
