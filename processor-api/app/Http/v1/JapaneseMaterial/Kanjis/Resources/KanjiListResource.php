<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Kanjis\Resources;

use App\Domain\Catalogues\DTOs\ViewerCatalogueStateDTO;
use App\Domain\JapaneseMaterial\Kanjis\DTOs\KanjiListResultDTO;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji;
use App\Http\v1\Shared\Resources\PaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property KanjiListResultDTO $resource
 */
class KanjiListResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @param array<int, ViewerCatalogueStateDTO> $viewerCatalogueStates
     */
    public function __construct(
        KanjiListResultDTO $resource,
        private readonly array $viewerCatalogueStates = [],
    ) {
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
                fn (Kanji $kanji): KanjiResource => new KanjiResource(
                    $kanji,
                    $this->viewerCatalogueStates[$kanji->getIdValue()] ?? null,
                ),
                $result->items,
            ),
            'pagination' => new PaginationResource($result->pagination),
        ];
    }
}
