<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Application\Articles\Jobs\ProcessArticleContentJob;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Activity\Enums\ActivitySource;
use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingStatus;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\SavedListType;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Filament\Resources\Articles\Pages\ViewArticle;
use App\Infrastructure\Persistence\Models\AdminActivity;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\HashtagEntity;
use App\Infrastructure\Persistence\Models\Kanji;
use App\Infrastructure\Persistence\Models\ProcessingState;
use App\Infrastructure\Persistence\Models\Uniquehashtag;
use App\Infrastructure\Persistence\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Livewire\Livewire;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\TestCase;

class ArticleResourceTest extends TestCase
{
    use InteractsWithAdminPanel;
    use RefreshDatabase;

    private User $admin;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        $this->seedAdminPanel();
        $this->admin = $this->createAdmin();
        $this->author = $this->createPanelUser('Article Author');
        $this->actingAsPanelUser($this->admin);
    }

    public function test_list_defaults_to_articles_awaiting_review(): void
    {
        $pending = $this->article(ArticleStatus::PENDING);
        $reviewing = $this->article(ArticleStatus::REVIEWING);
        $approved = $this->article(ArticleStatus::APPROVED);

        Livewire::test(ListArticles::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$pending, $reviewing])
            ->assertCanNotSeeTableRecords([$approved])
            ->removeTableFilter('status')
            ->assertCanSeeTableRecords([$pending, $reviewing, $approved]);
    }

    public function test_list_filters_by_publicity_processing_author_and_hashtag(): void
    {
        $public = $this->article(ArticleStatus::PENDING);
        $private = $this->article(ArticleStatus::PENDING, ['publicity' => PublicityStatus::PRIVATE]);
        $this->processing($public, ProcessingStatus::FAILED);
        $other = $this->createPanelUser('Other author');
        $byOther = $this->article(ArticleStatus::PENDING, ['user_id' => $other->id]);
        $this->tag($private, 'grammar');

        Livewire::test(ListArticles::class)
            ->filterTable('publicity', PublicityStatus::PRIVATE->value)
            ->assertCanSeeTableRecords([$private])
            ->assertCanNotSeeTableRecords([$public, $byOther]);

        Livewire::test(ListArticles::class)
            ->filterTable('processing', ProcessingStatus::FAILED->value)
            ->assertCanSeeTableRecords([$public])
            ->assertCanNotSeeTableRecords([$private]);

        Livewire::test(ListArticles::class)
            ->filterTable('processing', 'never')
            ->assertCanSeeTableRecords([$private, $byOther])
            ->assertCanNotSeeTableRecords([$public]);

        Livewire::test(ListArticles::class)
            ->filterTable('user_id', $other->id)
            ->assertCanSeeTableRecords([$byOther])
            ->assertCanNotSeeTableRecords([$public]);

        Livewire::test(ListArticles::class)
            ->filterTable('hashtag', ['tag' => 'gram'])
            ->assertCanSeeTableRecords([$private])
            ->assertCanNotSeeTableRecords([$public]);
    }

    public function test_view_page_shows_content_hashtags_and_processing(): void
    {
        $article = $this->article(ArticleStatus::PENDING, ['content_jp' => '本文のテキスト']);
        $this->tag($article, 'reading');
        $this->processing($article, ProcessingStatus::FAILED, 'Extraction timed out');

        foreach (['水', '火'] as $character) {
            $kanji = Kanji::query()->forceCreate([
                'uuid' => (string) Str::uuid(), 'kanji' => $character, 'onyomi' => '-', 'kunyomi' => '-', 'meaning' => '-',
                'nanori' => '-', 'jlpt' => '5', 'grade' => '1', 'stroke_count' => 4, 'frequency' => 1, 'radicals' => '-', 'radical_parts' => '-',
            ]);
            DB::table('article_kanji')->insert(['article_id' => $article->id, 'kanji_id' => $kanji->id]);
        }

        foreach (range(1, 3) as $index) {
            DB::table('likes')->insert([
                'user_id' => $this->createPanelUser("Liker {$index}")->id,
                'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
                'real_object_id' => $article->id,
                'value' => 1,
            ]);
        }

        Livewire::test(ViewArticle::class, ['record' => $article->uuid])
            ->assertOk()
            ->assertSee('本文のテキスト')
            ->assertSee('reading')
            ->assertSee('Extraction timed out')
            ->assertSee('Article Author')
            ->assertSeeInOrder(['Likes', '3', 'Comments', '0'])
            ->assertSeeInOrder(['Kanji', '2', 'Words', '0']);
    }

    public function test_status_change_goes_through_the_service_and_records_the_reason(): void
    {
        $article = $this->article(ArticleStatus::PENDING);
        $this->processing($article, ProcessingStatus::COMPLETED);

        Livewire::test(ViewArticle::class, ['record' => $article->uuid])
            ->callAction('setStatus', ['status' => ArticleStatus::REJECTED->value, 'reason' => 'Copied text'])
            ->assertHasNoActionErrors()
            ->assertNotified('Status set to Rejected.');

        self::assertSame(ArticleStatus::REJECTED, $article->fresh()->status);

        $row = AdminActivity::query()->sole();
        self::assertSame(ActivityAction::ArticleStatusChanged, $row->action);
        self::assertSame(['status' => 'Pending'], $row->changes['before']);
        self::assertSame(['status' => 'Rejected'], $row->changes['after']);
        self::assertSame(['reason' => 'Copied text'], $row->context);
    }

    public function test_approval_with_unfinished_processing_needs_explicit_confirmation(): void
    {
        $article = $this->article(ArticleStatus::PENDING);
        $this->processing($article, ProcessingStatus::FAILED);

        Livewire::test(ViewArticle::class, ['record' => $article->uuid])
            ->callAction('setStatus', ['status' => ArticleStatus::APPROVED->value])
            ->assertHasActionErrors(['approve_anyway']);

        self::assertSame(ArticleStatus::PENDING, $article->fresh()->status);

        Livewire::test(ViewArticle::class, ['record' => $article->uuid])
            ->callAction('setStatus', ['status' => ArticleStatus::APPROVED->value, 'approve_anyway' => true])
            ->assertHasNoActionErrors();

        self::assertSame(ArticleStatus::APPROVED, $article->fresh()->status);
        self::assertSame(
            ProcessingStatus::FAILED->value,
            AdminActivity::query()->where('action', ActivityAction::ArticleStatusChanged)->sole()->context['approved_despite_processing'],
        );
    }

    public function test_bulk_status_change_reports_the_outcome(): void
    {
        $first = $this->article(ArticleStatus::PENDING);
        $second = $this->article(ArticleStatus::REVIEWING);
        $this->processing($first, ProcessingStatus::COMPLETED);
        $this->processing($second, ProcessingStatus::COMPLETED);

        Livewire::test(ListArticles::class)
            ->callTableBulkAction('bulkSetStatus', [$first, $second], ['status' => ArticleStatus::APPROVED->value])
            ->assertNotified('2 changed, 0 failed');

        self::assertSame(ArticleStatus::APPROVED, $first->fresh()->status);
        self::assertSame(ArticleStatus::APPROVED, $second->fresh()->status);
        self::assertSame(2, AdminActivity::query()->where('action', ActivityAction::ArticleStatusChanged)->count());
    }

    public function test_publicity_toggles_independently_of_status(): void
    {
        $article = $this->article(ArticleStatus::APPROVED);

        Livewire::test(ViewArticle::class, ['record' => $article->uuid])
            ->callAction('togglePublicity')
            ->assertNotified('The article is now private.');

        $article->refresh();
        self::assertSame(PublicityStatus::PRIVATE, $article->publicity);
        self::assertSame(ArticleStatus::APPROVED, $article->status);
        self::assertSame(ActivityAction::ArticlePublicityChanged, AdminActivity::query()->sole()->action);
    }

    public function test_reprocess_queues_the_live_job_and_refuses_a_healthy_run_unless_forced(): void
    {
        $article = $this->article(ArticleStatus::PENDING, ['content_version' => 3]);
        $this->processing($article, ProcessingStatus::PROCESSING);

        Livewire::test(ViewArticle::class, ['record' => $article->uuid])
            ->callAction('reprocess')
            ->assertNotified('This article is still being processed. Wait for it to finish or fail, or force the retry.');

        Queue::assertNothingPushed();

        Livewire::test(ViewArticle::class, ['record' => $article->uuid])
            ->callAction('reprocess', ['force' => true])
            ->assertNotified('Processing was queued again.');

        Queue::assertPushed(ProcessArticleContentJob::class, static fn (ProcessArticleContentJob $job): bool => $job->articleUuid === (string) $article->uuid && $job->contentVersion === 3);

        $state = ProcessingState::query()->where('entity_id', $article->uuid)->sole();
        self::assertSame(ProcessingStatus::PENDING, $state->status);

        $row = AdminActivity::query()->sole();
        self::assertSame(ActivityAction::ArticleReprocessRequested, $row->action);
        self::assertTrue($row->context['forced']);
    }

    public function test_delete_runs_the_service_cleanup_and_records_a_snapshot(): void
    {
        $article = $this->article(ArticleStatus::REJECTED, ['title_jp' => '削除する記事']);
        $this->tag($article, 'cleanup');
        $catalogue = Catalogue::factory()->ofType(SavedListType::ARTICLES)->create(['user_id' => $this->author->id]);
        DB::table('customlist_object')->insert(['list_id' => $catalogue->id, 'real_object_id' => $article->id, 'listtype_id' => SavedListType::ARTICLES->value]);

        Livewire::test(ViewArticle::class, ['record' => $article->uuid])
            ->callAction('deleteArticle', ['confirmation' => 'nope'])
            ->assertHasActionErrors(['confirmation']);

        self::assertNotNull($article->fresh());

        Livewire::test(ViewArticle::class, ['record' => $article->uuid])
            ->callAction('deleteArticle', ['confirmation' => 'DELETE'])
            ->assertHasNoActionErrors()
            ->assertRedirect();

        self::assertNull($article->fresh());
        self::assertSame(0, HashtagEntity::query()->withTrashed()->where('entity_id', $article->id)->whereNull('deleted_at')->count());
        self::assertSame(0, DB::table('customlist_object')->where('real_object_id', $article->id)->count());

        $row = AdminActivity::query()->sole();
        self::assertSame(ActivityAction::ArticleDeleted, $row->action);
        self::assertSame('削除する記事', $row->changes['before']['title']);
        self::assertSame('Article Author', $row->changes['before']['author']);
    }

    public function test_viewer_permission_lists_without_moderation_actions(): void
    {
        $article = $this->article(ArticleStatus::PENDING);
        $viewer = $this->createUserWithPermissions(['articles.view']);
        $this->actingAsPanelUser($viewer);

        Livewire::test(ListArticles::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$article])
            ->assertTableActionHidden('setStatus', $article);

        Livewire::test(ViewArticle::class, ['record' => $article->uuid])
            ->assertOk()
            ->assertActionHidden('setStatus')
            ->assertActionHidden('togglePublicity')
            ->assertActionHidden('reprocess')
            ->assertActionHidden('deleteArticle');
    }

    public function test_write_permission_without_the_application_rule_hides_the_action(): void
    {
        $article = $this->article(ArticleStatus::PENDING);
        $moderator = $this->createUserWithPermissions(['articles.view', 'articles.update', 'articles.delete']);
        $this->actingAsPanelUser($moderator);

        Livewire::test(ViewArticle::class, ['record' => $article->uuid])
            ->assertOk()
            ->assertActionHidden('setStatus')
            ->assertActionHidden('deleteArticle');
    }

    public function test_articles_are_forbidden_without_articles_view(): void
    {
        $this->actingAs($this->createUserWithPermissions(['users.view']), 'web')
            ->get('/admin/articles')
            ->assertForbidden();
    }

    public function test_api_status_change_is_recorded_too(): void
    {
        $article = $this->article(ArticleStatus::PENDING);
        $this->processing($article, ProcessingStatus::COMPLETED);
        Passport::actingAs($this->admin, ['*'], 'api');

        $this->postJson("/api/v1/articles/{$article->uuid}/status", ['status' => ArticleStatus::APPROVED->value])
            ->assertOk();

        $row = AdminActivity::query()->sole();
        self::assertSame(ActivitySource::Api, $row->source);
        self::assertSame(ActivityAction::ArticleStatusChanged, $row->action);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function article(ArticleStatus $status, array $attributes = []): Article
    {
        return Article::factory()->create([
            'user_id' => $this->author->id,
            'status' => $status,
            ...$attributes,
        ]);
    }

    private function processing(Article $article, ProcessingStatus $status, ?string $error = null): void
    {
        ProcessingState::query()->create([
            'entity_type' => ProcessingEntityType::Article,
            'entity_id' => $article->uuid,
            'task_type' => ProcessingTaskType::ArticleContentProcessing->value,
            'status' => $status,
            'attempt' => 1,
            'started_at' => CarbonImmutable::now()->subMinute(),
            'finished_at' => $status->isTerminal() ? CarbonImmutable::now() : null,
            'error_code' => $error === null ? null : 'timeout',
            'error_message' => $error,
        ]);
    }

    private function tag(Article $article, string $content): void
    {
        $hashtag = Uniquehashtag::query()->firstOrCreate(['content' => $content]);
        HashtagEntity::query()->create([
            'entity_type_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'entity_id' => $article->id,
            'hashtag_id' => $hashtag->id,
            'user_id' => $this->author->id,
        ]);
    }
}
