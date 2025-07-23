<?php
// namespace App\Domain\Articles\Http\Resources;

// use Illuminate\Http\Resources\Json\JsonResource;

// class WordResource extends JsonResource
// {
//     public function toArray($request): array
//     {
//         return [
//             'id' => $this->id,
//             'word' => $this->word,
//             'reading' => $this->reading,
//             'meaning' => $this->meaning,
//             'jlpt' => $this->jlpt,
//             // Add other word fields as needed
//         ];
//     }
// }

namespace App\Domain\Articles\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ArticleWordCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'success' => true,
            'words' => $this->collection,
            'message' => 'Article words fetched'
        ];
    }
}
