<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Application\Articles\Services\ArticleListServiceInterface;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\User;
use App\Mcp\Servers\StudyServer;
use App\Mcp\Tools\FindArticlesTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

final class FindArticlesToolTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
        $this->author = User::factory()->create();
    }

    // ------------------------------------------------------------------ AC-1

    public function test_the_tool_is_read_only_and_describes_its_input_and_output(): void
    {
        $tool = app(FindArticlesTool::class)->toArray();

        $this->assertSame('find_articles', $tool['name']);
        $this->assertTrue($tool['annotations']['readOnlyHint']);

        $input = $tool['inputSchema'];
        $this->assertSame(['query', 'levels', 'limit', 'page'], array_keys($input['properties']));
        $this->assertArrayNotHasKey('required', $input);
        $this->assertSame('array', $input['properties']['levels']['type']);
        $this->assertSame(['N5', 'N4', 'N3', 'N2', 'N1'], $input['properties']['levels']['items']['enum']);
        $this->assertSame(1, $input['properties']['limit']['minimum']);
        $this->assertSame(20, $input['properties']['limit']['maximum']);
        $this->assertSame(5, $input['properties']['limit']['default']);
        $this->assertSame(1, $input['properties']['page']['minimum']);
        $this->assertSame(1, $input['properties']['page']['default']);

        $output = $tool['outputSchema'];
        $this->assertSame(['results', 'total', 'has_more'], array_keys($output['properties']));
        $this->assertSame(
            ['uuid', 'title_jp', 'title_en', 'preview_jp', 'main_level', 'level_mix', 'tags', 'created_at'],
            array_keys($output['properties']['results']['items']['properties']),
        );
        $this->assertSame(
            ['N5', 'N4', 'N3', 'N2', 'N1', 'uncommon'],
            array_keys($output['properties']['results']['items']['properties']['level_mix']['properties']),
        );
    }

    public function test_an_article_is_returned_in_the_documented_shape_without_internal_keys(): void
    {
        $article = $this->createArticle([
            'title_jp' => '日本の食べ物',
            'title_en' => 'Food in Japan',
            'content_jp' => str_repeat('あ', 150),
            'n5' => 12,
            'n4' => 3,
            'n3' => 1,
            'uncommon' => 1,
        ]);
        $this->tag($article, '#food');
        $this->tag($article, '#culture');

        $response = StudyServer::tool(FindArticlesTool::class, []);

        $response->assertOk()
            ->assertStructuredContent([
                'results' => [[
                    'uuid' => $article->uuid,
                    'title_jp' => '日本の食べ物',
                    'title_en' => 'Food in Japan',
                    'preview_jp' => str_repeat('あ', 100).'...',
                    'main_level' => 'N3',
                    'level_mix' => ['N5' => 12, 'N4' => 3, 'N3' => 1, 'N2' => 0, 'N1' => 0, 'uncommon' => 1],
                    'tags' => ['#food', '#culture'],
                    'created_at' => $article->fresh()->created_at->format('c'),
                ]],
                'total' => 1,
                'has_more' => false,
            ])
            ->assertStructuredContentMissingPaths([
                'results.0.id',
                'results.0.author',
                'results.0.status',
                'results.0.publicity',
                'results.0.engagement',
                'results.0.processing_status',
            ]);
    }

    public function test_an_article_without_an_english_title_or_kanji_has_nulls_and_empty_lists(): void
    {
        // The column is not null: an article saved without an English title holds an empty string.
        $this->createArticle(['title_jp' => 'ひらがなだけ', 'title_en' => '']);

        StudyServer::tool(FindArticlesTool::class, [])
            ->assertOk()
            ->assertStructuredContentPaths([
                'results.0.title_en' => null,
                'results.0.main_level' => null,
                'results.0.level_mix.N5' => 0,
                'results.0.tags' => [],
            ]);
    }

    public function test_the_query_matches_either_title_and_not_the_text(): void
    {
        $this->createArticle(['title_jp' => '水の話', 'title_en' => 'About something', 'content_jp' => 'ここには魚の話だけがあります。']);
        $this->createArticle(['title_jp' => '山の話', 'title_en' => 'Mountain trip', 'content_jp' => 'ここには水の話だけがあります。']);

        StudyServer::tool(FindArticlesTool::class, ['query' => '水'])
            ->assertOk()
            ->assertStructuredContentPaths(['total' => 1, 'results.0.title_jp' => '水の話']);

        StudyServer::tool(FindArticlesTool::class, ['query' => 'mountain'])
            ->assertOk()
            ->assertStructuredContentPaths(['total' => 1, 'results.0.title_jp' => '山の話']);
    }

    public function test_a_blank_query_is_no_query(): void
    {
        $this->createArticle(['title_jp' => '最初の記事']);
        $this->createArticle(['title_jp' => '次の記事']);

        StudyServer::tool(FindArticlesTool::class, ['query' => '   '])
            ->assertOk()
            ->assertStructuredContentPaths(['total' => 2]);
    }

    // ------------------------------------------------------------------ AC-2 and AC-3, end to end

    public function test_the_level_filter_keeps_articles_with_any_kanji_of_the_level_and_main_level_says_how_hard_each_is(): void
    {
        $this->createArticle(['title_jp' => '易しい記事', 'n5' => 40, 'n3' => 1, 'created_at' => Carbon::parse('2026-01-02')]);
        $this->createArticle(['title_jp' => '難しい記事', 'n5' => 1, 'n3' => 3, 'n2' => 10, 'n1' => 30, 'created_at' => Carbon::parse('2026-01-01')]);
        $this->createArticle(['title_jp' => 'N5だけの記事', 'n5' => 20]);

        // Both articles have an N3 kanji, so both match, yet one is an N5 read and the other an N1 read.
        StudyServer::tool(FindArticlesTool::class, ['levels' => ['N3']])
            ->assertOk()
            ->assertStructuredContentCount(2, 'results')
            ->assertStructuredContentPaths([
                'total' => 2,
                'results.0.title_jp' => '易しい記事',
                'results.0.main_level' => 'N5',
                'results.1.title_jp' => '難しい記事',
                'results.1.main_level' => 'N1',
            ]);
    }

    public function test_several_levels_match_an_article_that_has_any_of_them(): void
    {
        $this->createArticle(['title_jp' => 'N2の記事', 'n2' => 5]);
        $this->createArticle(['title_jp' => 'N4の記事', 'n4' => 5]);
        $this->createArticle(['title_jp' => 'N5の記事', 'n5' => 5]);

        StudyServer::tool(FindArticlesTool::class, ['levels' => ['N2', 'N4', 'N2']])
            ->assertOk()
            ->assertStructuredContentPaths(['total' => 2]);
    }

    public function test_the_description_tells_the_assistant_to_choose_by_main_level_and_what_null_means(): void
    {
        $description = app(FindArticlesTool::class)->toArray()['description'];

        $this->assertStringContainsString('at least one kanji of any chosen level', $description);
        $this->assertStringContainsString('choose by main_level', $description);
        $this->assertStringContainsString('main_level is null when the article has no kanji', $description);
        $this->assertStringContainsString('too many of its kanji are uncommon', $description);
    }

    // ------------------------------------------------------------------ AC-4

    public function test_a_guest_never_gets_private_or_unmoderated_articles_in_the_results_or_the_total(): void
    {
        foreach (ArticleStatus::cases() as $status) {
            $created = Carbon::parse('2026-01-01')->addDays($status->value);

            $this->createArticle(['title_jp' => "公開 {$status->name}", 'status' => $status, 'created_at' => $created]);
            $this->createArticle(['title_jp' => "非公開 {$status->name}", 'status' => $status, 'publicity' => PublicityStatus::PRIVATE, 'created_at' => $created]);
        }

        // Newest first: APPROVED (4) before PROCESSED (1). The other three statuses sit between them
        // in time, so a leak would show up as a row there.
        StudyServer::tool(FindArticlesTool::class, ['limit' => 20])
            ->assertOk()
            ->assertStructuredContentCount(2, 'results')
            ->assertStructuredContentPaths([
                'total' => 2,
                'has_more' => false,
                'results.0.title_jp' => '公開 APPROVED',
                'results.1.title_jp' => '公開 PROCESSED',
            ]);
    }

    public function test_the_level_and_keyword_filters_cannot_reach_hidden_articles(): void
    {
        $this->createArticle(['title_jp' => '隠れた記事', 'status' => ArticleStatus::REJECTED, 'n5' => 10]);
        $this->createArticle(['title_jp' => '隠れた私的記事', 'publicity' => PublicityStatus::PRIVATE, 'n5' => 10]);

        StudyServer::tool(FindArticlesTool::class, ['query' => '隠れた', 'levels' => ['N5']])
            ->assertOk()
            ->assertStructuredContent(['results' => [], 'total' => 0, 'has_more' => false]);
    }

    // ------------------------------------------------------------------ AC-5

    public function test_page_two_returns_the_next_items_and_has_more_is_false_on_the_last_page(): void
    {
        foreach (range(1, 7) as $number) {
            $this->createArticle(['title_jp' => "記事{$number}", 'created_at' => Carbon::parse('2026-01-01')->addDays($number)]);
        }

        $expected = [
            1 => [['記事7', '記事6', '記事5'], true],
            2 => [['記事4', '記事3', '記事2'], true],
            3 => [['記事1'], false],
        ];

        foreach ($expected as $page => [$titles, $hasMore]) {
            $paths = ['total' => 7, 'has_more' => $hasMore];

            foreach ($titles as $position => $title) {
                $paths["results.{$position}.title_jp"] = $title;
            }

            StudyServer::tool(FindArticlesTool::class, ['limit' => 3, 'page' => $page])
                ->assertOk()
                ->assertStructuredContentCount(count($titles), 'results')
                ->assertStructuredContentPaths($paths);
        }
    }

    public function test_the_default_page_size_is_five(): void
    {
        foreach (range(1, 6) as $number) {
            $this->createArticle(['title_jp' => "記事{$number}"]);
        }

        StudyServer::tool(FindArticlesTool::class, [])
            ->assertOk()
            ->assertStructuredContentCount(5, 'results')
            ->assertStructuredContentPaths(['total' => 6, 'has_more' => true]);
    }

    // ------------------------------------------------------------------ AC-6

    public function test_a_search_with_no_matches_is_an_ok_empty_list(): void
    {
        $this->createArticle(['title_jp' => '学校の話']);

        StudyServer::tool(FindArticlesTool::class, ['query' => 'zzzz'])
            ->assertOk()
            ->assertStructuredContent(['results' => [], 'total' => 0, 'has_more' => false]);
    }

    public function test_a_page_past_the_end_is_an_ok_empty_list(): void
    {
        $this->createArticle(['title_jp' => '学校の話']);

        StudyServer::tool(FindArticlesTool::class, ['page' => 4])
            ->assertOk()
            ->assertStructuredContent(['results' => [], 'total' => 1, 'has_more' => false]);
    }

    // ------------------------------------------------------------------ input

    /**
     * @return array<string, array{array<string, mixed>, array<int, string>}>
     */
    public static function invalidInput(): array
    {
        return [
            'a one letter query' => [['query' => 'a'], ['query', '2 to 255 bytes']],
            'a query over 255 bytes' => [['query' => str_repeat('あ', 86)], ['query', '255']],
            'a query that is not text' => [['query' => ['x']], ['query']],
            'an unknown level' => [['levels' => ['N6']], ['levels.0', 'N5, N4, N3, N2, N1']],
            'a lower case level' => [['levels' => ['n5']], ['levels.0', 'N5, N4, N3, N2, N1']],
            'levels that are not a list' => [['levels' => 'N5'], ['levels', 'list of levels']],
            'more than five levels' => [['levels' => ['N5', 'N4', 'N3', 'N2', 'N1', 'N1']], ['levels', 'at most 5']],
            'a limit of 0' => [['limit' => 0], ['limit', '1 to 20']],
            'a limit of 21' => [['limit' => 21], ['limit', '1 to 20']],
            'a page of 0' => [['page' => 0], ['page', '1 to 500']],
            'a page past the website limit' => [['page' => 501], ['page', '1 to 500']],
            'a huge page' => [['page' => PHP_INT_MAX], ['page', '1 to 500']],
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array<int, string> $expectedInMessage
     */
    #[DataProvider('invalidInput')]
    public function test_invalid_input_is_an_error_naming_the_field_and_the_service_is_not_called(
        array $arguments,
        array $expectedInMessage,
    ): void {
        $this->mock(ArticleListServiceInterface::class)->shouldNotReceive('list');

        StudyServer::tool(FindArticlesTool::class, $arguments)->assertHasErrors($expectedInMessage);
    }

    public function test_one_japanese_character_is_a_valid_query(): void
    {
        $this->createArticle(['title_jp' => '水の話']);

        StudyServer::tool(FindArticlesTool::class, ['query' => '水'])
            ->assertOk()
            ->assertStructuredContentPaths(['total' => 1]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createArticle(array $attributes = []): Article
    {
        return Article::factory()->byUser($this->author)->create(array_merge([
            'status' => ArticleStatus::APPROVED,
            'publicity' => PublicityStatus::PUBLIC,
        ], $attributes));
    }

    private function tag(Article $article, string $content): void
    {
        $hashtagId = DB::table('uniquehashtags')->insertGetId([
            'content' => $content,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hashtag_entity')->insert([
            'entity_id' => $article->id,
            'entity_type_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'hashtag_id' => $hashtagId,
            'user_id' => $this->author->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
