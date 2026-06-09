<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Kanjis\Resources;

use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji as DomainKanji;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KanjiResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(DomainKanji $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array{
     *     id: int,
     *     uuid: string,
     *     character: string,
     *     onyomi: array<int, string>,
     *     kunyomi: array<int, string>,
     *     meanings: array<int, string>,
     *     nanori: array<int, string>,
     *     grade: string|null,
     *     stroke_count: int,
     *     jlpt: string|null,
     *     frequency: int|null,
     *     radicals: array<int, string>,
     *     radical_parts: array<int, string>
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var DomainKanji $kanji */
        $kanji = $this->resource;

        return [
            'id' => $kanji->getIdValue(),
            'uuid' => $kanji->getUuid()->value(),
            'character' => $kanji->getCharacter()->value(),
            'onyomi' => $kanji->getOnyomi(),
            'kunyomi' => $kanji->getKunyomi(),
            'meanings' => $kanji->getMeanings(),
            'nanori' => $kanji->getNanori(),
            'grade' => $kanji->getGrade()?->value(),
            'stroke_count' => $kanji->getStrokeCount(),
            'jlpt' => $kanji->getJlpt()?->value(),
            'frequency' => $kanji->getFrequency(),
            'radicals' => $kanji->getRadicals(),
            'radical_parts' => $kanji->getRadicalParts(),
        ];
    }
}
