<?php

declare(strict_types=1);

namespace Tests\Feature\Processing;

use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingStatus;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Domain\Processing\ProcessingThresholds;
use App\Infrastructure\Persistence\Models\ProcessingState;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The stale rule exists twice, as ProcessingThresholds::isStale() and as the ProcessingState
 * `stale` scope. This test seeds every interesting row and requires both to pick the same ones.
 */
final class StaleProcessingRuleAgreementTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_scope_and_the_predicate_agree_on_every_row(): void
    {
        $now = CarbonImmutable::parse('2026-10-27 12:00:00');
        CarbonImmutable::setTestNow($now);
        $threshold = ProcessingThresholds::STALE_AFTER_SECONDS;

        foreach (ProcessingStatus::cases() as $status) {
            foreach ([10, $threshold - 1, $threshold, $threshold + 60, null] as $updatedAgo) {
                foreach ([10, $threshold + 60, null] as $createdAgo) {
                    $this->row($status, $now, $updatedAgo, $createdAgo);
                }
            }
        }

        $expected = ProcessingState::query()->orderBy('id')->get()
            ->filter(static fn (ProcessingState $row): bool => ProcessingThresholds::isStale($row->status, $row->updated_at, $row->created_at, $now))
            ->pluck('id')
            ->values()
            ->all();

        $fromScope = ProcessingState::query()->stale(ProcessingThresholds::staleBefore($now))->orderBy('id')->pluck('id')->all();

        self::assertNotEmpty($expected);
        self::assertSame($expected, $fromScope);

        CarbonImmutable::setTestNow();
    }

    private function row(ProcessingStatus $status, CarbonImmutable $now, ?int $updatedAgo, ?int $createdAgo): void
    {
        DB::table('processing_states')->insert([
            'entity_type' => ProcessingEntityType::Article->value,
            'entity_id' => (string) Str::uuid(),
            'task_type' => ProcessingTaskType::ArticleContentProcessing->value,
            'status' => $status->value,
            'attempt' => 1,
            'updated_at' => $updatedAgo === null ? null : $now->subSeconds($updatedAgo),
            'created_at' => $createdAgo === null ? null : $now->subSeconds($createdAgo),
        ]);
    }
}
