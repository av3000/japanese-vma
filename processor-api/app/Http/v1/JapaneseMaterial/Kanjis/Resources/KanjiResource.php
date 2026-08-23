<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Kanjis\Resources;

use App\Domain\Catalogues\DTOs\ViewerCatalogueStateDTO;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji as DomainKanji;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property DomainKanji $resource
 */
class KanjiResource extends JsonResource
{
    public static $wrap = null;

    private ?ViewerCatalogueStateDTO $viewerCatalogueState = null;

    public function withViewerCatalogueState(?ViewerCatalogueStateDTO $viewerCatalogueState): self
    {
        $this->viewerCatalogueState = $viewerCatalogueState;

        return $this;
    }

    /**
     * @return array{
     *     id: int,
     *     uuid: string,
     *     character: string,
     *     onyomi: list<string>,
     *     kunyomi: list<string>,
     *     meanings: list<string>,
     *     nanori: list<string>,
     *     grade: ?string,
     *     stroke_count: int,
     *     jlpt: ?string,
     *     frequency: ?int,
     *     radicals: list<string>,
     *     radical_parts: list<string>,
     *     viewer_catalogue_state: array{is_saved: bool, is_known: bool|null}|null
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var DomainKanji $kanji */
        $kanji = $this->resource;
        $grade = $kanji->getGrade()?->value();
        $jlpt = $kanji->getJlpt()?->value();
        $frequency = $kanji->getFrequency();

        return [
            'id' => (int) $kanji->getIdValue(),
            'uuid' => $kanji->getUuid()->value(),
            'character' => $kanji->getCharacter()->value(),
            'onyomi' => $kanji->getOnyomi(),
            'kunyomi' => $kanji->getKunyomi(),
            'meanings' => $kanji->getMeanings(),
            'nanori' => $kanji->getNanori(),
            'grade' => $grade === null ? null : (string) $grade,
            'stroke_count' => (int) $kanji->getStrokeCount(),
            'jlpt' => $jlpt === null ? null : (string) $jlpt,
            'frequency' => $frequency === null ? null : (int) $frequency,
            'radicals' => $kanji->getRadicals(),
            'radical_parts' => $kanji->getRadicalParts(),
            'viewer_catalogue_state' => $this->viewerCatalogueState === null
                ? null
                : [
                    'is_saved' => $this->viewerCatalogueState->isSaved,
                    'is_known' => $this->viewerCatalogueState->isKnown,
                ],
        ];
    }
}
