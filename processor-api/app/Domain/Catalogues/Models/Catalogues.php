<?php

namespace App\Domain\Catalogues\Models;

use App\Shared\Utils\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Domain collection of Catalogue domain models.
 */
final class Catalogues
{
    private Paginator $inner;

    private function __construct(Paginator $inner)
    {
        $this->inner = $inner;
    }

    public static function fromEloquentPaginator(LengthAwarePaginator $paginator): self
    {
        $inner = Paginator::fromEloquentPaginator($paginator, Catalogue::class);
        return new self($inner);
    }

    /**
     * @param Catalogue[] $domainModels
     */
    public static function fromArray(array $domainModels, LengthAwarePaginator $originalPaginator): self
    {
        $inner = Paginator::fromArray($domainModels, $originalPaginator, Catalogue::class);
        return new self($inner);
    }

    public function getPaginator(): LengthAwarePaginator
    {
        return $this->inner->getPaginator();
    }

    public function toEloquentPaginator(): LengthAwarePaginator
    {
        return $this->inner->toEloquentPaginator();
    }

    public function isEmpty(): bool
    {
        return $this->inner->isEmpty();
    }

    /**
     * @return Catalogue[]
     */
    public function getItems(): array
    {
        return $this->inner->getItems();
    }
}
