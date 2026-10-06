<?php

declare(strict_types=1);

namespace Tests\Feature\ContentImport;

use App\Application\ContentImport\Interfaces\ArticleTaggerInterface;
use App\Application\ContentImport\Services\ContentImportServiceInterface;
use App\Domain\ContentImport\DTOs\ContentSourceDTO;
use App\Domain\ContentImport\DTOs\ExternalArticle;
use App\Domain\ContentImport\DTOs\ImportRunResult;
use App\Domain\ContentImport\Enums\ImportItemOutcome;
use App\Domain\ContentImport\Enums\ImportRunStatus;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\ContentImportRun;
use App\Infrastructure\Persistence\Models\ContentSource;
use App\Infrastructure\Persistence\Models\User;
use Database\Seeders\ContentImporterUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\Support\ContentImport\FakeContentSourceAdapter;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

class ContentImportServiceTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    private FakeContentSourceAdapter $adapter;

    private ContentSource $source;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
        $this->seed(ContentImporterUserSeeder::class);
        Bus::fake();

        $this->source = ContentSource::query()->create([
            'key' => 'fake-source',
            'name' => 'Fake Source',
            'homepage_url' => 'https://news.example.jp',
            'enabled' => true,
        ]);

        $this->adapter = new FakeContentSourceAdapter();
        $this->app->instance(FakeContentSourceAdapter::class, $this->adapter);
        config([
            'content_import.sources.fake-source' => [
                'adapter' => FakeContentSourceAdapter::class,
                'max_created_per_run' => 3,
                'max_listed' => 6,
                'min_lead_length' => 20,
                'excluded_genres' => ['気象・災害'],
            ],
        ]);

        $this->app->instance(ArticleTaggerInterface::class, new class implements ArticleTaggerInterface
        {
            public function tagsFor(ContentSourceDTO $source, ExternalArticle $article): array
            {
                return ['#テスト'];
            }
        });
    }

    public function test_it_creates_public_imported_articles_authored_by_the_system_user_and_records_the_run(): void
    {
        $this->adapter->articles = [FakeContentSourceAdapter::article('a1'), FakeContentSourceAdapter::article('a2')];

        $result = $this->runImport();

        self::assertSame(ImportRunStatus::Succeeded, $result->status);
        self::assertSame(2, $result->created());

        $systemUser = User::query()->where('email', config('content_import.system_user.email'))->sole();
        $article = Article::query()->where('external_id', 'a1')->sole();
        self::assertSame($systemUser->id, $article->user_id);
        self::assertSame($this->source->id, $article->content_source_id);
        self::assertSame('https://news.example.jp/a1', $article->source_link);
        self::assertNull($article->title_en);
        self::assertSame(1, $article->publicity->value);
        self::assertSame(ArticleStatus::APPROVED, $article->status, 'an import skips the moderation queue');
        $this->assertDatabaseHas('uniquehashtags', ['content' => '#テスト']);

        $run = ContentImportRun::query()->sole();
        self::assertSame(ImportRunStatus::Succeeded, $run->status);
        self::assertSame([2, 2, 0, 0], [$run->listed, $run->created, $run->skipped, $run->failed]);
        self::assertNotNull($run->finished_at);
    }

    public function test_a_second_run_skips_articles_it_already_imported(): void
    {
        $this->adapter->articles = [FakeContentSourceAdapter::article('a1'), FakeContentSourceAdapter::article('a2')];
        $this->runImport();

        $this->adapter->articles = [
            FakeContentSourceAdapter::article('a3'),
            FakeContentSourceAdapter::article('a1'),
            FakeContentSourceAdapter::article('a2'),
        ];
        $this->adapter->yielded = 0;
        $result = $this->runImport();

        self::assertSame(1, $result->created());
        self::assertSame(1, $this->adapter->yielded);
        self::assertSame(3, Article::query()->count());
    }

    public function test_an_article_that_failed_is_tried_again_by_the_next_run(): void
    {
        $broken = new ExternalArticle('b1', 'タイトル', str_repeat('本文です。', 10), 'not a url', null, ['社会']);
        $this->adapter->articles = [FakeContentSourceAdapter::article('a1'), $broken, FakeContentSourceAdapter::article('a2')];
        $first = $this->runImport();

        $this->adapter->articles = [
            FakeContentSourceAdapter::article('a1'),
            FakeContentSourceAdapter::article('b1'),
            FakeContentSourceAdapter::article('a2'),
        ];
        $second = $this->runImport();

        self::assertSame(1, $first->failed());
        self::assertSame(['b1'], array_map(fn ($item) => $item->externalId, $second->items));
        self::assertSame(1, $second->created());
        $this->assertDatabaseHas('articles', ['external_id' => 'b1']);
    }

    public function test_a_run_left_running_by_a_dead_process_is_closed_before_the_next_one(): void
    {
        $abandoned = ContentImportRun::query()->create([
            'content_source_id' => $this->source->id,
            'status' => ImportRunStatus::Running,
            'started_at' => now()->subHours(3),
        ]);
        $this->adapter->articles = [FakeContentSourceAdapter::article('a1')];

        $this->runImport();

        $abandoned->refresh();
        self::assertSame(ImportRunStatus::Failed, $abandoned->status);
        self::assertNotNull($abandoned->finished_at);
        self::assertStringStartsWith('Abandoned', (string) $abandoned->error);
        self::assertSame(1, ContentImportRun::query()->where('status', ImportRunStatus::Succeeded->value)->count());
    }

    public function test_it_stops_at_the_cap_on_created_articles(): void
    {
        $this->adapter->articles = array_map(
            fn (int $i): ExternalArticle => FakeContentSourceAdapter::article("c{$i}"),
            range(1, 5),
        );

        $result = $this->runImport();

        self::assertSame(3, $result->created());
        self::assertSame(3, Article::query()->count());
        self::assertSame(3, $this->adapter->yielded, 'the listing is consumed lazily, not drained');
    }

    public function test_it_skips_excluded_genres_and_short_leads_without_counting_them_against_the_cap(): void
    {
        $this->adapter->articles = [
            FakeContentSourceAdapter::article('w1', ['気象・災害']),
            FakeContentSourceAdapter::article('w2', ['気象・災害', '科学・文化']),
            FakeContentSourceAdapter::article('s1', lead: '短い速報。'),
            FakeContentSourceAdapter::article('n1'),
        ];

        $result = $this->runImport();

        self::assertSame(
            [ImportItemOutcome::FilteredGenre, ImportItemOutcome::Created, ImportItemOutcome::FilteredTooShort, ImportItemOutcome::Created],
            array_map(fn ($item) => $item->outcome, $result->items),
        );
        self::assertSame(2, $result->skipped());
        $this->assertDatabaseMissing('articles', ['external_id' => 'w1']);
        $this->assertDatabaseHas('articles', ['external_id' => 'w2']);
    }

    public function test_the_listing_ceiling_bounds_a_source_full_of_filtered_items(): void
    {
        $this->adapter->articles = array_map(
            fn (int $i): ExternalArticle => FakeContentSourceAdapter::article("w{$i}", ['気象・災害']),
            range(1, 20),
        );

        $result = $this->runImport();

        self::assertSame(6, $result->listed());
        self::assertSame(0, $result->created());
    }

    public function test_one_failing_article_does_not_abort_the_run(): void
    {
        $broken = new ExternalArticle('b1', 'タイトル', str_repeat('本文です。', 10), 'not a url', null, ['社会']);
        $this->adapter->articles = [$broken, FakeContentSourceAdapter::article('ok1')];

        $result = $this->runImport();

        self::assertSame(ImportRunStatus::Succeeded, $result->status);
        self::assertSame(ImportItemOutcome::Failed, $result->items[0]->outcome);
        self::assertSame(1, $result->created());
        self::assertSame(1, ContentImportRun::query()->sole()->failed);
    }

    public function test_an_unreadable_source_fails_the_run_but_keeps_what_was_created(): void
    {
        $this->adapter->articles = [FakeContentSourceAdapter::article('a1')];
        $this->adapter->failWith = 'Unexpected response shape';

        $result = $this->runImport();

        self::assertSame(ImportRunStatus::Failed, $result->status);
        $run = ContentImportRun::query()->sole();
        self::assertSame(ImportRunStatus::Failed, $run->status);
        self::assertSame('Unexpected response shape', $run->error);
        self::assertSame(1, $run->created);
    }

    public function test_an_unexpected_adapter_error_still_closes_the_run_as_failed(): void
    {
        $this->adapter->articles = [FakeContentSourceAdapter::article('a1')];
        $this->adapter->crashWith = new \TypeError('boom');

        $result = $this->runImport();

        self::assertSame(ImportRunStatus::Failed, $result->status);
        $run = ContentImportRun::query()->sole();
        self::assertSame(ImportRunStatus::Failed, $run->status);
        self::assertNotNull($run->finished_at);
        self::assertSame('TypeError: boom', $run->error);
    }

    public function test_a_dry_run_writes_nothing(): void
    {
        $this->adapter->articles = [FakeContentSourceAdapter::article('a1')];

        $result = $this->runImport(dryRun: true);

        self::assertTrue($result->dryRun);
        self::assertSame(ImportItemOutcome::WouldCreate, $result->items[0]->outcome);
        self::assertSame(['#テスト'], $result->items[0]->tags);
        self::assertSame(0, Article::query()->count());
        self::assertSame(0, ContentImportRun::query()->count());
        Bus::assertNothingDispatched();
    }

    public function test_a_disabled_or_unknown_source_is_refused(): void
    {
        $this->source->update(['enabled' => false]);

        $disabled = app(ContentImportServiceInterface::class)->run('fake-source');
        $unknown = app(ContentImportServiceInterface::class)->run('nope');

        self::assertSame('ContentImport.SourceDisabled', $disabled->getError()->code);
        self::assertSame('ContentImport.UnknownSource', $unknown->getError()->code);
        self::assertSame(0, ContentImportRun::query()->count());
    }

    public function test_a_missing_system_user_is_refused(): void
    {
        User::query()->where('email', config('content_import.system_user.email'))->delete();

        $result = app(ContentImportServiceInterface::class)->run('fake-source');

        self::assertSame('ContentImport.SystemAuthorMissing', $result->getError()->code);
    }

    private function runImport(bool $dryRun = false): ImportRunResult
    {
        $result = app(ContentImportServiceInterface::class)->run('fake-source', $dryRun);
        self::assertTrue($result->isSuccess());

        return $result->getData();
    }
}
