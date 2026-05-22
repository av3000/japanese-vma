<?php

declare(strict_types=1);

namespace App\Http\v1\Articles\Resources;

use App\Domain\JapaneseMaterial\Words\Models\Word;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Word $resource
 */
class ArticleWordResource extends JsonResource
{
    /**
     * @return array{
     *     id: int,
     *     uuid: string,
     *     word: string,
     *     furigana: string,
     *     jlpt: ?string,
     *     word_type: string,
     *     word_k_ele: string,
     *     furigana_r_ele: string,
     *     sense: ?string
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
            'word_type' => $word->getRawWordType() ?? '',
            'word_k_ele' => $word->getRawWritingElements() ?? '',
            'furigana_r_ele' => $word->getRawReadingElements() ?? '',
            'sense' => $word->getRawSense(),
        ];
    }
}
