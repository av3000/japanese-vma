<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Sentences\Resources;

use App\Domain\JapaneseMaterial\Sentences\Models\Sentence as DomainSentence;
use App\Domain\JapaneseMaterial\Words\Models\Word as DomainWord;
use App\Http\v1\JapaneseMaterial\Kanjis\Resources\KanjiResource;
use App\Http\v1\JapaneseMaterial\Words\Resources\WordResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The class-level `@property` is what lets Scramble resolve `$this->resource`
 * when it infers the `SentenceResource` component schema. Without it every
 * property degrades to `string` and nested resources degrade to untyped
 * arrays, which then lands in the generated frontend client. Keep it in sync
 * with `KanjiResource`, which is the working reference for this pattern.
 *
 * @property DomainSentence $resource
 */
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
     *     words?: array<int, WordResource>
     * }
     */
    public function toArray(Request $request): array
    {
        $payload = [
            'id' => $this->resource->getIdValue(),
            'uuid' => $this->resource->getUuid()->value(),
            'user_id' => $this->resource->getUserId(),
            'tatoeba_entry' => $this->resource->getTatoebaEntry(),
            'content' => $this->resource->getContent(),
        ];

        // `::collection()` rather than `array_map`: Scramble does not infer the
        // element type of an `array_map` result, so the mapped variant documents
        // `kanjis` as an untyped array and the generated client then types it
        // `unknown[]`. Same combination as ArticleModerationItemResource.
        // Safe here because KanjiResource takes a single constructor argument.
        if ($this->includeKanjis) {
            $payload['kanjis'] = KanjiResource::collection($this->resource->getKanjis());
        }

        // WordResource cannot use `::collection()`: `mapInto` passes the collection
        // key as the second constructor argument, which WordResource types as
        // ?ViewerCatalogueStateDTO, so it throws a TypeError on any non-empty list.
        // `words` stays an untyped array in the schema; no client reads it today.
        if ($this->includeWords) {
            $payload['words'] = array_map(
                fn (DomainWord $word): WordResource => new WordResource($word),
                $this->resource->getWords(),
            );
        }

        return $payload;
    }
}
