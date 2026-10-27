<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Application\Articles\Jobs\ProcessArticleContentJob;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingStatus;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Filament\Resources\Processing\Pages\ListProcessingStates;
use App\Filament\Resources\Processing\Pages\ViewProcessingState;
use App\Filament\Resources\Processing\ProcessingStateResource;
use App\Infrastructure\Persistence\Models\AdminActivity;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\ProcessingState;
use App\Infrastructure\Persistence\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\TestCase;

class ProcessingMonitorTest extends TestCase
{
    use InteractsWithAdminPanel;
    use RefreshDatabase;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        $this->seedAdminPanel();
        $this->author = $this->createPanelUser('Author');
        $this->actingAsPanelUser($this->createAdmin());
    }

    public function test_needs_attention_preset_shows_failed_and_stuck_runs_only(): void
    {
        $failed = $this->state(ProcessingStatus::FAILED, error: 'Word extraction timed out');
        $stuck = $this->state(ProcessingStatus::PROCESSING, updatedAt: CarbonImmutable::now()->subMinutes(20));
        $running = $this->state(ProcessingStatus::PROCESSING, updatedAt: CarbonImmutable::now()->subMinute());
        $done = $this->state(ProcessingStatus::COMPLETED);

        Livewire::test(ListProcessingStates::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$failed, $stuck])
            ->assertCanNotSeeTableRecords([$running, $done])
            ->assertSee('Word extraction timed out')
            ->assertSee('Stuck');

        Livewire::test(ListProcessingStates::class, ['activeTab' => 'all'])
            ->assertCanSeeTableRecords([$failed, $stuck, $running, $done]);

        Livewire::test(ListProcessingStates::class, ['activeTab' => 'all'])
            ->filterTable('stuck')
            ->assertCanSeeTableRecords([$stuck])
            ->assertCanNotSeeTableRecords([$running]);

        Livewire::test(ListProcessingStates::class, ['activeTab' => 'all'])
            ->filterTable('status', [ProcessingStatus::COMPLETED->value])
            ->assertCanSeeTableRecords([$done])
            ->assertCanNotSeeTableRecords([$failed]);
    }

    public function test_view_page_shows_metadata_and_full_error(): void
    {
        $state = $this->state(ProcessingStatus::FAILED, error: str_repeat('trace line ', 20), metadata: ['stage' => 'words']);

        Livewire::test(ViewProcessingState::class, ['record' => $state->getKey()])
            ->assertOk()
            ->assertSee('trace line trace line')
            ->assertSee('"stage": "words"');
    }

    public function test_retry_dispatches_the_live_job_and_records_activity(): void
    {
        $state = $this->state(ProcessingStatus::FAILED, contentVersion: 4);

        Livewire::test(ListProcessingStates::class)
            ->callTableAction('retry', $state)
            ->assertNotified('Processing was queued again.');

        Queue::assertPushed(ProcessArticleContentJob::class, static fn (ProcessArticleContentJob $job): bool => $job->articleUuid === $state->entity_id && $job->contentVersion === 4);
        self::assertSame(ProcessingStatus::PENDING, $state->fresh()->status);
        self::assertSame(ActivityAction::ArticleReprocessRequested, AdminActivity::query()->sole()->action);
    }

    public function test_a_run_that_never_reported_progress_is_stuck_and_retryable_without_force(): void
    {
        $silent = $this->state(ProcessingStatus::PENDING);
        DB::table('processing_states')->where('id', $silent->id)->update(['updated_at' => null, 'created_at' => CarbonImmutable::now()->subHour()]);

        Livewire::test(ListProcessingStates::class)
            ->assertCanSeeTableRecords([$silent])
            ->assertSee('Stuck');

        Livewire::test(ListProcessingStates::class, ['activeTab' => 'all'])
            ->filterTable('stuck')
            ->assertCanSeeTableRecords([$silent]);

        Livewire::test(ListProcessingStates::class)
            ->callTableAction('retry', $silent->fresh())
            ->assertNotified('Processing was queued again.');

        self::assertSame(ProcessingStatus::PENDING, $silent->fresh()->status);
        Queue::assertPushed(ProcessArticleContentJob::class);
    }

    public function test_a_non_admin_holding_operations_manage_can_retry(): void
    {
        $this->actingAsPanelUser($this->createUserWithPermissions(['operations.view', 'operations.manage']));
        $state = $this->state(ProcessingStatus::FAILED, contentVersion: 2);

        Livewire::test(ListProcessingStates::class)
            ->callTableAction('retry', $state)
            ->assertNotified('Processing was queued again.');

        Queue::assertPushed(ProcessArticleContentJob::class);
        self::assertSame(ProcessingStatus::PENDING, $state->fresh()->status);
    }

    public function test_retry_is_refused_for_a_healthy_run_unless_forced(): void
    {
        $state = $this->state(ProcessingStatus::PROCESSING, updatedAt: CarbonImmutable::now()->subMinute());

        Livewire::test(ViewProcessingState::class, ['record' => $state->getKey()])
            ->callAction('retry')
            ->assertNotified('This article is still being processed. Wait for it to finish or fail, or force the retry.');

        Queue::assertNothingPushed();

        Livewire::test(ViewProcessingState::class, ['record' => $state->getKey()])
            ->callAction('retry', ['force' => true])
            ->assertNotified('Processing was queued again.');

        Queue::assertPushed(ProcessArticleContentJob::class);
    }

    public function test_bulk_retry_stops_at_the_cap(): void
    {
        $states = collect(range(1, ProcessingStateResource::RETRY_LIMIT + 1))
            ->map(fn (): ProcessingState => $this->state(ProcessingStatus::FAILED));

        Livewire::test(ListProcessingStates::class)
            ->callTableBulkAction('bulkRetry', $states)
            ->assertNotified('Select at most '.ProcessingStateResource::RETRY_LIMIT.' runs to retry; '.$states->count().' were selected.');

        Queue::assertNothingPushed();

        Livewire::test(ListProcessingStates::class)
            ->callTableBulkAction('bulkRetry', $states->take(3))
            ->assertNotified('3 queued, 0 failed');

        Queue::assertPushed(ProcessArticleContentJob::class, 3);
    }

    public function test_viewers_cannot_retry_and_others_cannot_see(): void
    {
        $state = $this->state(ProcessingStatus::FAILED);
        $this->actingAsPanelUser($this->createUserWithPermissions(['operations.view']));

        Livewire::test(ListProcessingStates::class)
            ->assertCanSeeTableRecords([$state])
            ->assertTableActionHidden('retry', $state);

        $this->actingAs($this->createUserWithPermissions(['articles.view']), 'web')
            ->get('/admin/operations/processing')
            ->assertForbidden();
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function state(
        ProcessingStatus $status,
        ?CarbonImmutable $updatedAt = null,
        ?string $error = null,
        int $contentVersion = 1,
        array $metadata = [],
    ): ProcessingState {
        $article = Article::factory()->create(['user_id' => $this->author->id, 'content_version' => $contentVersion]);

        $state = ProcessingState::query()->create([
            'entity_type' => ProcessingEntityType::Article,
            'entity_id' => $article->uuid,
            'task_type' => ProcessingTaskType::ArticleContentProcessing->value,
            'status' => $status,
            'attempt' => 1,
            'content_version' => $contentVersion,
            'started_at' => CarbonImmutable::now()->subMinutes(30),
            'finished_at' => $status->isTerminal() ? CarbonImmutable::now()->subMinutes(29) : null,
            'error_code' => $error === null ? null : 'timeout',
            'error_message' => $error === null ? null : mb_substr($error, 0, 300),
            'metadata' => $metadata,
        ]);
        $state->timestamps = false;
        $state->forceFill(['updated_at' => $updatedAt ?? CarbonImmutable::now()->subMinutes(29)])->save();

        return $state;
    }
}
