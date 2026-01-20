<?php

namespace App\Domain\Articles\Models;

use App\Domain\Articles\Models\Article;

use App\Shared\Utils\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Domain collection of Article domain models.
 */
final class Articles
{
    private Paginator $inner;

    private function __construct(Paginator $inner)
    {
        $this->inner = $inner;
    }

    public static function fromEloquentPaginator(LengthAwarePaginator $paginator): self
    {
        $inner = Paginator::fromEloquentPaginator($paginator, Article::class);
        return new self($inner);
    }

    /**
     * @param Article[] $domainModels
     */
    public static function fromArray(array $domainModels, LengthAwarePaginator $originalPaginator): self
    {
        $inner = Paginator::fromArray($domainModels, $originalPaginator, Article::class);
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
     * @return Article[]
     */
    public function getItems(): array
    {
        return $this->inner->getItems();
    }

    /**
     * Transform keeps typed wrapper: if transform returns Article instances, keep Articles,
     * otherwise return a Paginator of the new type via transform on inner.
     *
     * Accepts a callback fn(Article): U and optional output validator.
     *
     * @template U
     * @param callable $callback
     * @param string|callable|null $outputValidator
     * @return mixed  // either Articles (if U is Article) or Paginator<U>
     */
    public function transform(callable $callback, $outputValidator = null)
    {
        $result = $this->inner->transform($callback, $outputValidator);

        // If the caller expects Articles (i.e., output validator is Article::class or omitted),
        if ($outputValidator === Article::class || $outputValidator === null) {
            // Runtime-check: ensure transformed items are Article instances
            return new self($result instanceof Paginator ? $result : Paginator::fromEloquentPaginator($result->getPaginator(), Article::class));
        }

        return $result;
    }
}
