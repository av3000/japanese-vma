<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Words\Resources;

use App\Domain\JapaneseMaterial\Words\DTOs\WordListResultDTO;
use App\Domain\JapaneseMaterial\Words\Models\Word;
use App\Http\v1\Shared\Resources\PaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WordListResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(WordListResultDTO $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array{
     *     items: array<int, WordResource>,
     *     pagination: PaginationResource
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var WordListResultDTO $result */
        $result = $this->resource;

        return [
            'items' => array_map(
                fn (Word $word): WordResource => new WordResource($word),
                $result->items,
            ),
            'pagination' => new PaginationResource($result->pagination),
        ];
    }
}
