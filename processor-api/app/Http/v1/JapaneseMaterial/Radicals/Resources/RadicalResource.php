<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Radicals\Resources;

use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji as DomainKanji;
use App\Domain\JapaneseMaterial\Radicals\Models\Radical as DomainRadical;
use App\Http\v1\JapaneseMaterial\Kanjis\Resources\KanjiResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RadicalResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(
        DomainRadical $resource,
        private readonly bool $includeKanjis = false,
    )
    {
        parent::__construct($resource);
    }

    /**
     * @return array{
     *     id: int,
     *     uuid: string,
     *     radical: string|null,
     *     strokes: int|null,
     *     meaning: string|null,
     *     hiragana: string|null,
     *     kanjis?: array<int, KanjiResource>
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var DomainRadical $radical */
        $radical = $this->resource;

        $payload = [
            'id' => $radical->getIdValue(),
            'uuid' => $radical->getUuid()->value(),
            'radical' => $radical->getRadical(),
            'strokes' => $radical->getStrokes(),
            'meaning' => $radical->getMeaning(),
            'hiragana' => $radical->getHiragana(),
        ];

        if ($this->includeKanjis) {
            $payload['kanjis'] = array_map(
                fn (DomainKanji $kanji): KanjiResource => new KanjiResource($kanji),
                $radical->getKanjis(),
            );
        }

        return $payload;
    }
}
