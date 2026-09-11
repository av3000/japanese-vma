<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Articles;

use App\Domain\Articles\ValueObjects\ArticleDateRange;
use App\Domain\Shared\Exceptions\ValueObjectValidationException;
use PHPUnit\Framework\TestCase;

class ArticleDateRangeTest extends TestCase
{
    public function test_no_input_produces_no_range(): void
    {
        $this->assertNull(ArticleDateRange::fromInput(null, null));
        $this->assertNull(ArticleDateRange::fromInput('', ''));
    }

    /**
     * The boundary that matters: created_to must include the whole of its day, or an
     * Article created that afternoon silently falls outside a range that names it.
     */
    public function test_the_upper_bound_covers_the_entire_day(): void
    {
        $range = ArticleDateRange::fromInput(null, '2026-09-08');

        $this->assertSame('2026-09-08 23:59:59', $range->to->format('Y-m-d H:i:s'));
        $this->assertSame('999999', $range->to->format('u'));
        $this->assertNull($range->from);
    }

    public function test_the_lower_bound_starts_at_midnight(): void
    {
        $range = ArticleDateRange::fromInput('2026-09-08', null);

        $this->assertSame('2026-09-08 00:00:00', $range->from->format('Y-m-d H:i:s'));
        $this->assertNull($range->to);
    }

    public function test_a_single_day_range_is_valid(): void
    {
        $range = ArticleDateRange::fromInput('2026-09-08', '2026-09-08');

        $this->assertSame('2026-09-08 00:00:00', $range->from->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-08 23:59:59', $range->to->format('Y-m-d H:i:s'));
    }

    public function test_a_reversed_range_is_rejected(): void
    {
        $this->expectException(ValueObjectValidationException::class);

        ArticleDateRange::fromInput('2026-09-09', '2026-09-08');
    }

    public function test_an_unparseable_date_is_rejected(): void
    {
        $this->expectException(ValueObjectValidationException::class);

        ArticleDateRange::fromInput('08-09-2026', null);
    }
}
