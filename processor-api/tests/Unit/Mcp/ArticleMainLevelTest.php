<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp;

use App\Domain\Shared\ValueObjects\JlptLevels;
use App\Mcp\Support\ArticleMainLevel;
use App\Mcp\Support\StudyLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ArticleMainLevelTest extends TestCase
{
    /**
     * @return array<string, array{array{n5: int, n4: int, n3: int, n2: int, n1: int, uncommon: int}, ?StudyLevel}>
     */
    public static function articles(): array
    {
        return [
            'no kanji at all' => [self::counts(), null],
            'only N5' => [self::counts(n5: 12), StudyLevel::N5],
            'exactly 90% at N5' => [self::counts(n5: 9, n1: 1), StudyLevel::N5],
            'just under 90% at N5 moves up a level' => [self::counts(n5: 89, n4: 11), StudyLevel::N4],
            'exactly 90% at N4, counting the N5 kanji too' => [self::counts(n5: 40, n4: 50, n3: 10), StudyLevel::N4],
            'a mix that lands on N3' => [self::counts(n5: 30, n4: 30, n3: 30, n2: 8, n1: 2), StudyLevel::N3],
            'a hard article with mostly N2 and N1 kanji' => [self::counts(n5: 5, n4: 5, n3: 10, n2: 40, n1: 40), StudyLevel::N1],
            'the issue example reaches 90% only at N1' => [self::counts(n5: 30, n4: 45, n3: 40, n2: 25, n1: 15, uncommon: 5), StudyLevel::N1],
            'exactly 10% uncommon still leaves N5 at 90%' => [self::counts(n5: 90, uncommon: 10), StudyLevel::N5],
            'more than 10% uncommon has no level' => [self::counts(n5: 40, n4: 30, n3: 10, n2: 5, n1: 4, uncommon: 11), null],
            'only uncommon kanji has no level' => [self::counts(uncommon: 7), null],
        ];
    }

    /**
     * @param array{n5: int, n4: int, n3: int, n2: int, n1: int, uncommon: int} $counts
     */
    #[DataProvider('articles')]
    public function test_the_main_level_is_the_easiest_level_that_covers_ninety_percent_of_the_kanji(array $counts, ?StudyLevel $expected): void
    {
        $this->assertSame($expected, ArticleMainLevel::fromCounts(new JlptLevels(...$counts)));
    }

    /**
     * @return array{n5: int, n4: int, n3: int, n2: int, n1: int, uncommon: int}
     */
    private static function counts(int $n5 = 0, int $n4 = 0, int $n3 = 0, int $n2 = 0, int $n1 = 0, int $uncommon = 0): array
    {
        return ['n5' => $n5, 'n4' => $n4, 'n3' => $n3, 'n2' => $n2, 'n1' => $n1, 'uncommon' => $uncommon];
    }
}
