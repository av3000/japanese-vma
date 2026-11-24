<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp;

use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\JlptLevel;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiCharacter;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiGrade;
use App\Domain\JapaneseMaterial\Words\Models\Word;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Mcp\Presenters\KanjiPresenter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class KanjiPresenterTest extends TestCase
{
    private const KANJI_UUID = '6f1c2f0e-8f5d-4d1e-9d57-0f4d3f6c9a10';

    public function test_a_kanji_is_shaped_for_an_assistant_without_ids_or_example_words(): void
    {
        $shaped = (new KanjiPresenter())->present($this->kanji('5', '1'));

        $this->assertSame([
            'uuid' => self::KANJI_UUID,
            'character' => '水',
            'meanings' => ['water'],
            'onyomi' => ['スイ'],
            'kunyomi' => ['みず'],
            'strokes' => 4,
            'level' => 'N5',
            'grade' => 1,
            'radicals' => ['水'],
        ], $shaped);
    }

    public function test_example_words_are_added_as_word_reading_and_meaning(): void
    {
        $shaped = (new KanjiPresenter())->presentWithExamples($this->kanji('5', '1'), [
            $this->word(1, '水道', 'すいどう', ['water supply']),
            $this->word(2, '水', 'みず', ['water', 'fluid', 'liquid', 'flood']),
        ]);

        $this->assertSame([
            ['word' => '水道', 'reading' => 'すいどう', 'meaning' => 'water supply'],
            ['word' => '水', 'reading' => 'みず', 'meaning' => 'water; fluid; liquid'],
        ], $shaped['example_words']);
    }

    public function test_a_kanji_without_example_words_has_an_empty_list(): void
    {
        $shaped = (new KanjiPresenter())->presentWithExamples($this->kanji('5', '1'), []);

        $this->assertSame([], $shaped['example_words']);
    }

    /**
     * @return array<string, array{string, ?string}>
     */
    public static function levels(): array
    {
        return [
            '1 is N1' => ['1', 'N1'],
            '2 is N2' => ['2', 'N2'],
            '3 is N3' => ['3', 'N3'],
            '4 is N4' => ['4', 'N4'],
            '5 is N5' => ['5', 'N5'],
            '- is null' => ['-', null],
        ];
    }

    #[DataProvider('levels')]
    public function test_levels_come_back_as_n_levels_and_dash_as_null(string $stored, ?string $expected): void
    {
        $shaped = (new KanjiPresenter())->present($this->kanji($stored, '-'));

        $this->assertSame($expected, $shaped['level']);
        $this->assertNull($shaped['grade']);
    }

    private function kanji(string $jlpt, string $grade): Kanji
    {
        return new Kanji(
            id: 7,
            uuid: new EntityId(self::KANJI_UUID),
            character: new KanjiCharacter('水'),
            onyomi: ['スイ'],
            kunyomi: ['みず'],
            meanings: ['water'],
            nanori: ['み'],
            grade: $grade === '-' ? null : new KanjiGrade($grade),
            strokeCount: 4,
            jlpt: new JlptLevel($jlpt),
            frequency: 2,
            radicals: ['水'],
            radicalParts: ['水'],
        );
    }

    /**
     * @param array<int, string> $meanings
     */
    private function word(int $id, string $surface, string $furigana, array $meanings): Word
    {
        return new Word(
            id: $id,
            uuid: new EntityId('00000000-0000-4000-8000-00000000000'.$id),
            surface: $surface,
            furigana: $furigana,
            jlpt: '-',
            wordTypes: ['noun'],
            writingElements: [$surface],
            readingElements: [$furigana],
            meanings: $meanings,
            rawWordType: null,
            rawWritingElements: null,
            rawReadingElements: null,
            rawSense: null,
        );
    }
}
