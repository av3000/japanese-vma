<?php

namespace App\Domain\Users\Models;

use App\Domain\Users\Models\User as DomainUser;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * A domain collection for paginated User entities.
 */
class Users
{
    private LengthAwarePaginator $paginator;

    private function __construct(LengthAwarePaginator $paginator)
    {
        foreach ($paginator->getCollection() as $item) {
            if (!$item instanceof DomainUser) {
                throw new \InvalidArgumentException('Paginator collection must contain only DomainUser instances.');
            }
        }
        $this->paginator = $paginator;
    }

    /**
     * Creates a Users domain collection from an Eloquent LengthAwarePaginator.
     * The paginator's collection should already be mapped to DomainUser.
     */
    public static function fromEloquentPaginator(LengthAwarePaginator $paginator): self
    {
        return new self($paginator);
    }

    /**
     * Creates a paginated list from an array of DomainUser models with pagination info.
     */
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
     * @return DomainUser[]
     */
    public function getItems(): array
    {
        return $this->paginator->items();
    }

    /**
     * Transforms all users in the list using a callable.
     *
     * @param callable $callback A callback function to transform each DomainUser.
     * @return self A new Users collection with transformed items.
     */
    public function transform(callable $callback): self
    {
        $transformedItems = array_map($callback, $this->getItems());
        return self::fromArray($transformedItems, $this->paginator);
    }
}
