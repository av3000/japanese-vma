<?php
namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;

readonly class Pagination
{
    private const MIN_PAGE = 1;
    private const MAX_PER_PAGE = 100;
    private const DEFAULT_PER_PAGE = 20;

    public function __construct(
        public int $page,
        public int $per_page
    ) {
        if ($this->page < self::MIN_PAGE) {
            throw new InvalidArgumentException('Page must be at least ' . self::MIN_PAGE);
        }
        if ($this->per_page <= 0 || $this->per_page > self::MAX_PER_PAGE) {
            throw new InvalidArgumentException('Per page must be between 1 and ' . self::MAX_PER_PAGE);
        }
    }

    public static function fromInputOrDefault(?int $page, ?int $per_page): ?self
    {
        return new self(
            $page ?? self::MIN_PAGE,
            $per_page ?? self::DEFAULT_PER_PAGE
        );
    }

    public static function default(): self
    {
        return new self(self::MIN_PAGE, self::DEFAULT_PER_PAGE);
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->per_page;
    }

    public function isFirstPage(): bool
    {
        return $this->page === self::MIN_PAGE;
    }
}
