<?php

namespace App\Domain\Comments\Models;

use App\Shared\Utils\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

final class Comments
{
    private Paginator $inner;

    private function __construct(Paginator $inner)
    {
        $this->inner = $inner;
    }

    public static function fromEloquentPaginator(LengthAwarePaginator $paginator): self
    {
        $inner = Paginator::fromEloquentPaginator($paginator, Comment::class);
        return new self($inner);
    }

    /**
     * @param Comment[] $domainModels
     */
    public static function fromArray(array $domainModels, LengthAwarePaginator $originalPaginator): self
    {
        $inner = Paginator::fromArray($domainModels, $originalPaginator, Comment::class);
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
     * @return Comment[]
     */
    public function getItems(): array
    {
        return $this->inner->getItems();
    }

    /**
     * Transform keeps typed wrapper: if transform returns Comment instances, keep Comments,
     * otherwise return a Paginator of the new type via transform on inner.
     *
     * Accepts a callback fn(Comment): U and optional output validator.
     *
     * @template U
     * @param callable $callback
     * @param string|callable|null $outputValidator
     * @return mixed  // either Comments (if U is Comment) or Paginator<U>
     */
    public function transform(callable $callback, $outputValidator = null)
    {
        $result = $this->inner->transform($callback, $outputValidator);

        // If the caller expects Comments (i.e., output validator is Comment::class or omitted),
        if ($outputValidator === Comment::class || $outputValidator === null) {
            // Runtime-check: ensure transformed items are Comment instances
            return new self($result instanceof Paginator ? $result : Paginator::fromEloquentPaginator($result->getPaginator(), Comment::class));
        }

        return $result;
    }
}
