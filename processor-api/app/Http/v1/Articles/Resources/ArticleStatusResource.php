<?php

namespace App\Http\v1\Articles\Resources;

use App\Domain\Articles\DTOs\ArticleStatusResultDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ArticleStatusResultDTO $resource
 */
class ArticleStatusResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{uuid: string, status: int, status_label: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->resource->uuid->value(),
            'status' => $this->resource->status->value,
            'status_label' => $this->resource->status->label(),
        ];
    }
}
