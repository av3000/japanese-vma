<?php

declare(strict_types=1);

namespace App\Domain\Articles\ValueObjects;

use App\Domain\Shared\Exceptions\ValueObjectValidationException;
use DateTimeImmutable;

/**
 * An inclusive creation-date interval.
 *
 * Callers pass calendar dates; this widens them to cover the whole day, so
 * created_to=2026-09-08 includes an Article created at 23:59 that day rather than
 * silently excluding everything after midnight.
 */
final readonly class ArticleDateRange
{
    private function __construct(
        public ?DateTimeImmutable $from,
        public ?DateTimeImmutable $to,
    ) {
    }

    public static function fromInput(?string $from, ?string $to): ?self
    {
        if (($from === null || $from === '') && ($to === null || $to === '')) {
            return null;
        }

        $start = $from !== null && $from !== ''
            ? self::startOfDay($from)
            : null;

        $end = $to !== null && $to !== ''
            ? self::endOfDay($to)
            : null;

        if ($start !== null && $end !== null && $start > $end) {
            throw ValueObjectValidationException::forField(
                'created_from',
                'created_from must not be later than created_to.',
            );
        }

        return new self($start, $end);
    }

    private static function startOfDay(string $date): DateTimeImmutable
    {
        return self::parse($date, 'created_from')->setTime(0, 0, 0);
    }

    private static function endOfDay(string $date): DateTimeImmutable
    {
        return self::parse($date, 'created_to')->setTime(23, 59, 59, 999999);
    }

    private static function parse(string $date, string $field): DateTimeImmutable
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($parsed === false) {
            throw ValueObjectValidationException::forField(
                $field,
                "Invalid date: {$date}. Expected format YYYY-MM-DD.",
            );
        }

        return $parsed;
    }
}
