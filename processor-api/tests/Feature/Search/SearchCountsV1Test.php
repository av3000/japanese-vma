<?php

namespace Tests\Feature\Search;

use App\Application\Search\Interfaces\Caches\SearchCountCacheInterface;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

class SearchCountsV1Test extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    private const LIST_PATHS = [
        'articles' => '/api/v1/articles',
        'kanji' => '/api/v1/kanjis',
        'words' => '/api/v1/words',
        'sentences' => '/api/v1/sentences',
        'radicals' => '/api/v1/radicals',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    public function test_guest_gets_every_scope_counted_like_its_list(): void
    {
        $author = User::factory()->create();
        $this->createArticle($author, 'Water in the city', PublicityStatus::PUBLIC);
        $this->createArticle($author, 'Private water notes', PublicityStatus::PRIVATE);
        $this->createArticle($author, 'Fire and ice', PublicityStatus::PUBLIC);
        $this->createKanji('水', 'water');
        $this->createKanji('氷', 'ice, water frozen');
        $this->createKanji('火', 'fire');
        // The readable-character scope hides placeholder rows from the list, so the count must too.
        $this->createKanji('?', 'water');
        $this->createWord('水', 'みず', 'water');
        $this->createWord('火', 'ひ', 'fire');
        $this->createSentence('水を飲みます。', '3001');
        $this->createRadical('水', 'water', 'みず');

        $response = $this->getJson('/api/v1/search/counts?q=water');

        $response->assertOk()
            ->assertJsonMissingPath('data')
            ->assertExactJson([
                'q' => 'water',
                'articles' => 1,
                'kanji' => 2,
                'words' => 1,
                'sentences' => 0,
                'radicals' => 1,
            ]);
        $this->assertCountsMatchLists($response, 'water');
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function viewers(): array
    {
        return [
            'a guest sees public articles' => ['guest', 1],
            'the owner also sees their own private one' => ['owner', 2],
            'an admin sees every article' => ['admin', 3],
        ];
    }

    /**
     * One viewer per test: controllers memoise the viewer for the life of a test's application.
     */
    #[DataProvider('viewers')]
    public function test_article_count_follows_the_viewer_like_the_list(string $viewer, int $expected): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->createArticle($owner, 'River guide', PublicityStatus::PUBLIC);
        $this->createArticle($owner, 'River diary', PublicityStatus::PRIVATE);
        $this->createArticle($other, 'River secrets', PublicityStatus::PRIVATE);

        if ($viewer === 'owner') {
            $this->signIn($owner);
        } elseif ($viewer === 'admin') {
            $admin = User::factory()->create();
            $admin->assignRole(UserRole::ADMIN->value);
            $this->signIn($admin);
        }

        $counts = $this->getJson('/api/v1/search/counts?q=River');

        $counts->assertOk()->assertJsonPath('articles', $expected);
        $this->assertCountsMatchLists($counts, 'River');
    }

    public function test_a_single_character_has_no_article_count_because_the_list_rejects_it(): void
    {
        $this->createKanji('水', 'water');

        $this->getJson('/api/v1/articles?q='.urlencode('水'))->assertStatus(422);

        $this->getJson('/api/v1/search/counts?q='.urlencode('水'))
            ->assertOk()
            ->assertJsonPath('articles', null)
            ->assertJsonPath('kanji', 1);
    }

    public function test_article_count_is_null_when_the_term_is_too_long_in_bytes_for_the_list(): void
    {
        // 90 kanji are 90 characters but 270 bytes, and SearchTerm measures bytes.
        $keyword = str_repeat('水', 90);

        $this->getJson('/api/v1/articles?q='.urlencode($keyword))->assertStatus(422);

        $this->getJson('/api/v1/search/counts?q='.urlencode($keyword))
            ->assertOk()
            ->assertJsonPath('articles', null)
            ->assertJsonPath('kanji', 0)
            ->assertJsonPath('words', 0)
            ->assertJsonPath('sentences', 0)
            ->assertJsonPath('radicals', 0);
    }

    public function test_sentences_and_radicals_have_no_count_past_their_list_limit(): void
    {
        $keyword = str_repeat('a', 101);

        $this->getJson('/api/v1/sentences?keyword='.$keyword)->assertStatus(422);
        $this->getJson('/api/v1/radicals?keyword='.$keyword)->assertStatus(422);

        $this->getJson('/api/v1/search/counts?q='.$keyword)
            ->assertOk()
            ->assertExactJson([
                'q' => $keyword,
                'articles' => 0,
                'kanji' => 0,
                'words' => 0,
                'sentences' => null,
                'radicals' => null,
            ]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidKeywords(): array
    {
        return [
            'missing' => [''],
            'only whitespace' => ['?q=%20%20'],
            'longer than 255 characters' => ['?q='.str_repeat('a', 256)],
        ];
    }

    #[DataProvider('invalidKeywords')]
    public function test_invalid_keyword_is_rejected(string $query): void
    {
        $this->getJson('/api/v1/search/counts'.$query)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    public function test_the_keyword_is_trimmed_and_echoed(): void
    {
        $this->getJson('/api/v1/search/counts?q=%20water%20')
            ->assertOk()
            ->assertJsonPath('q', 'water');
    }

    public function test_repeated_dictionary_counts_are_served_from_cache(): void
    {
        $this->createWord('水', 'みず', 'water');
        $this->getJson('/api/v1/search/counts?q=water')->assertJsonPath('words', 1);

        $this->createWord('お水', 'おみず', 'water (polite)');

        $this->getJson('/api/v1/search/counts?q=water')->assertJsonPath('words', 1);
        $this->getJson('/api/v1/words?keyword=water')->assertJsonPath('pagination.total', 2);
    }

    public function test_cache_entries_keep_the_keyword_case_because_the_lists_match_case_sensitively(): void
    {
        $this->createWord('水', 'みず', 'water');
        $this->getJson('/api/v1/search/counts?q=water')->assertJsonPath('words', 1);

        $this->getJson('/api/v1/search/counts?q=Water')->assertJsonPath('words', 0);
    }

    public function test_guest_article_count_is_cached(): void
    {
        $author = User::factory()->create();
        $this->createArticle($author, 'River guide', PublicityStatus::PUBLIC);
        $this->getJson('/api/v1/search/counts?q=River')->assertJsonPath('articles', 1);

        $this->createArticle($author, 'River walk', PublicityStatus::PUBLIC);

        $this->getJson('/api/v1/search/counts?q=River')->assertJsonPath('articles', 1);
    }

    public function test_signed_in_article_count_is_live_and_never_reads_the_guest_entry(): void
    {
        $owner = User::factory()->create();
        $this->createArticle($owner, 'River guide', PublicityStatus::PUBLIC);
        // A guest entry for the same keyword that a signed-in viewer must not be served.
        $this->app->make(SearchCountCacheInterface::class)->put('articles', 'River', 99, 60);
        $this->signIn($owner);

        $this->getJson('/api/v1/search/counts?q=River')->assertJsonPath('articles', 1);
        $this->createArticle($owner, 'River diary', PublicityStatus::PRIVATE);

        $this->getJson('/api/v1/search/counts?q=River')->assertJsonPath('articles', 2);
    }

    public function test_the_route_has_its_own_rate_limit_outside_the_shared_api_budget(): void
    {
        for ($attempt = 1; $attempt <= 60; $attempt++) {
            $this->getJson('/api/v1/search/counts?q=water')->assertOk();
        }

        $this->getJson('/api/v1/search/counts?q=water')->assertStatus(429);

        // Sixty counts requests would have used up the shared 60-a-minute budget if they spent it.
        $this->getJson('/api/v1/kanjis')->assertOk();
    }

    /**
     * The app resolves an optional viewer from the bearer token, so the header is needed too.
     */
    private function signIn(User $user): void
    {
        Passport::actingAs($user, ['*'], 'api');
        $this->withHeader('Authorization', 'Bearer test-token');
    }

    /**
     * Each count equals the total its list reports for the same keyword and the same viewer.
     */
    private function assertCountsMatchLists(TestResponse $counts, string $keyword): void
    {
        foreach (self::LIST_PATHS as $scope => $path) {
            $parameter = $scope === 'articles' ? 'q' : 'keyword';
            $list = $this->getJson($path.'?'.$parameter.'='.urlencode($keyword));
            $list->assertOk();

            $this->assertSame(
                $list->json('pagination.total'),
                $counts->json($scope),
                "The {$scope} count differs from {$path}'s total for '{$keyword}'.",
            );
        }
    }

    private function createArticle(User $author, string $title, PublicityStatus $publicity): void
    {
        PersistenceArticle::factory()
            ->byUser($author)
            ->create([
                'title_jp' => $title,
                'title_en' => $title,
                'publicity' => $publicity,
                'status' => ArticleStatus::APPROVED,
            ]);
    }

    private function createKanji(string $kanji, string $meaning): void
    {
        DB::table('japanese_kanji_bank_long')->insert([
            'uuid' => (string) Str::uuid(),
            'kanji' => $kanji,
            'onyomi' => '-',
            'kunyomi' => '-',
            'meaning' => $meaning,
            'nanori' => '-',
            'grade' => '1',
            'stroke_count' => '4',
            'jlpt' => '5',
            'frequency' => '1',
            'radicals' => '-',
            'radical_parts' => $kanji,
        ]);
    }

    private function createWord(string $word, string $furigana, string $gloss): void
    {
        static $sequence = 1000;
        $sequence++;

        DB::table('japanese_word_bank_long')->insert([
            'uuid' => (string) Str::uuid(),
            'entry_sequence' => (string) $sequence,
            'word' => $word,
            'furigana' => $furigana,
            'jlpt' => '-',
            'word_type' => 'noun',
            'word_k_ele' => $word,
            'furigana_r_ele' => $furigana,
            'sense' => json_encode([[['gloss', [$gloss]]]], JSON_THROW_ON_ERROR),
        ]);
    }

    private function createSentence(string $content, string $tatoebaEntry): void
    {
        DB::table('japanese_tatoeba_sentences')->insert([
            'uuid' => (string) Str::uuid(),
            'user_id' => null,
            'tatoeba_entry' => $tatoebaEntry,
            'content' => $content,
        ]);
    }

    private function createRadical(string $radical, string $meaning, string $hiragana): void
    {
        DB::table('japanese_radicals_bank_long')->insert([
            'uuid' => (string) Str::uuid(),
            'radical' => $radical,
            'strokes' => 4,
            'meaning' => $meaning,
            'hiragana' => $hiragana,
        ]);
    }
}
