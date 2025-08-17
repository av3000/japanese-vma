<?php
namespace App\Domain\Shared\ValueObjects;

use Carbon\Carbon;
use InvalidArgumentException;

readonly class ArticleTimestamp
{
    private Carbon $carbon;

    public function __construct(public string $value)
    {
        try {
            $this->carbon = Carbon::parse($this->value);
        } catch (\Exception $e) {
            throw new InvalidArgumentException('Invalid timestamp format');
        }
    }

    public static function fromDateTime(\DateTime $dateTime): self
    {
        return new self($dateTime->format('Y-m-d H:i:s'));
    }

    public function formatted(string $format = 'Y-m-d H:i:s'): string
    {
        return $this->carbon->format($format);
    }

    public function isOlderThan(int $days): bool
    {
        return $this->carbon->diffInDays(now()) > $days;
    }

    public function isNewerThan(int $days): bool
    {
        return $this->carbon->diffInDays(now()) < $days;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
