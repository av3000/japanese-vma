<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Kanjis\Models;

use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji as DomainKanji;
use Illuminate\Pagination\LengthAwarePaginator;

// A domain collection for paginated Kanji entities.
final readonly class Kanjis
{
    private LengthAwarePaginator $paginator;

    private function __construct(LengthAwarePaginator $paginator)
    {
        foreach ($paginator->getCollection() as $item) {
            if (!$item instanceof DomainKanji) {
                throw new \InvalidArgumentException('Paginator collection must contain only DomainKanji instances.');
            }
        }
        $this->paginator = $paginator;
    }

    public static function fromEloquentPaginator(LengthAwarePaginator $paginator): self
    {
        return new self($paginator);
    }

    public static function fromArray(array $domainModels, LengthAwarePaginator $originalPaginator): self
    {
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

    /**
     * @return DomainKanji[]
     */
    public function getItems(): array
    {
        return $this->paginator->items();
    }

    // Transforms all kanjis in the list using a callable.
    // @param callable $callback A callback function to transform each DomainKanji.
    // @return self A new Kanjis collection with transformed items.
    public function transform(callable $callback): self
    {
        $transformedItems = array_map($callback, $this->getItems());
        return self::fromArray($transformedItems, $this->paginator);
    }
}
