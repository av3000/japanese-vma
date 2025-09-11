<?php
namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;

readonly class Pagination
{
    private const MIN_PAGE = 1;
    private const MAX_PER_PAGE = 100;
    private const DEFAULT_PER_PAGE = 20;

    public function __construct(
        public int $page = self::MIN_PAGE,
        public int $perPage = self::DEFAULT_PER_PAGE
    ) {
        if ($this->page < self::MIN_PAGE) {
            throw new InvalidArgumentException('Page must be at least ' . self::MIN_PAGE);
        }

        if ($this->perPage <= 0 || $this->perPage > self::MAX_PER_PAGE) {
            throw new InvalidArgumentException('Per page must be between 1 and ' . self::MAX_PER_PAGE);
        }
    }

    public static function fromPrimitives(int $page, int $perPage): self
    {
        return new self($page, $perPage);
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function isFirstPage(): bool
    {
        return $this->page === self::MIN_PAGE;
    }
}
