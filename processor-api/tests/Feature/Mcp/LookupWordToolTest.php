<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Application\JapaneseMaterial\Words\Services\WordServiceInterface;
use App\Mcp\Servers\StudyServer;
use App\Mcp\Tools\LookupWordTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class LookupWordToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_tool_is_read_only_and_describes_its_input_and_output(): void
    {
        $tool = app(LookupWordTool::class)->toArray();

        $this->assertSame('lookup_word', $tool['name']);
        $this->assertTrue($tool['annotations']['readOnlyHint']);
        $this->assertStringContainsString('levels may be missing', $tool['description']);

        $input = $tool['inputSchema'];
        $this->assertSame(['query'], $input['required']);
        $this->assertSame(1, $input['properties']['query']['minLength']);
        $this->assertSame(255, $input['properties']['query']['maxLength']);
        $this->assertSame(['N5', 'N4', 'N3', 'N2', 'N1'], $input['properties']['level']['enum']);
        $this->assertSame(1, $input['properties']['limit']['minimum']);
        $this->assertSame(20, $input['properties']['limit']['maximum']);
        $this->assertSame(5, $input['properties']['limit']['default']);
        $this->assertSame(1, $input['properties']['page']['minimum']);
        $this->assertSame(1, $input['properties']['page']['default']);

        $output = $tool['outputSchema'];
        $this->assertSame(['results', 'total', 'has_more'], array_keys($output['properties']));
        $this->assertSame(
            ['uuid', 'word', 'reading', 'meanings', 'level', 'types'],
            array_keys($output['properties']['results']['items']['properties']),
        );
    }

    public function test_a_query_returns_the_documented_shape_without_internal_keys(): void
    {
        $uuid = (string) Str::uuid();
        $this->createWord(id: 1, uuid: $uuid, word: '学校', furigana: 'がっこう', jlpt: '5', meanings: ['school', 'academy']);
        $this->createWord(id: 2, word: '勉強', furigana: 'べんきょう', meanings: ['study']);

        $response = StudyServer::tool(LookupWordTool::class, ['query' => 'school']);

        $response->assertOk()
            ->assertStructuredContent([
                'results' => [[
                    'uuid' => $uuid,
                    'word' => '学校',
                    'reading' => 'がっこう',
                    'meanings' => ['school', 'academy'],
                    'level' => 'N5',
                    'types' => ['noun'],
                ]],
                'total' => 1,
                'has_more' => false,
            ])
            ->assertStructuredContentMissingPaths(['results.0.id', 'results.0.viewer_catalogue_state']);
    }

    public function test_a_word_whose_headword_equals_the_query_comes_first_even_with_a_higher_id(): void
    {
        $this->createWord(id: 1, word: '学校生活', furigana: 'がっこうせいかつ', meanings: ['school life']);
        $this->createWord(id: 2, word: '学校', furigana: 'がっこう', meanings: ['school']);
        $this->createWord(id: 3, word: '小学校', furigana: 'しょうがっこう', meanings: ['primary school']);

        $response = StudyServer::tool(LookupWordTool::class, ['query' => '学校']);

        $response->assertOk()
            ->assertStructuredContentPaths([
                'results.0.word' => '学校',
                'results.1.word' => '学校生活',
                'results.2.word' => '小学校',
                'total' => 3,
            ]);
    }

    public function test_the_exact_headword_leads_page_one_and_no_match_is_lost_or_repeated_across_pages(): void
    {
        foreach (range(1, 5) as $id) {
            $this->createWord(id: $id, word: "水{$id}", furigana: 'みず', meanings: ['water']);
        }
        $this->createWord(id: 6, word: '水', furigana: 'みず', meanings: ['water']);

        $seen = [];
        $pages = [];

        foreach ([1, 2, 3] as $page) {
            $response = StudyServer::tool(LookupWordTool::class, ['query' => '水', 'limit' => 2, 'page' => $page]);
            $response->assertOk()->assertStructuredContentPaths([
                'total' => 6,
                'has_more' => $page < 3,
            ]);

            $words = $this->wordsOf($response);
            $pages[] = $words;
            $seen = array_merge($seen, $words);
        }

        $this->assertSame(['水', '水1'], $pages[0]);
        $this->assertSame(['水2', '水3'], $pages[1]);
        $this->assertSame(['水4', '水5'], $pages[2]);
        $this->assertCount(6, array_unique($seen));
    }

    public function test_a_page_past_the_end_is_empty_and_has_no_more(): void
    {
        $this->createWord(id: 1, word: '水', furigana: 'みず', meanings: ['water']);

        StudyServer::tool(LookupWordTool::class, ['query' => '水', 'page' => 2])
            ->assertOk()
            ->assertStructuredContent(['results' => [], 'total' => 1, 'has_more' => false]);
    }

    public function test_the_level_filter_keeps_only_that_level_and_reports_levels_in_tool_form(): void
    {
        $this->createWord(id: 1, word: '水', furigana: 'みず', jlpt: '5', meanings: ['water']);
        $this->createWord(id: 2, word: '水道', furigana: 'すいどう', jlpt: '3', meanings: ['water supply']);
        $this->createWord(id: 3, word: '水泳', furigana: 'すいえい', jlpt: '-', meanings: ['swimming (water)']);

        $n5 = StudyServer::tool(LookupWordTool::class, ['query' => '水', 'level' => 'N5']);
        $n5->assertOk()->assertStructuredContentPaths([
            'total' => 1,
            'results.0.word' => '水',
            'results.0.level' => 'N5',
        ]);

        $all = StudyServer::tool(LookupWordTool::class, ['query' => '水']);
        $all->assertOk()->assertStructuredContentPaths([
            'results.0.level' => 'N5',
            'results.1.level' => 'N3',
            'results.2.level' => null,
        ]);
    }

    public function test_the_exact_headword_is_not_pinned_when_it_misses_the_level_filter(): void
    {
        $this->createWord(id: 1, word: '水道', furigana: 'すいどう', jlpt: '3', meanings: ['water supply']);
        $this->createWord(id: 2, word: '水', furigana: 'みず', jlpt: '5', meanings: ['water']);

        $response = StudyServer::tool(LookupWordTool::class, ['query' => '水', 'level' => 'N3']);

        $response->assertOk()->assertStructuredContentPaths(['total' => 1, 'results.0.word' => '水道']);
        $this->assertSame(['水道'], $this->wordsOf($response));
    }

    public function test_a_query_with_no_match_is_an_ok_empty_result(): void
    {
        $this->createWord(id: 1, word: '学校', furigana: 'がっこう', meanings: ['school']);

        StudyServer::tool(LookupWordTool::class, ['query' => 'zzzz'])
            ->assertOk()
            ->assertStructuredContent(['results' => [], 'total' => 0, 'has_more' => false]);
    }

    public function test_a_numeric_query_is_searched_like_any_other_text(): void
    {
        $this->createWord(id: 1, word: '三日', furigana: 'みっか', meanings: ['3 days']);

        StudyServer::tool(LookupWordTool::class, ['query' => '3'])
            ->assertOk()
            ->assertStructuredContentPaths(['total' => 1, 'results.0.word' => '三日']);
    }

    /**
     * @return array<string, array{array<string, mixed>, array<int, string>}>
     */
    public static function invalidInput(): array
    {
        return [
            'a missing query' => [[], ['query']],
            'an empty query' => [['query' => ''], ['query']],
            'a blank query' => [['query' => '   '], ['query']],
            'a 256 character query' => [['query' => str_repeat('a', 256)], ['query', '255']],
            'an unknown level' => [['query' => 'water', 'level' => 'N6'], ['level', 'N5, N4, N3, N2, N1']],
            'a lower case level' => [['query' => 'water', 'level' => 'n5'], ['level', 'N5, N4, N3, N2, N1']],
            'a limit of 0' => [['query' => 'water', 'limit' => 0], ['limit', '1 to 20']],
            'a limit of 21' => [['query' => 'water', 'limit' => 21], ['limit', '1 to 20']],
            'a page of 0' => [['query' => 'water', 'page' => 0], ['page', '1 or more']],
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array<int, string> $expectedInMessage
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidInput')]
    public function test_invalid_input_is_an_error_naming_the_field_and_the_service_is_not_called(
        array $arguments,
        array $expectedInMessage,
    ): void {
        $this->mock(WordServiceInterface::class)
            ->shouldNotReceive('find')
            ->shouldNotReceive('findByIdentifier')
            ->shouldNotReceive('count');

        StudyServer::tool(LookupWordTool::class, $arguments)->assertHasErrors($expectedInMessage);
    }

    /**
     * @return array<int, string>
     */
    private function wordsOf(mixed $response): array
    {
        $reflection = new \ReflectionMethod($response, 'structuredContent');
        $structured = $reflection->invoke($response);

        return array_column($structured['results'], 'word');
    }

    /**
     * @param array<int, string> $meanings
     */
    private function createWord(
        int $id,
        ?string $uuid = null,
        string $word = '学校',
        string $furigana = 'がっこう',
        string $jlpt = '-',
        array $meanings = ['school'],
    ): void {
        DB::table('japanese_word_bank_long')->insert([
            'id' => $id,
            'uuid' => $uuid ?? (string) Str::uuid(),
            'entry_sequence' => (string) (1000 + $id),
            'word' => $word,
            'furigana' => $furigana,
            'jlpt' => $jlpt,
            'word_type' => 'noun',
            'word_k_ele' => $word,
            'furigana_r_ele' => $furigana,
            'sense' => json_encode([[['gloss', $meanings]]], JSON_THROW_ON_ERROR),
        ]);
    }
}
