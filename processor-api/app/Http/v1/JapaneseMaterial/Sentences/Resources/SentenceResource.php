<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Sentences\Resources;

use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji as DomainKanji;
use App\Domain\JapaneseMaterial\Sentences\Models\Sentence as DomainSentence;
use App\Http\v1\JapaneseMaterial\Kanjis\Resources\KanjiResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SentenceResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(
        DomainSentence $resource,
        private readonly bool $includeKanjis = false,
        private readonly bool $includeWords = false,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array{
     *     id: int,
     *     uuid: string,
     *     user_id: int|null,
     *     tatoeba_entry: string|null,
     *     content: string,
     *     kanjis?: array<int, KanjiResource>,
     *     words?: array<int, mixed>
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var DomainSentence $sentence */
        $sentence = $this->resource;

        $payload = [
            'id' => $sentence->getIdValue(),
            'uuid' => $sentence->getUuid()->value(),
            'user_id' => $sentence->getUserId(),
            'tatoeba_entry' => $sentence->getTatoebaEntry(),
            'content' => $sentence->getContent(),
        ];

        if ($this->includeKanjis) {
            $payload['kanjis'] = array_map(
                fn (DomainKanji $kanji): KanjiResource => new KanjiResource($kanji),
                $sentence->getKanjis(),
            );
        }

        if ($this->includeWords) {
            // Sentence-word relation is not represented in persistence yet; expose an empty array until that relation exists.
            $payload['words'] = $sentence->getWords();
        }

        return $payload;
    }
}
