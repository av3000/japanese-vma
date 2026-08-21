<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Words\Resources;

use App\Domain\Catalogues\DTOs\ViewerCatalogueStateDTO;
use App\Domain\JapaneseMaterial\Words\Models\Word;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WordResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(
        Word $resource,
        private readonly ?ViewerCatalogueStateDTO $viewerCatalogueState = null,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array{
     *     id: int,
     *     uuid: string,
     *     word: string,
     *     furigana: string,
     *     jlpt: ?string,
     *     meaning: string,
     *     meanings: array<int, string>,
     *     word_types: array<int, string>,
     *     writing_elements: array<int, string>,
     *     reading_elements: array<int, string>,
     *     word_type: string,
     *     word_k_ele: string,
     *     furigana_r_ele: string,
     *     sense: ?string,
     *     viewer_catalogue_state: array{is_saved: bool, is_known: bool|null}|null
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var Word $word */
        $word = $this->resource;

        return [
            'id' => $word->getIdValue(),
            'uuid' => $word->getUuid()->value(),
            'word' => $word->getSurface(),
            'furigana' => $word->getFurigana(),
            'jlpt' => $word->getJlpt(),
            'meaning' => implode(', ', array_slice($word->getMeanings(), 0, 3)),
            'meanings' => $word->getMeanings(),
            'word_types' => $word->getWordTypes(),
            'writing_elements' => $word->getWritingElements(),
            'reading_elements' => $word->getReadingElements(),
            'word_type' => $word->getRawWordType() ?? '',
            'word_k_ele' => $word->getRawWritingElements() ?? '',
            'furigana_r_ele' => $word->getRawReadingElements() ?? '',
            'sense' => $word->getRawSense(),
            'viewer_catalogue_state' => $this->viewerCatalogueState === null
                ? null
                : [
                    'is_saved' => $this->viewerCatalogueState->isSaved,
                    'is_known' => $this->viewerCatalogueState->isKnown,
                ],
        ];
    }
}
