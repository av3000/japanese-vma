<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Application\Articles\Services\ArticleServiceInterface;
use App\Application\JapaneseMaterial\Words\Services\WordServiceInterface;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\View;
use App\Mcp\Presenters\ArticleDetailPresenter;
use App\Mcp\Servers\StudyServer;
use App\Mcp\Tools\ReadArticleTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

final class ReadArticleToolTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    private const NOT_FOUND = 'No public article with that id. Use find_articles to get one.';

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
        $tool = app(ReadArticleTool::class)->toArray();

        $this->assertSame('read_article', $tool['name']);
        $this->assertTrue($tool['annotations']['readOnlyHint']);

        $input = $tool['inputSchema'];
        $this->assertSame(['id', 'max_words'], array_keys($input['properties']));
        $this->assertSame(['id'], $input['required']);
        $this->assertSame(1, $input['properties']['max_words']['minimum']);
        $this->assertSame(200, $input['properties']['max_words']['maximum']);
        $this->assertSame(50, $input['properties']['max_words']['default']);

        $output = $tool['outputSchema'];
        $this->assertSame([
            'uuid', 'title_jp', 'title_en', 'content_jp', 'content_en', 'content_jp_truncated', 'content_en_truncated',
            'main_level', 'level_mix', 'source_link', 'words', 'words_total', 'words_truncated',
        ], array_keys($output['properties']));
        $this->assertSame(
            ['word', 'reading', 'level', 'meaning'],
            array_keys($output['properties']['words']['items']['properties']),
        );
    }

    public function test_an_article_is_returned_in_the_documented_shape_without_internal_keys(): void
    {
        $article = $this->createArticle([
            'title_jp' => '日本の食べ物',
            'title_en' => 'Food in Japan',
            'content_jp' => '日本の食べ物はおいしいです。',
            'content_en' => 'Food in Japan is delicious.',
            'source_link' => 'https://example.com/food',
            'n5' => 12,
            'n4' => 3,
            'n3' => 1,
            'uncommon' => 1,
        ]);
        $this->attachWord($article, id: 1, word: '食べ物', furigana: 'たべもの', jlpt: '5', meanings: ['food', 'something to eat']);
        $this->attachWord($article, id: 2, word: '日本', furigana: 'にほん', meanings: ['Japan']);

        StudyServer::tool(ReadArticleTool::class, ['id' => $article->uuid])
            ->assertOk()
            ->assertStructuredContent([
                'uuid' => $article->uuid,
                'title_jp' => '日本の食べ物',
                'title_en' => 'Food in Japan',
                'content_jp' => '日本の食べ物はおいしいです。',
                'content_en' => 'Food in Japan is delicious.',
                'content_jp_truncated' => false,
                'content_en_truncated' => false,
                'main_level' => 'N3',
                'level_mix' => ['N5' => 12, 'N4' => 3, 'N3' => 1, 'N2' => 0, 'N1' => 0, 'uncommon' => 1],
                'source_link' => 'https://example.com/food',
                'words' => [
                    ['word' => '食べ物', 'reading' => 'たべもの', 'level' => 'N5', 'meaning' => 'food; something to eat'],
                    ['word' => '日本', 'reading' => 'にほん', 'level' => null, 'meaning' => 'Japan'],
                ],
                'words_total' => 2,
                'words_truncated' => false,
            ])
            ->assertStructuredContentMissingPaths([
                'id',
                'author',
                'user',
                'status',
                'publicity',
                'engagement',
                'processing_status',
                'hashtags',
                'words.0.id',
                'words.0.uuid',
            ]);
    }

    public function test_an_article_without_an_english_text_or_words_has_nulls_and_an_empty_list(): void
    {
        // The columns are not null: an article saved without English holds empty strings.
        $article = $this->createArticle(['title_en' => '', 'content_en' => '', 'content_jp' => 'ひらがなだけのぶんしょうです。']);

        StudyServer::tool(ReadArticleTool::class, ['id' => $article->uuid])
            ->assertOk()
            ->assertStructuredContentPaths([
                'title_en' => null,
                'content_en' => null,
                'content_en_truncated' => false,
                'main_level' => null,
                'words' => [],
                'words_total' => 0,
                'words_truncated' => false,
            ]);
    }

    public function test_the_description_says_what_is_cut_and_that_word_levels_may_be_missing(): void
    {
        $description = app(ReadArticleTool::class)->toArray()['description'];

        $this->assertStringContainsString('cut at 10,000 characters', $description);
        $this->assertStringContainsString('content_jp_truncated', $description);
        $this->assertStringContainsString('max_words', $description);
        $this->assertStringContainsString('Reading records no view', $description);
        $this->assertStringContainsString('Word levels may be missing', $description);
    }

    // ------------------------------------------------------------------ AC-2

    /**
     * @return array<string, array{mixed}>
     */
    public static function malformedIds(): array
    {
        return [
            'text that is not a uuid' => ['not-a-uuid'],
            'an integer id' => [42],
            'a numeric string' => ['123'],
            'an empty string' => [''],
            'a uuid with a character cut off' => ['1b4e28ba-2fa1-11d2-883f-0016d3cca42'],
            'a list' => [['x']],
        ];
    }

    #[DataProvider('malformedIds')]
    public function test_a_malformed_id_is_an_error_response_and_no_exception_is_logged(mixed $id): void
    {
        Log::spy();
        Exceptions::fake();
        $this->mock(ArticleServiceInterface::class)->shouldNotReceive('findPublicArticle');
        $this->mock(WordServiceInterface::class)->shouldNotReceive('find');

        StudyServer::tool(ReadArticleTool::class, ['id' => $id])
            ->assertHasErrors(['id', 'uuid of an article']);

        Exceptions::assertNothingReported();
        Log::shouldNotHaveReceived('error');
        Log::shouldNotHaveReceived('critical');
    }

    public function test_a_missing_id_is_an_error_response(): void
    {
        StudyServer::tool(ReadArticleTool::class, [])->assertHasErrors(['id', 'uuid of an article']);
    }

    // ------------------------------------------------------------------ AC-3

    public function test_text_over_the_presenter_limit_is_cut_with_its_flag_through_the_tool(): void
    {
        // An article cannot hold more than 2,000 characters per text (ArticleContent), so the limit is lowered to make
        // the cut reachable; BoundedTextTest holds the 10,000 boundary itself.
        $this->app->instance(ArticleDetailPresenter::class, new ArticleDetailPresenter(maxTextCharacters: 100));
        $article = $this->createArticle([
            'content_jp' => str_repeat('あ', 150),
            'content_en' => str_repeat('a', 100),
        ]);

        StudyServer::tool(ReadArticleTool::class, ['id' => $article->uuid])
            ->assertOk()
            ->assertStructuredContentPaths([
                'content_jp' => str_repeat('あ', 100),
                'content_jp_truncated' => true,
                'content_en' => str_repeat('a', 100),
                'content_en_truncated' => false,
            ]);
    }

    public function test_content_under_the_default_limit_is_returned_whole_with_the_flags_false(): void
    {
        $article = $this->createArticle(['content_jp' => str_repeat('あ', 2000), 'content_en' => str_repeat('a', 2000)]);

        StudyServer::tool(ReadArticleTool::class, ['id' => $article->uuid])
            ->assertOk()
            ->assertStructuredContentPaths([
                'content_jp' => str_repeat('あ', 2000),
                'content_jp_truncated' => false,
                'content_en' => str_repeat('a', 2000),
                'content_en_truncated' => false,
            ]);
    }

    // ------------------------------------------------------------------ AC-4

    public function test_max_words_defaults_to_fifty_and_the_total_is_the_real_count(): void
    {
        $article = $this->createArticle();
        $this->attachWords($article, 60);

        DB::enableQueryLog();

        StudyServer::tool(ReadArticleTool::class, ['id' => $article->uuid])
            ->assertOk()
            ->assertStructuredContentCount(50, 'words')
            ->assertStructuredContentPaths([
                'words_total' => 60,
                'words_truncated' => true,
                'words.0.word' => '単語1',
                'words.49.word' => '単語50',
            ]);

        $this->assertSame([50], $this->wordPageSizes());
    }

    public function test_max_words_limits_what_is_read_from_the_database(): void
    {
        $article = $this->createArticle();
        $this->attachWords($article, 5);

        DB::enableQueryLog();

        StudyServer::tool(ReadArticleTool::class, ['id' => $article->uuid, 'max_words' => 2])
            ->assertOk()
            ->assertStructuredContentCount(2, 'words')
            ->assertStructuredContentPaths(['words_total' => 5, 'words_truncated' => true]);

        $this->assertSame([2], $this->wordPageSizes());
    }

    public function test_an_article_with_fewer_words_than_max_words_is_not_truncated(): void
    {
        $article = $this->createArticle();
        $this->attachWords($article, 3);

        StudyServer::tool(ReadArticleTool::class, ['id' => $article->uuid, 'max_words' => 10])
            ->assertOk()
            ->assertStructuredContentCount(3, 'words')
            ->assertStructuredContentPaths(['words_total' => 3, 'words_truncated' => false]);
    }

    public function test_the_largest_max_words_is_read_as_two_pages_of_one_hundred(): void
    {
        $article = $this->createArticle();
        $this->attachWords($article, 230);

        DB::enableQueryLog();

        StudyServer::tool(ReadArticleTool::class, ['id' => $article->uuid, 'max_words' => 200])
            ->assertOk()
            ->assertStructuredContentCount(200, 'words')
            ->assertStructuredContentPaths([
                'words_total' => 230,
                'words_truncated' => true,
                'words.0.word' => '単語1',
                'words.99.word' => '単語100',
                'words.100.word' => '単語101',
                'words.199.word' => '単語200',
            ]);

        // A page holds at most 100 words, and 200 words are never read in one go.
        $this->assertSame([100, 100], $this->wordPageSizes());
    }

    public function test_an_odd_max_words_above_one_hundred_returns_exactly_that_many_words(): void
    {
        $article = $this->createArticle();
        $this->attachWords($article, 130);

        StudyServer::tool(ReadArticleTool::class, ['id' => $article->uuid, 'max_words' => 101])
            ->assertOk()
            ->assertStructuredContentCount(101, 'words')
            ->assertStructuredContentPaths(['words_total' => 130, 'words_truncated' => true, 'words.100.word' => '単語101']);
    }

    public function test_a_second_page_is_not_read_when_the_first_has_every_word(): void
    {
        $article = $this->createArticle();
        $this->attachWords($article, 80);

        DB::enableQueryLog();

        StudyServer::tool(ReadArticleTool::class, ['id' => $article->uuid, 'max_words' => 200])
            ->assertOk()
            ->assertStructuredContentCount(80, 'words')
            ->assertStructuredContentPaths(['words_total' => 80, 'words_truncated' => false]);

        $this->assertSame([100], $this->wordPageSizes());
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidMaxWords(): array
    {
        return [
            'zero' => [['max_words' => 0]],
            'above the maximum' => [['max_words' => 201]],
            'negative' => [['max_words' => -1]],
            'not a number' => [['max_words' => 'many']],
            'a huge number' => [['max_words' => PHP_INT_MAX]],
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     */
    #[DataProvider('invalidMaxWords')]
    public function test_an_invalid_max_words_is_an_error_and_nothing_is_read(array $arguments): void
    {
        $this->mock(ArticleServiceInterface::class)->shouldNotReceive('findPublicArticle');
        $this->mock(WordServiceInterface::class)->shouldNotReceive('find');

        StudyServer::tool(ReadArticleTool::class, ['id' => (string) Str::uuid()] + $arguments)
            ->assertHasErrors(['max_words', '1 to 200']);
    }

    // ------------------------------------------------------------------ AC-5

    public function test_a_private_article_an_unpublished_article_and_a_missing_one_all_get_the_same_error(): void
    {
        $hidden = ['a missing article' => (string) Str::uuid()];

        $private = $this->createArticle(['publicity' => PublicityStatus::PRIVATE]);
        $hidden['a private article'] = $private->uuid;
        $this->attachWords($private, 3);

        foreach (ArticleStatus::cases() as $status) {
            if ($status->isPubliclyVisible()) {
                continue;
            }

            $article = $this->createArticle(['status' => $status]);
            $hidden["a public {$status->name} article"] = $article->uuid;
            $this->attachWords($article, 3, startAt: 100 * ($status->value + 1));
        }

        // Words are only read for an article that passed the visibility check.
        $this->spy(WordServiceInterface::class);

        $messages = [];

        foreach ($hidden as $case => $uuid) {
            StudyServer::tool(ReadArticleTool::class, ['id' => $uuid])->assertHasErrors([self::NOT_FOUND]);
            $messages[$case] = $this->errorText($uuid);
        }

        $this->assertCount(1, array_unique($messages), 'Every hidden case must read the same: '.json_encode($messages, JSON_UNESCAPED_UNICODE));
        $this->assertSame(self::NOT_FOUND, array_values($messages)[0]);
        $this->app->make(WordServiceInterface::class)->shouldNotHaveReceived('find');
    }

    public function test_a_public_processed_article_can_be_read(): void
    {
        $article = $this->createArticle(['status' => ArticleStatus::PROCESSED]);

        StudyServer::tool(ReadArticleTool::class, ['id' => $article->uuid])
            ->assertOk()
            ->assertStructuredContentPaths(['uuid' => $article->uuid]);
    }

    public function test_the_id_is_matched_without_regard_to_letter_case(): void
    {
        $article = $this->createArticle();

        StudyServer::tool(ReadArticleTool::class, ['id' => strtoupper($article->uuid)])
            ->assertOk()
            ->assertStructuredContentPaths(['uuid' => $article->uuid]);
    }

    // ------------------------------------------------------------------ AC-6

    public function test_reading_twice_changes_neither_the_view_count_nor_the_view_records(): void
    {
        $article = $this->createArticle();
        $this->attachWords($article, 3);

        // A view from the website already exists, so an update of its timestamp would show too.
        DB::table('views')->insert([
            'user_id' => null,
            'user_ip' => '203.0.113.7',
            'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'real_object_id' => $article->id,
            'created_at' => '2026-01-01 10:00:00',
            'updated_at' => '2026-01-01 10:00:00',
        ]);

        $before = View::query()->orderBy('id')->get()->toArray();
        $this->assertCount(1, $before);

        StudyServer::tool(ReadArticleTool::class, ['id' => $article->uuid])->assertOk();
        StudyServer::tool(ReadArticleTool::class, ['id' => $article->uuid])->assertOk();

        $this->assertSame($before, View::query()->orderBy('id')->get()->toArray());
        $this->assertSame(1, View::query()->where('real_object_id', $article->id)->count());
    }

    public function test_reading_records_no_view_when_the_article_has_none_yet(): void
    {
        $article = $this->createArticle();

        StudyServer::tool(ReadArticleTool::class, ['id' => $article->uuid])->assertOk();
        StudyServer::tool(ReadArticleTool::class, ['id' => $article->uuid])->assertOk();

        $this->assertSame(0, View::query()->count());
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

    /**
     * @param array<int, string> $meanings
     */
    private function attachWord(
        Article $article,
        int $id,
        string $word = '学校',
        string $furigana = 'がっこう',
        string $jlpt = '-',
        array $meanings = ['school'],
    ): void {
        DB::table('japanese_word_bank_long')->insert([
            'id' => $id,
            'uuid' => (string) Str::uuid(),
            'entry_sequence' => (string) (1000 + $id),
            'word' => $word,
            'furigana' => $furigana,
            'jlpt' => $jlpt,
            'word_type' => 'noun',
            'word_k_ele' => $word,
            'furigana_r_ele' => $furigana,
            'sense' => json_encode([[['gloss', $meanings]]], JSON_THROW_ON_ERROR),
        ]);

        DB::table('article_word')->insert(['article_id' => $article->id, 'word_id' => $id]);
    }

    private function attachWords(Article $article, int $count, int $startAt = 0): void
    {
        // Ids are made apart by $startAt so several articles can have words in one test.
        foreach (range(1, $count) as $number) {
            $this->attachWord($article, id: $startAt * 1000 + $number, word: "単語{$number}", furigana: 'たんご', meanings: ["word {$number}"]);
        }
    }

    /**
     * The page size of each word list query run since the query log was enabled, in order.
     *
     * @return array<int, int>
     */
    private function wordPageSizes(): array
    {
        $sizes = [];

        foreach (DB::getQueryLog() as $query) {
            // The page size is inlined in the SQL: `... limit 50 offset 0`.
            if (str_contains($query['query'], 'from "japanese_word_bank_long"')
                && preg_match('/limit (\d+) offset \d+/', $query['query'], $match) === 1) {
                $sizes[] = (int) $match[1];
            }
        }

        return $sizes;
    }

    /**
     * The exact text of the error the tool answers with, read from the tool itself (the test response only checks
     * that a message is contained).
     */
    private function errorText(string $uuid): string
    {
        $response = app()->call(app(ReadArticleTool::class)->handle(...), ['request' => new Request(['id' => $uuid])]);

        $this->assertTrue($response->isError());

        return $response->content()->toArray()['text'];
    }
}
