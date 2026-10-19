<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Application\AdminDashboard\Interfaces\Readers\AdminDashboardReaderInterface;
use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingStatus;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\ContentStatsWidget;
use App\Filament\Widgets\GrowthChartWidget;
use App\Filament\Widgets\NeedsAttentionWidget;
use App\Filament\Widgets\ReviewQueueWidget;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\Comment;
use App\Infrastructure\Persistence\Models\Post;
use App\Infrastructure\Persistence\Models\ProcessingState;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Readers\CachedAdminDashboardReader;
use App\Infrastructure\Persistence\Readers\DatabaseAdminDashboardReader;
use Carbon\CarbonImmutable;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RuntimeException;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\TestCase;

class DashboardWidgetsTest extends TestCase
{
    use InteractsWithAdminPanel;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-10-14 12:00:00');
        $this->travelTo(CarbonImmutable::now());
        $this->seedAdminPanel();
        $this->admin = $this->createAdmin();
        $this->actingAsPanelUser($this->admin);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_shows_product_widgets_instead_of_filament_defaults(): void
    {
        $this->get('/admin')
            ->assertOk()
            ->assertSeeLivewire(ContentStatsWidget::class)
            ->assertSeeLivewire(NeedsAttentionWidget::class)
            ->assertSeeLivewire(GrowthChartWidget::class)
            ->assertSeeLivewire(ReviewQueueWidget::class)
            ->assertDontSeeLivewire(FilamentInfoWidget::class);
    }

    public function test_zero_state_renders_zeros(): void
    {
        $stats = $this->liveReader()->contentStats();
        $attention = $this->liveReader()->attentionCounts();

        self::assertSame(1, $stats->users);
        self::assertSame(0, $stats->articles());
        self::assertSame(0, $stats->publicCatalogues);
        self::assertSame(0, $attention->articlesAwaitingReview);
        self::assertSame(0, $attention->failedJobs);

        Livewire::test(NeedsAttentionWidget::class)->assertOk()->assertSee('Nothing to do');
        Livewire::test(ReviewQueueWidget::class)->assertOk()->assertSee('No articles are waiting for review');
    }

    public function test_content_stats_match_seeded_rows(): void
    {
        $author = $this->createPanelUser('Author');
        $this->travelTo(CarbonImmutable::parse('2026-09-14 12:00:00'));
        $this->createPanelUser('Old member');
        $this->travelTo(CarbonImmutable::parse('2026-10-14 12:00:00'));

        Article::factory()->count(2)->create(['user_id' => $author->id, 'status' => ArticleStatus::APPROVED]);
        Article::factory()->create(['user_id' => $author->id, 'status' => ArticleStatus::PENDING]);
        Article::factory()->create(['user_id' => $author->id, 'status' => ArticleStatus::REVIEWING]);
        Article::factory()->create(['user_id' => $author->id, 'status' => ArticleStatus::REJECTED]);
        Catalogue::factory()->count(2)->create(['user_id' => $author->id]);
        Catalogue::factory()->asPrivate()->create(['user_id' => $author->id]);
        $post = Post::factory()->create(['user_id' => $author->id]);
        $this->comment($post, $author, CarbonImmutable::now()->subDays(2));
        $this->comment($post, $author, CarbonImmutable::now()->subDays(10));

        $stats = $this->liveReader()->contentStats();

        self::assertSame(3, $stats->users);
        self::assertSame(2, $stats->newUsersLastWeek);
        self::assertSame(5, $stats->articles());
        self::assertSame(2, $stats->articlesWithStatus(ArticleStatus::APPROVED));
        self::assertSame(0, $stats->articlesWithStatus(ArticleStatus::PROCESSED));
        self::assertSame(2, $stats->publicCatalogues);
        self::assertSame(1, $stats->posts);
        self::assertSame(1, $stats->newCommentsLastWeek);

        Livewire::test(ContentStatsWidget::class)
            ->assertOk()
            ->assertSee('2 approved, 2 awaiting review, 1 rejected');
    }

    public function test_attention_counts_cover_review_failures_stuck_rows_and_failed_jobs(): void
    {
        $author = $this->createPanelUser('Author');
        Article::factory()->create(['user_id' => $author->id, 'status' => ArticleStatus::PENDING]);
        Article::factory()->create(['user_id' => $author->id, 'status' => ArticleStatus::REVIEWING]);
        Article::factory()->create(['user_id' => $author->id, 'status' => ArticleStatus::APPROVED]);

        $now = CarbonImmutable::now();
        $this->processingRow(ProcessingStatus::FAILED, finishedAt: $now->subHours(2), updatedAt: $now->subHours(2));
        $this->processingRow(ProcessingStatus::FAILED, finishedAt: $now->subDays(2), updatedAt: $now->subDays(2));
        $this->processingRow(ProcessingStatus::PROCESSING, updatedAt: $now->subMinutes(10));
        $this->processingRow(ProcessingStatus::PENDING, updatedAt: $now->subMinute());
        DB::table('failed_jobs')->insert([
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'boom',
            'failed_at' => $now,
        ]);

        $counts = $this->liveReader()->attentionCounts();

        self::assertSame(2, $counts->articlesAwaitingReview);
        self::assertSame(1, $counts->processingFailedLastDay);
        self::assertSame(1, $counts->processingStuck);
        self::assertSame(1, $counts->failedJobs);
    }

