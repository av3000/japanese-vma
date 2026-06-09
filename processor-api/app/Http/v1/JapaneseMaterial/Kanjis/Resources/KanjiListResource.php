<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Kanjis\Resources;

use App\Domain\JapaneseMaterial\Kanjis\DTOs\KanjiListResultDTO;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji;
use App\Http\v1\Shared\Resources\PaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KanjiListResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(KanjiListResultDTO $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array{
     *     items: array<int, KanjiResource>,
     *     pagination: PaginationResource
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var KanjiListResultDTO $result */
        $result = $this->resource;

        return [
            'items' => array_map(
                fn (Kanji $kanji): KanjiResource => new KanjiResource($kanji),
                $result->items,
            ),
            'pagination' => new PaginationResource($result->pagination),
        ];
    }
}
