<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Application\Processing\Events\ProcessingStatusUpdated;
use App\Console\Commands\SweepStaleArticleProcessing;
use App\Domain\Processing\Enums\ProcessingStatus;
use App\Infrastructure\Persistence\Models\ProcessingState;
use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class SweepStaleArticleProcessingCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sweeper_fails_stale_non_terminal_rows_and_leaves_fresh_and_terminal_rows_alone(): void
    {
        $staleProcessing = $this->insertOperation(ProcessingStatus::PROCESSING, ageSeconds: 400);
        $stalePending = $this->insertOperation(ProcessingStatus::PENDING, ageSeconds: 400);
        $freshProcessing = $this->insertOperation(ProcessingStatus::PROCESSING, ageSeconds: 60);
        $oldCompleted = $this->insertOperation(ProcessingStatus::COMPLETED, ageSeconds: 4000);
        $oldFailed = $this->insertOperation(ProcessingStatus::FAILED, ageSeconds: 4000);

        Event::fake([ProcessingStatusUpdated::class]);

        $exitCode = Artisan::call('article-processing:sweep-stale');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Swept 2 stale processing operation(s) older than 330 seconds.', Artisan::output());

        foreach ([$staleProcessing, $stalePending] as $swept) {
            $swept->refresh();
            $this->assertSame(ProcessingStatus::FAILED, $swept->status);
            $this->assertSame('stale', $swept->error_code);
            $this->assertSame('no heartbeat', $swept->error_message);
            $this->assertSame('no heartbeat', $swept->metadata['reason']);
        }

        $this->assertSame(ProcessingStatus::PROCESSING, $freshProcessing->refresh()->status);
        $this->assertSame(ProcessingStatus::COMPLETED, $oldCompleted->refresh()->status);
        $this->assertNull($oldFailed->refresh()->error_code, 'Already-failed rows must not be rewritten.');

        Event::assertDispatchedTimes(ProcessingStatusUpdated::class, 2);
    }

    public function test_older_than_option_overrides_the_threshold(): void
    {
        $row = $this->insertOperation(ProcessingStatus::PROCESSING, ageSeconds: 30);

        Artisan::call('article-processing:sweep-stale', ['--older-than' => 10]);

        $this->assertSame(ProcessingStatus::FAILED, $row->refresh()->status);
    }

    public function test_sweeper_is_scheduled_every_five_minutes_without_overlapping(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn (ScheduledEvent $event): bool => str_contains($event->command ?? '', 'article-processing:sweep-stale'));

        $this->assertNotNull($event, 'article-processing:sweep-stale is not registered in the scheduler.');
        $this->assertSame('*/5 * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping, 'The sweeper should not overlap itself.');
    }

    public function test_default_threshold_covers_timeout_plus_retry_after(): void
    {
        $this->assertSame(330, SweepStaleArticleProcessing::DEFAULT_OLDER_THAN_SECONDS);
        $this->assertGreaterThan(120 + config('queue.connections.redis.retry_after'), SweepStaleArticleProcessing::DEFAULT_OLDER_THAN_SECONDS);
    }

    private function insertOperation(ProcessingStatus $status, int $ageSeconds): ProcessingState
    {
        $row = ProcessingState::create([
            'entity_type' => 'article',
            'entity_id' => (string) Str::uuid(),
            'task_type' => 'article_content_processing',
            'status' => $status,
            'content_version' => 1,
            'metadata' => [],
        ]);

        // Eloquent refreshes updated_at on save, so age the row with a raw update.
        DB::table('processing_states')
            ->where('id', $row->id)
            ->update(['updated_at' => now()->subSeconds($ageSeconds)]);

        return $row->refresh();
    }
}
