<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Application\Operations\Interfaces\Readers\ImportStatusReaderInterface;
use App\Domain\Operations\Enums\DatasetDrift;
use App\Filament\Pages\ImportStatus;
use App\Support\JapaneseDataImport\JapaneseDataManifest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\TestCase;

class ImportStatusPageTest extends TestCase
{
    use InteractsWithAdminPanel;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAdminPanel();
        $this->actingAsPanelUser($this->createAdmin());
    }

    public function test_drift_states_follow_recorded_and_live_counts(): void
    {
        self::assertSame(DatasetDrift::InSync, DatasetDrift::compare(10, 10));
        self::assertSame(DatasetDrift::RowsAdded, DatasetDrift::compare(10, 12));
        self::assertSame(DatasetDrift::RowsMissing, DatasetDrift::compare(10, 8));
        self::assertSame(DatasetDrift::NeverImported, DatasetDrift::compare(null, 8));
        self::assertSame(DatasetDrift::TableMissing, DatasetDrift::compare(10, null));
    }

    public function test_page_shows_the_latest_run_and_per_table_health(): void
    {
        $this->radical('一');
        $this->radical('丨');
        $this->sentinel('benchmark', now()->subDay(), ['japanese_radicals_bank_long' => 2, 'japanese_kanji_bank_long' => 5]);

        $health = app(ImportStatusReaderInterface::class)->datasetHealth('benchmark');
        $byTable = collect($health)->keyBy('table');

        self::assertSame(DatasetDrift::InSync, $byTable['japanese_radicals_bank_long']->drift);
        self::assertSame(DatasetDrift::RowsMissing, $byTable['japanese_kanji_bank_long']->drift);
        self::assertSame(DatasetDrift::NeverImported, $byTable['japanese_tatoeba_sentences']->drift);
        self::assertSame(count((new JapaneseDataManifest())->datasets()), count($health));

        Livewire::test(ImportStatus::class)
            ->assertOk()
            ->assertSee('benchmark')
            ->assertSee('japanese_radicals_bank_long')
            ->assertSee('In sync')
            ->assertSee('Rows missing');
    }

    public function test_missing_dump_files_are_listed(): void
    {
        $manifest = new JapaneseDataManifest();
        $missing = array_values(array_filter($manifest->datasets(), static fn (array $dataset): bool => ! $manifest->sourceExists($dataset)));

        $component = Livewire::test(ImportStatus::class)->assertOk();

        if ($missing === []) {
            $component->assertSee('dump files are present');
        } else {
            $component->assertSee('dump files are missing')->assertSee($missing[0]['source']);
        }
    }

    public function test_live_counts_are_cached_until_refreshed(): void
    {
        $reader = app(ImportStatusReaderInterface::class);
        $this->radical('一');

        $first = collect($reader->datasetHealth(null))->firstWhere('table', 'japanese_radicals_bank_long');
        $this->radical('丨');
        $cached = collect($reader->datasetHealth(null))->firstWhere('table', 'japanese_radicals_bank_long');

        self::assertSame(1, $first->liveRows);
        self::assertSame(1, $cached->liveRows);

        Livewire::test(ImportStatus::class)
            ->callAction('refreshCounts')
            ->assertNotified('Row counts refreshed.');

        $fresh = collect($reader->datasetHealth(null))->firstWhere('table', 'japanese_radicals_bank_long');
        self::assertSame(2, $fresh->liveRows);
    }

    public function test_sequence_behind_the_data_is_reported(): void
    {
        DB::table('japanese_radicals_bank_long')->insert(['id' => 500, 'uuid' => (string) Str::uuid(), 'radical' => '乙', 'strokes' => 1, 'meaning' => 'second']);

        $radicals = collect(app(ImportStatusReaderInterface::class)->datasetHealth(null))->firstWhere('table', 'japanese_radicals_bank_long');

        self::assertTrue($radicals->sequenceBehind);
    }

    public function test_page_is_forbidden_without_operations_view(): void
    {
        $this->actingAs($this->createUserWithPermissions(['articles.view']), 'web')
            ->get('/admin/operations/import-status')
            ->assertForbidden();
    }

    public function test_page_opens_with_operations_view(): void
    {
        $this->actingAs($this->createUserWithPermissions(['operations.view']), 'web')
            ->get('/admin/operations/import-status')
            ->assertOk();
    }

    private function radical(string $radical): void
    {
        DB::table('japanese_radicals_bank_long')->insert([
            'uuid' => (string) Str::uuid(),
            'radical' => $radical,
            'strokes' => 1,
            'meaning' => 'one',
        ]);
    }

    /**
     * @param array<string, int> $datasets
     */
    private function sentinel(string $environment, \DateTimeInterface $completedAt, array $datasets): void
    {
        DB::table('environment_bootstrap_runs')->insert([
            'environment' => $environment,
            'task' => JapaneseDataManifest::SENTINEL_TASK,
            'completed_at' => $completedAt,
            'metadata' => json_encode(['datasets' => $datasets]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
