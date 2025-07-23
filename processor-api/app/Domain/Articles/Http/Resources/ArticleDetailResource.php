<?php

namespace App\Domain\Articles\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
// use App\Domain\Articles\DTOs\JlptLevelsDTO;
// use App\Domain\Articles\DTOs\StatsDTO;
use App\Domain\Articles\DTOs\ArticleDTO;

class ArticleDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'success' => true,
            'article' => ArticleDTO::fromModel($this)->toDetailArray(),
            'message' => 'Article details fetched',
        ];
    }
}

// 'success' => true,
// 'article' => [
//     'id' => $this->id,
//     'title_jp' => $this->title_jp,
//     'title_en' => $this->title_en,
//     'content_jp' => $this->content_jp,
//     'content_en' => $this->content_en,
//     'source_link' => $this->source_link,
//     'publicity' => $this->publicity,
//     'status' => $this->status,
//     'jlpt_levels' => JlptLevelsDTO::fromModel($article)->toArray(),
//     'jlptcommon' => $this->jlptcommon ?? 0,
//     'stats' => StatsDTO::fromModel($article)->toArray(),
//     'author' => [
//         'id' => $this->user->id,
//         'name' => $this->user->name,
//     ],
//     'hashtags' => $this->hashtags ?? [],
//     'comments' => $this->comments ?? [],
//     'kanjis' => $this->kanjis ?? [],
//     'words' => $this->words ?? [],
//     'created_at' => $this->created_at->toDateTimeString(),
//     'updated_at' => $this->updated_at->toDateTimeString(),
// ]
