<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Radicals\Resources;

use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji as DomainKanji;
use App\Domain\JapaneseMaterial\Radicals\Models\Radical as DomainRadical;
use App\Http\v1\JapaneseMaterial\Kanjis\Resources\KanjiResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The class-level `@property` lets Scramble resolve `$this->resource` when it infers this
 * component; without it every property degrades to `string`. Same pattern as `KanjiResource`
 * and `SentenceResource`.
 *
 * @property DomainRadical $resource
 */
class RadicalResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(
        DomainRadical $resource,
        private readonly bool $includeKanjis = false,
    ) {
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

        // `kanjis` goes through `when` rather than a conditional assignment: Scramble then
        // documents the key as optional and types its items, where the assignment left it
        // untyped and required.
        return [
            'id' => $radical->getIdValue(),
            'uuid' => $radical->getUuid()->value(),
            'radical' => $radical->getRadical(),
            'strokes' => $radical->getStrokes(),
            'meaning' => $radical->getMeaning(),
            'hiragana' => $radical->getHiragana(),
            /** @var array<int, KanjiResource> */
            'kanjis' => $this->when(
                $this->includeKanjis,
                fn (): array => array_map(
                    fn (DomainKanji $kanji): KanjiResource => new KanjiResource($kanji),
                    $radical->getKanjis(),
                ),
            ),
        ];
    }
}