    public function test_growth_has_twelve_weekly_buckets_with_empty_weeks_as_zero(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-02 09:00:00'));
        $this->createPanelUser('September member');
        $this->travelTo(CarbonImmutable::parse('2026-10-14 12:00:00'));

        $weeks = $this->liveReader()->weeklyGrowth()->weeks;

        self::assertCount(12, $weeks);
        self::assertSame('2026-07-27', $weeks[0]['week_start']);
        self::assertSame('2026-10-12', $weeks[11]['week_start']);

        $byWeek = array_column($weeks, null, 'week_start');
        self::assertSame(1, $byWeek['2026-08-31']['registrations']);
        self::assertSame(1, $byWeek['2026-10-12']['registrations']);
        self::assertSame(0, $byWeek['2026-09-07']['registrations']);
        self::assertSame(0, array_sum(array_column($weeks, 'articles')));

        Livewire::test(GrowthChartWidget::class)->assertOk();
    }

    public function test_each_widget_reads_with_a_bounded_number_of_queries(): void
    {
        $reader = $this->liveReader();

        foreach (['contentStats' => 2, 'attentionCounts' => 1, 'weeklyGrowth' => 2, 'reviewQueue' => 1] as $method => $expected) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $reader->{$method}();
            self::assertCount($expected, DB::getQueryLog(), $method);
            DB::disableQueryLog();
        }
    }

    public function test_review_queue_lists_oldest_awaiting_articles_first(): void
    {
        $author = $this->createPanelUser('Queue author');
        $this->travelTo(CarbonImmutable::now()->subDays(3));
        $oldest = Article::factory()->create(['user_id' => $author->id, 'status' => ArticleStatus::PENDING, 'title_jp' => '古い記事']);
        $this->travelTo(CarbonImmutable::parse('2026-10-14 12:00:00'));
        Article::factory()->create(['user_id' => $author->id, 'status' => ArticleStatus::REVIEWING, 'title_jp' => '新しい記事']);
        Article::factory()->create(['user_id' => $author->id, 'status' => ArticleStatus::APPROVED, 'title_jp' => '承認済み']);

        $queue = $this->liveReader()->reviewQueue();

        self::assertSame([(string) $oldest->uuid], [$queue[0]->uuid]);
        self::assertCount(2, $queue);

        Livewire::test(ReviewQueueWidget::class)
            ->assertOk()
            ->assertSee('古い記事')
            ->assertSee('Queue author')
            ->assertDontSee('承認済み');
    }

    public function test_review_queue_and_attention_need_their_permissions(): void
    {
        $support = $this->createUserWithPermissions(['users.view']);
        $this->actingAsPanelUser($support);

        self::assertFalse(ReviewQueueWidget::canView());
        self::assertFalse(NeedsAttentionWidget::canView());

        $moderator = $this->createUserWithPermissions(['articles.view']);
        $this->actingAsPanelUser($moderator);

        self::assertTrue(ReviewQueueWidget::canView());
        self::assertTrue(NeedsAttentionWidget::canView());
    }

    public function test_figures_are_cached_until_the_refresh_action_clears_them(): void
    {
        $reader = app(AdminDashboardReaderInterface::class);
        self::assertSame(1, $reader->contentStats()->users);

        $this->createPanelUser('Late arrival');
        self::assertSame(1, $reader->contentStats()->users);

        Livewire::test(Dashboard::class)
            ->callAction('refresh')
            ->assertRedirect();

        self::assertSame(2, $reader->contentStats()->users);
    }

    public function test_cache_outage_falls_back_to_the_live_read(): void
    {
        $failingCache = $this->createMock(CacheRepository::class);
        $failingCache->method('remember')->willThrowException(new RuntimeException('cache down'));
        $failingCache->method('forget')->willThrowException(new RuntimeException('cache down'));

        $reader = new CachedAdminDashboardReader(new DatabaseAdminDashboardReader(), $failingCache);

        self::assertSame(1, $reader->contentStats()->users);
        $reader->forget();
    }

    private function liveReader(): DatabaseAdminDashboardReader
    {
        return new DatabaseAdminDashboardReader();
    }

    private function comment(Post $post, User $author, CarbonImmutable $createdAt): void
    {
        $comment = Comment::create([
            'uuid' => (string) Str::uuid(),
            'template_id' => ObjectTemplateType::POST->getLegacyId(),
            'real_object_id' => $post->id,
            'real_object_uuid' => $post->uuid,
            'entity_type_uuid' => ObjectTemplateType::POST->value,
            'user_id' => $author->id,
            'content' => 'A comment',
        ]);
        $comment->forceFill(['created_at' => $createdAt])->save();
    }

    private function processingRow(
        ProcessingStatus $status,
        ?CarbonImmutable $finishedAt = null,
        ?CarbonImmutable $updatedAt = null,
    ): void {
        $row = ProcessingState::query()->create([
            'entity_type' => ProcessingEntityType::Article,
            'entity_id' => (string) Str::uuid(),
            'task_type' => ProcessingTaskType::ArticleContentProcessing->value,
            'status' => $status,
            'finished_at' => $finishedAt,
        ]);
        $row->timestamps = false;
        $row->forceFill(['updated_at' => $updatedAt ?? CarbonImmutable::now()])->save();
    }
}
