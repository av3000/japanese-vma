<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp;

use App\Mcp\Support\StudyLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StudyLevelTest extends TestCase
{
    /**
     * @return array<string, array{string, ?StudyLevel}>
     */
    public static function dictionaryValues(): array
    {
        return [
            '5 is N5' => ['5', StudyLevel::N5],
            '4 is N4' => ['4', StudyLevel::N4],
            '3 is N3' => ['3', StudyLevel::N3],
            '2 is N2' => ['2', StudyLevel::N2],
            '1 is N1' => ['1', StudyLevel::N1],
            'the unassigned marker is no level' => ['-', null],
            'an empty string is no level' => ['', null],
            'a value outside the format is no level' => ['6', null],
            'the tool format is not the dictionary format' => ['N5', null],
        ];
    }

    #[DataProvider('dictionaryValues')]
    public function test_reads_the_dictionary_format_that_kanji_and_words_share(string $stored, ?StudyLevel $expected): void
    {
        $this->assertSame($expected, StudyLevel::fromDictionary($stored));
    }

    public function test_a_missing_dictionary_value_is_no_level(): void
    {
        $this->assertNull(StudyLevel::fromDictionary(null));
    }

    public function test_writes_the_dictionary_format_for_the_word_search(): void
    {
        $this->assertSame(
            ['5', '4', '3', '2', '1'],
            array_map(static fn (StudyLevel $level): string => $level->toDictionary(), StudyLevel::cases()),
        );
    }

    /**
     * @return array<string, array{string, ?StudyLevel}>
     */
    public static function articleValues(): array
    {
        return [
            'n5 is N5' => ['n5', StudyLevel::N5],
            'n1 is N1' => ['n1', StudyLevel::N1],
            'uncommon is no level' => ['uncommon', null],
            'an unknown column is no level' => ['n6', null],
        ];
    }

    #[DataProvider('articleValues')]
    public function test_reads_the_article_format(string $stored, ?StudyLevel $expected): void
    {
        $this->assertSame($expected, StudyLevel::fromArticle($stored));
    }

    public function test_writes_the_article_format_for_the_level_filter(): void
    {
        $this->assertSame(
            ['n5', 'n4', 'n3', 'n2', 'n1'],
            array_map(static fn (StudyLevel $level): string => $level->toArticle(), StudyLevel::cases()),
        );
    }

    public function test_lists_the_names_a_client_may_send_easiest_first(): void
    {
        $this->assertSame(['N5', 'N4', 'N3', 'N2', 'N1'], StudyLevel::names());
    }
}
