<?php

declare(strict_types=1);

namespace App\Application\Articles\DTOs;

/**
 * One facet dimension and its available choices.
 */
final readonly class ArticleFacetDTO
{
    public const TYPE_MULTI = 'multi';

    /**
     * @param array<int, ArticleFacetValueDTO> $values
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $type,
        public array $values,
    ) {
    }

    /**
     * @param array<int, ArticleFacetValueDTO> $values
     */
    public static function multi(string $key, string $label, array $values): self
    {
        return new self($key, $label, self::TYPE_MULTI, $values);
    }
}
