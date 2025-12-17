<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Kanjis\Resources;

use App\Http\v1\JapaneseMaterial\Kanjis\Resources\KanjiResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class KanjiCollectionResource extends ResourceCollection
{
    public $collects = KanjiResource::class;

    public function __construct(LengthAwarePaginator $resource)
    {
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
            'pagination' => [
                'total' => $this->total(),
                'count' => $this->count(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'total_pages' => $this->lastPage(),
                'next_page_url' => $this->nextPageUrl(),
                'prev_page_url' => $this->previousPageUrl(),
            ],
        ];
    }
}
