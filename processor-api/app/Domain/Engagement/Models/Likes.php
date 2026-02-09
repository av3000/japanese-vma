<?php

namespace App\Domain\Engagement\Models;

use App\Shared\Utils\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Domain\Engagement\Models\Like;

/**
 * Domain collection of Like domain models.
 */
final class Likes
{
    private Paginator $inner;

    private function __construct(Paginator $inner)
    {
        $this->inner = $inner;
    }

    public static function fromEloquentPaginator(LengthAwarePaginator $paginator): self
    {
        $inner = Paginator::fromEloquentPaginator($paginator, Like::class);
        return new self($inner);
    }

    /**
     * @param Like[] $domainModels
     */
    public static function fromArray(array $domainModels, LengthAwarePaginator $originalPaginator): self
    {
        $inner = Paginator::fromArray($domainModels, $originalPaginator, Like::class);
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
     * @return Like[]
     */
    public function getItems(): array
    {
        return $this->inner->getItems();
    }

    /**
     * Transform keeps typed wrapper: if transform returns Like instances, keep Likes,
     * otherwise return a Paginator of the new type via transform on inner.
     *
     * Accepts a callback fn(Like): U and optional output validator.
     *
     * @template U
     * @param callable $callback
     * @param string|callable|null $outputValidator
     * @return mixed  // either Likes (if U is Like) or Paginator<U>
     */
    public function transform(callable $callback, $outputValidator = null)
    {
        $result = $this->inner->transform($callback, $outputValidator);

        // If the caller expects Likes (i.e., output validator is Like::class or omitted),
        if ($outputValidator === Like::class || $outputValidator === null) {
            // Runtime-check: ensure transformed items are Like instances
            return new self($result instanceof Paginator ? $result : Paginator::fromEloquentPaginator($result->getPaginator(), Like::class));
        }

        return $result;
    }
}
