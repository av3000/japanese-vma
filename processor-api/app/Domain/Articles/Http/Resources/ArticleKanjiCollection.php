<?php
// namespace App\Domain\Articles\Http\Resources;

// use Illuminate\Http\Resources\Json\JsonResource;

// class KanjiResource extends JsonResource
// {
//     public function toArray($request): array
//     {
//         return [
//             'id' => $this->id,
//             'kanji' => $this->kanji,
//             'meaning' => $this->meaning,
//             'reading' => $this->reading,
//             'jlpt' => $this->jlpt,
//             // Add other kanji fields as needed
//         ];
//     }
// }

namespace App\Domain\Articles\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ArticleKanjiCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'success' => true,
            'kanjis' => $this->collection,
            'message' => 'Article kanjis fetched'
        ];
    }
}
