<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Sentences\Resources;

use App\Domain\JapaneseMaterial\Sentences\DTOs\SentenceListResultDTO;
use App\Domain\JapaneseMaterial\Sentences\Models\Sentence;
use App\Http\v1\Shared\Resources\PaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SentenceListResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(SentenceListResultDTO $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array{
     *     items: array<int, SentenceResource>,
     *     pagination: PaginationResource
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var SentenceListResultDTO $result */
        $result = $this->resource;

        return [
            'items' => array_map(
                fn (Sentence $sentence): SentenceResource => new SentenceResource($sentence),
                $result->items,
            ),
            'pagination' => new PaginationResource($result->pagination),
        ];
    }
}
