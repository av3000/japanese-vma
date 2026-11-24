<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Application\JapaneseMaterial\Kanjis\Services\KanjiDetailServiceInterface;
use App\Application\JapaneseMaterial\Kanjis\Services\KanjiServiceInterface;
use App\Mcp\Servers\StudyServer;
use App\Mcp\Tools\LookupKanjiTool;
use App\Shared\Enums\HttpStatus;
use App\Shared\Results\Result;
use App\Shared\Results\ResultError;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class LookupKanjiToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_tool_is_registered_read_only_and_describes_its_input_and_output(): void
    {
        $tool = app(LookupKanjiTool::class)->toArray();

        $this->assertSame('lookup_kanji', $tool['name']);
        $this->assertTrue($tool['annotations']['readOnlyHint']);

        $input = $tool['inputSchema'];
        $this->assertSame(['character', 'meaning', 'limit', 'page'], array_keys($input['properties']));
        $this->assertSame([], $input['required'] ?? []);
        $this->assertSame(20, $input['properties']['limit']['maximum']);
        $this->assertSame(5, $input['properties']['limit']['default']);
        $this->assertSame(1, $input['properties']['page']['default']);

        $output = $tool['outputSchema'];
        $this->assertSame(['results', 'total', 'has_more'], array_keys($output['properties']));
        $this->assertSame(
            ['uuid', 'character', 'meanings', 'onyomi', 'kunyomi', 'strokes', 'level', 'grade', 'radicals', 'example_words'],
            array_keys($output['properties']['results']['items']['properties']),
        );
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function wrongNumberOfSearchInputs(): array
    {
        return [
            'neither' => [[]],
            'both' => [['character' => '水', 'meaning' => 'water']],
            'a blank character alone' => [['character' => '  ']],
            'a blank meaning alone' => [['meaning' => '']],
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     */
    #[DataProvider('wrongNumberOfSearchInputs')]
    public function test_exactly_one_of_character_or_meaning_is_required_and_no_service_is_called(array $arguments): void
    {
        $this->expectNoServiceCall();

        StudyServer::tool(LookupKanjiTool::class, $arguments)
            ->assertHasErrors(['exactly one of character or meaning']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function notASingleKanji(): array
    {
        return [
            'hiragana' => ['みず'],
            'katakana' => ['スイ'],
            'latin text' => ['water'],
            'one latin letter' => ['a'],
            'a digit' => ['4'],
            'two kanji' => ['水火'],
            'a kanji with a kana' => ['水の'],
        ];
    }

    #[DataProvider('notASingleKanji')]
    public function test_a_character_that_is_not_a_single_kanji_is_an_error_with_an_example_and_no_service_is_called(string $character): void
    {
        $this->expectNoServiceCall();

        StudyServer::tool(LookupKanjiTool::class, ['character' => $character])
            ->assertHasErrors(['character', 'single kanji', '水']);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidPaging(): array
    {
        return [
            'a limit of 0' => [['meaning' => 'water', 'limit' => 0], 'limit'],
            'a limit of 21' => [['meaning' => 'water', 'limit' => 21], 'limit'],
            'a page of 0' => [['meaning' => 'water', 'page' => 0], 'page'],
            'a 256 character meaning' => [['meaning' => str_repeat('a', 256)], 'meaning'],
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     */
    #[DataProvider('invalidPaging')]
    public function test_invalid_paging_or_length_is_an_error_naming_the_field_and_no_service_is_called(array $arguments, string $field): void
    {
        $this->expectNoServiceCall();

        StudyServer::tool(LookupKanjiTool::class, $arguments)->assertHasErrors([$field]);
    }

    public function test_a_character_lookup_returns_one_result_with_the_documented_shape(): void
    {
        $uuid = (string) Str::uuid();
        $this->createKanji(id: 1, uuid: $uuid, character: '学', meaning: 'study, learning', onyomi: 'ガク', kunyomi: 'まな.ぶ', strokes: '8', jlpt: '5', grade: '1', radicals: '子,冖');
        $this->createWord(id: 1, word: '学校', furigana: 'がっこう', meanings: ['school']);
        $this->link(kanjiId: 1, wordId: 1);

        StudyServer::tool(LookupKanjiTool::class, ['character' => '学'])
            ->assertOk()
            ->assertStructuredContent([
                'results' => [[
                    'uuid' => $uuid,
                    'character' => '学',
                    'meanings' => ['study', 'learning'],
                    'onyomi' => ['ガク'],
                    'kunyomi' => ['まな.ぶ'],
                    'strokes' => 8,
                    'level' => 'N5',
                    'grade' => 1,
                    'radicals' => ['子', '冖'],
                    'example_words' => [
                        ['word' => '学校', 'reading' => 'がっこう', 'meaning' => 'school'],
                    ],
                ]],
                'total' => 1,
                'has_more' => false,
            ])
            ->assertStructuredContentMissingPaths(['results.0.id', 'results.0.nanori', 'results.0.frequency']);
    }

    public function test_example_words_are_the_first_five_linked_words_by_id(): void
    {
        $this->createKanji(id: 1, character: '水');
        $this->createKanji(id: 2, character: '火');

        foreach (range(1, 7) as $id) {
            $this->createWord(id: $id, word: "水{$id}", furigana: 'みず', meanings: ["water {$id}"]);
            $this->link(kanjiId: 1, wordId: $id);
        }

        $this->createWord(id: 8, word: '火事', furigana: 'かじ', meanings: ['fire']);
        $this->link(kanjiId: 2, wordId: 8);

        $response = StudyServer::tool(LookupKanjiTool::class, ['character' => '水'])->assertOk();

        $response->assertStructuredContentCount(5, 'results.0.example_words')
            ->assertStructuredContentPaths([
                'results.0.example_words.0.word' => '水1',
                'results.0.example_words.4.word' => '水5',
            ]);
    }

    /**
     * @return array<string, array{string, ?string}>
     */
    public static function storedLevels(): array
    {
        return [
            '1' => ['1', 'N1'],
            '2' => ['2', 'N2'],
            '3' => ['3', 'N3'],
            '4' => ['4', 'N4'],
            '5' => ['5', 'N5'],
            'dash' => ['-', null],
        ];
    }

    #[DataProvider('storedLevels')]
    public function test_levels_one_to_five_are_n_levels_and_dash_is_null(string $stored, ?string $expected): void
    {
        $this->createKanji(id: 1, character: '水', jlpt: $stored, grade: '-');

        StudyServer::tool(LookupKanjiTool::class, ['character' => '水'])
            ->assertOk()
            ->assertStructuredContentPaths(['results.0.level' => $expected, 'results.0.grade' => null]);

        StudyServer::tool(LookupKanjiTool::class, ['meaning' => 'water'])
            ->assertOk()
            ->assertStructuredContentPaths(['results.0.level' => $expected]);
    }

    public function test_a_meaning_search_returns_kanji_without_example_words(): void
    {
        $this->createKanji(id: 1, character: '水', meaning: 'water');
        $this->createKanji(id: 2, character: '泳', meaning: 'swim');
        $this->createWord(id: 1, word: '水道', furigana: 'すいどう', meanings: ['water supply']);
        $this->link(kanjiId: 1, wordId: 1);

        StudyServer::tool(LookupKanjiTool::class, ['meaning' => 'water'])
            ->assertOk()
            ->assertStructuredContentPaths(['total' => 1, 'has_more' => false, 'results.0.character' => '水'])
            ->assertStructuredContentCount(1, 'results')
            ->assertStructuredContentMissingPaths(['results.0.example_words']);
    }

    public function test_a_meaning_search_pages_with_total_and_has_more_and_the_last_page_has_no_more(): void
    {
        foreach (range(1, 7) as $id) {
            $this->createKanji(id: $id, character: mb_substr('一二三四五六七', $id - 1, 1), meaning: "number {$id}, count");
        }

        $expected = [
            1 => [3, true, '一'],
            2 => [3, true, '四'],
            3 => [1, false, '七'],
        ];

        foreach ($expected as $page => [$count, $hasMore, $first]) {
            StudyServer::tool(LookupKanjiTool::class, ['meaning' => 'count', 'limit' => 3, 'page' => $page])
                ->assertOk()
                ->assertStructuredContentCount($count, 'results')
                ->assertStructuredContentPaths([
                    'total' => 7,
                    'has_more' => $hasMore,
                    'results.0.character' => $first,
                ]);
        }
    }

    public function test_a_meaning_search_returns_five_results_by_default_and_page_defaults_to_one(): void
    {
        foreach (range(1, 6) as $id) {
            $this->createKanji(id: $id, character: mb_substr('一二三四五六', $id - 1, 1), meaning: 'count');
        }

        StudyServer::tool(LookupKanjiTool::class, ['meaning' => 'count'])
            ->assertOk()
            ->assertStructuredContentCount(5, 'results')
            ->assertStructuredContentPaths(['total' => 6, 'has_more' => true, 'results.0.character' => '一']);
    }

    public function test_a_page_past_the_end_is_empty_and_has_no_more(): void
    {
        $this->createKanji(id: 1, character: '水', meaning: 'water');

        StudyServer::tool(LookupKanjiTool::class, ['meaning' => 'water', 'page' => 2])
            ->assertOk()
            ->assertStructuredContent(['results' => [], 'total' => 1, 'has_more' => false]);
    }

    public function test_a_kanji_not_in_the_dictionary_is_an_ok_empty_result(): void
    {
        $this->createKanji(id: 1, character: '水');

        StudyServer::tool(LookupKanjiTool::class, ['character' => '火'])
            ->assertOk()
            ->assertStructuredContent(['results' => [], 'total' => 0, 'has_more' => false]);
    }

    public function test_a_meaning_nothing_matches_is_an_ok_empty_result(): void
    {
        $this->createKanji(id: 1, character: '水', meaning: 'water');

        StudyServer::tool(LookupKanjiTool::class, ['meaning' => 'zzzz'])
            ->assertOk()
            ->assertStructuredContent(['results' => [], 'total' => 0, 'has_more' => false]);
    }

    public function test_a_backslash_or_wildcard_in_a_meaning_is_searched_without_failing(): void
    {
        $this->createKanji(id: 1, character: '水', meaning: 'water');

        StudyServer::tool(LookupKanjiTool::class, ['meaning' => 'a\\'])->assertOk();
        StudyServer::tool(LookupKanjiTool::class, ['meaning' => '_'])->assertOk();
    }

    public function test_a_service_failure_other_than_not_found_is_an_error_the_client_can_read(): void
    {
        $this->mock(KanjiDetailServiceInterface::class)
            ->shouldReceive('findByIdentifier')
            ->once()
            ->andReturn(Result::failure(new ResultError('BOOM', HttpStatus::INTERNAL_SERVER_ERROR, 'database down')));

        StudyServer::tool(LookupKanjiTool::class, ['character' => '水'])
            ->assertHasErrors(['could not be read']);
    }

    private function expectNoServiceCall(): void
    {
        $this->mock(KanjiServiceInterface::class)
            ->shouldNotReceive('find')
            ->shouldNotReceive('findByCharacter')
            ->shouldNotReceive('findByIdentifier')
            ->shouldNotReceive('count');
        $this->mock(KanjiDetailServiceInterface::class)->shouldNotReceive('findByIdentifier');
    }

    private function createKanji(
        int $id,
        string $character = '水',
        ?string $uuid = null,
        string $meaning = 'water',
        string $onyomi = 'スイ',
        string $kunyomi = 'みず',
        string $strokes = '4',
        string $jlpt = '5',
        string $grade = '1',
        string $radicals = '水',
    ): void {
        DB::table('japanese_kanji_bank_long')->insert([
            'id' => $id,
            'uuid' => $uuid ?? (string) Str::uuid(),
            'kanji' => $character,
            'onyomi' => $onyomi,
            'kunyomi' => $kunyomi,
            'meaning' => $meaning,
            'nanori' => '',
            'grade' => $grade,
            'stroke_count' => $strokes,
            'jlpt' => $jlpt,
            'frequency' => '2',
            'radicals' => $radicals,
            'radical_parts' => $radicals,
        ]);
    }

    /**
     * @param array<int, string> $meanings
     */
    private function createWord(int $id, string $word, string $furigana, array $meanings): void
    {
        DB::table('japanese_word_bank_long')->insert([
            'id' => $id,
            'uuid' => (string) Str::uuid(),
            'entry_sequence' => (string) (1000 + $id),
            'word' => $word,
            'furigana' => $furigana,
            'jlpt' => '-',
            'word_type' => 'noun',
            'word_k_ele' => $word,
            'furigana_r_ele' => $furigana,
            'sense' => json_encode([[['gloss', $meanings]]], JSON_THROW_ON_ERROR),
        ]);
    }

    private function link(int $kanjiId, int $wordId): void
    {
        DB::table('japanese_kanji_word_long')->insert(['kanji_id' => $kanjiId, 'word_id' => $wordId]);
    }
}
