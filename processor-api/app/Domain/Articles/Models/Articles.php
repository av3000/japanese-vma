<?php
namespace App\Domain\Articles\Models;

use Illuminate\Pagination\LengthAwarePaginator;

class Articles
{
    private LengthAwarePaginator $paginator;

    private function __construct(LengthAwarePaginator $paginator)
    {
        $this->paginator = $paginator;
    }

    public static function fromEloquentPaginator(LengthAwarePaginator $paginator): self
    {
        return new self($paginator);
    }

    /**
     * Create paginanted list from domain models array with pagination info
     */
    public static function fromArray(array $domainModels, LengthAwarePaginator $originalPaginator): self
    {
        // Create new paginator with domain models
        $newPaginator = new LengthAwarePaginator(
            $domainModels,
            $originalPaginator->total(),
            $originalPaginator->perPage(),
            $originalPaginator->currentPage(),
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );

        return new self($newPaginator);
    }

    public function toEloquentPaginator(): LengthAwarePaginator
    {
        return $this->paginator;
    }

    public function getPaginator(): LengthAwarePaginator
    {
        return $this->paginator;
    }

    public function isEmpty(): bool
    {
        return $this->paginator->isEmpty();
    }

    public function getItems(): array
    {
        return $this->paginator->items();
    }

    /**
     * Transform all articles in the list
     */
    public function transform(callable $callback): self
    {
        $transformedItems = array_map($callback, $this->getItems());
        return self::fromArray($transformedItems, $this->paginator);
    }
}
