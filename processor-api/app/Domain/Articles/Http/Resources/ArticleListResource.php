<?php
namespace App\Domain\Articles\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Domain\Articles\DTOs\ArticleDTO;

class ArticleListResource extends ResourceCollection
{
    protected $includeStats;

    public function __construct($resource, $includeStats = false)
    {
        parent::__construct($resource);
        $this->includeStats = $includeStats;
    }

    public function toArray($request)
    {
        return [
            'success' => true,
            'articles' => $this->collection->map(function ($article) {
                return ArticleDTO::fromModel($article)->toListArray($this->includeStats);
            }),
            'message' => 'articles fetched'
        ];
    }
}
