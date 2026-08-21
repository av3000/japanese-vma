<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Kanjis\Resources;

use App\Domain\JapaneseMaterial\Kanjis\DTOs\KanjiDetailResultDTO;
use App\Http\v1\Articles\Resources\ArticleListResource;
use App\Http\v1\JapaneseMaterial\Sentences\Resources\SentenceListResource;
use App\Http\v1\JapaneseMaterial\Words\Resources\WordListResource;
use App\Http\v1\Shared\Resources\PaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property KanjiDetailResultDTO $resource
 */
class KanjiDetailResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(KanjiDetailResultDTO $resource)
    {
        parent::__construct($resource);
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
     *     grade: string|null,
     *     stroke_count: int,
     *     jlpt: string|null,
     *     frequency: int|null,
     *     radicals: list<string>,
     *     radical_parts: list<string>,
     *     viewer_catalogue_state: array{is_saved: bool, is_known: bool|null}|null,
     *     words?: array{
     *         items: array<int, array{
     *             id: int,
     *             uuid: string,
     *             word: string,
     *             furigana: string,
     *             jlpt: string|null,
     *             meaning: string,
     *             meanings: array<int, string>,
     *             word_types: array<int, string>,
     *             writing_elements: array<int, string>,
     *             reading_elements: array<int, string>,
     *             word_type: string,
     *             word_k_ele: string,
     *             furigana_r_ele: string,
     *             sense: string|null,
     *             viewer_catalogue_state: array{is_saved: bool, is_known: bool|null}|null
     *         }>,
     *         pagination: PaginationResource
     *     },
     *     sentences?: array{
     *         items: array<int, array{
     *             id: int,
     *             uuid: string,
     *             user_id: int|null,
     *             tatoeba_entry: string|null,
     *             content: string
     *         }>,
     *         pagination: PaginationResource
     *     },
     *     articles?: ArticleListResource
     * }
     */
    public function toArray(Request $request): array
    {
        $payload = (new KanjiResource(
            $this->resource->kanji,
            $this->resource->viewerCatalogueState,
        ))->resolve($request);

        if ($this->resource->words !== null) {
            $payload['words'] = new WordListResource($this->resource->words);
        }

        if ($this->resource->sentences !== null) {
            $payload['sentences'] = new SentenceListResource($this->resource->sentences);
        }

        if ($this->resource->articles !== null) {
            $payload['articles'] = new ArticleListResource($this->resource->articles);
        }

        return $payload;
    }
}
