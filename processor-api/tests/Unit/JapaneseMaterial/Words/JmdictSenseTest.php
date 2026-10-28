<?php

declare(strict_types=1);

namespace Tests\Unit\JapaneseMaterial\Words;

use App\Domain\JapaneseMaterial\Words\ValueObjects\JmdictSense;
use PHPUnit\Framework\TestCase;

final class JmdictSenseTest extends TestCase
{
    public function test_glosses_are_read_from_every_sense_in_order_without_repeats(): void
    {
        $sense = json_encode([
            [['pos', 'n'], ['gloss', 'water'], ['gloss', ['fluid', ['liquid']]]],
            [['gloss', 'water'], ['gloss', '  cold water  ']],
        ]);

        self::assertSame(['water', 'fluid', 'liquid', 'cold water'], JmdictSense::glosses($sense));
    }

    public function test_entries_that_are_not_glosses_or_not_lists_are_skipped(): void
    {
        $sense = json_encode([[['pos', 'n'], 'stray', ['gloss', ''], ['gloss', null]], 'stray']);

        self::assertSame([], JmdictSense::glosses($sense));
    }

    public function test_malformed_json_and_null_give_no_glosses(): void
    {
        self::assertSame([], JmdictSense::glosses('{not json'));
        self::assertSame([], JmdictSense::glosses('"a string"'));
        self::assertSame([], JmdictSense::glosses(null));
    }
}
