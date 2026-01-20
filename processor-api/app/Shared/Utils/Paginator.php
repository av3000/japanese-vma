<?php

namespace App\Shared\Utils;

use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

/**
 *  Paginator wrapper around a LengthAwarePaginator that enforces/annotates the
 * type of items in the collection.
 *
 * @template T
 */
// TODO: Consider PHPStan analyzer
final class Paginator
{
    /**
     * @var LengthAwarePaginator
     * @phpstan-var LengthAwarePaginator<T>
     * @var LengthAwarePaginator<T>
     */
    private LengthAwarePaginator $paginator;

    /**
     * Validator used to check items at runtime.
     * - string: class name to check instanceof
     * - callable: fn(mixed): bool
     * - null: no validation
     *
     * @var string|callable|null
     */
    private $validator;

    /**
     * @param LengthAwarePaginator $paginator
     * @param string|callable|null $validator
     *
     * @phpstan-param LengthAwarePaginator<T> $paginator
     */
    public function __construct(LengthAwarePaginator $paginator, $validator = null)
    {
        $this->validator = $validator;
        if ($validator !== null) {
            foreach ($paginator->getCollection() as $item) {
                if (!$this->validateItem($item)) {
                    throw new InvalidArgumentException('Paginator collection contains invalid item.');
                }
            }
        }

        $this->paginator = $paginator;
    }

    /**
     * Create a wrapper from an existing LengthAwarePaginator.
     *
     * @param LengthAwarePaginator $paginator
     * @param string|callable|null $validator
     * @return self
     *
     * @phpstan-param LengthAwarePaginator<T> $paginator
     * @phpstan-return self<T>
     */
    public static function fromEloquentPaginator(LengthAwarePaginator $paginator, $validator = null): self
    {
        return new self($paginator, $validator);
    }

    /**
     * Create a wrapper from an array of domain models using the pagination
     * metadata from the original paginator.
     *
     * @param array $domainModels
     * @param LengthAwarePaginator $originalPaginator
     * @param string|callable|null $validator
     * @return self
     *
     * @phpstan-param array<T> $domainModels
     * @phpstan-param LengthAwarePaginator<T> $originalPaginator
     * @phpstan-return self<T>
     */
    public static function fromArray(array $domainModels, LengthAwarePaginator $originalPaginator, $validator = null): self
    {
        if ($validator !== null) {
            foreach ($domainModels as $m) {
                if (!self::staticValidate($m, $validator)) {
                    throw new InvalidArgumentException('Array contains invalid item.');
                }
            }
        }

        $newPaginator = new LengthAwarePaginator(
            $domainModels,
            $originalPaginator->total(),
            $originalPaginator->perPage(),
            $originalPaginator->currentPage(),
            [
                'path' => request()->url(),
                'pageName' => method_exists($originalPaginator, 'getPageName')
                    ? $originalPaginator->getPageName()
                    : 'page',
            ]
        );

        return new self($newPaginator, $validator);
    }

    /**
     * Return underlying LengthAwarePaginator.
     *
     * @return LengthAwarePaginator
     *
     * @phpstan-return LengthAwarePaginator<T>
     */
    public function toEloquentPaginator(): LengthAwarePaginator
    {
        return $this->paginator;
    }

    /**
     * Alias for toEloquentPaginator for your existing API compatibility.
     *
     * @return LengthAwarePaginator
     *
     * @phpstan-return LengthAwarePaginator<T>
     */
    public function getPaginator(): LengthAwarePaginator
    {
        return $this->paginator;
    }

    public function isEmpty(): bool
    {
        return $this->paginator->isEmpty();
    }

    /**
     * Items as array.
     *
     * @return array
     *
     * @phpstan-return array<T>
     */
    public function getItems(): array
    {
        return $this->paginator->items();
    }

    /**
     * Transform all items with $callback.
     *
     * If the callback changes the item type, provide $outputValidator
     * describing the new item type (class name or callable). If omitted,
     * no validation will be enforced for the transformed type.
     *
     * @template U
     * @param callable $callback fn(T): U
     * @param string|callable|null $outputValidator
     * @return Paginator
     *
     * @phpstan-param callable(T): U $callback
     * @phpstan-param string|callable|null $outputValidator
     * @phpstan-return Paginator<U>
     */
    public function transform(callable $callback, $outputValidator = null): Paginator
    {
        $transformedItems = array_map($callback, $this->getItems());

        // Build a paginator that keeps original pagination meta but new items.
        $newPaginator = new LengthAwarePaginator(
            $transformedItems,
            $this->paginator->total(),
            $this->paginator->perPage(),
            $this->paginator->currentPage(),
            [
                'path' => request()->url(),
                'pageName' => method_exists($this->paginator, 'getPageName')
                    ? $this->paginator->getPageName()
                    : 'page',
            ]
        );

        return new Paginator($newPaginator, $outputValidator);
    }

    /**
     * Runtime item validation using the instance validator.
     *
     * @param mixed $item
     * @return bool
     */
    private function validateItem($item): bool
    {
        return self::staticValidate($item, $this->validator);
    }

    /**
     * Static validation helper for classname/callable/null.
     *
     * @param mixed $item
     * @param string|callable|null $validator
     * @return bool
     */
    private static function staticValidate($item, $validator): bool
    {
        if ($validator === null) {
            return true;
        }

        if (is_string($validator)) {
            return $item instanceof $validator;
        }

        if (is_callable($validator)) {
            return (bool) ($validator)($item);
        }

        throw new InvalidArgumentException('Validator must be a class name or callable.');
    }
}
