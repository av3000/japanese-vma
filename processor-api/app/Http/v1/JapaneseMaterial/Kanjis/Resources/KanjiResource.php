<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Kanjis\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji as DomainKanji;

class KanjiResource extends JsonResource
{
    public function __construct(DomainKanji $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var DomainKanji $kanji */
        $kanji = $this->resource;

        return [
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
