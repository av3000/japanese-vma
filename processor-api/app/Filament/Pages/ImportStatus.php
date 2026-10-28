<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Application\Operations\Interfaces\Readers\ImportStatusReaderInterface;
use App\Application\Users\Support\PanelPermission;
use App\Domain\Operations\DTOs\DatasetHealthDTO;
use App\Domain\Operations\DTOs\ImportRunDTO;
use App\Domain\Operations\Enums\DatasetDrift;
use App\Filament\AdminNavigationGroup;
use App\Filament\Support\PanelActor;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Is the dictionary data here fresh and complete? Read-only: an import truncates tables and is
 * run deliberately from the GitLab pipeline, never from the panel.
 */
class ImportStatus extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Operations;

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Dictionary import';

    protected static ?string $title = 'Dictionary import';

    protected static ?string $slug = 'operations/import-status';

    #[Url]
    public ?string $environment = null;

    public static function canAccess(): bool
    {
        return PanelActor::may(PanelPermission::OperationsView);
    }

    public function getSubheading(): string
    {
        return 'Live row counts are cached for '.intdiv(ImportStatusReaderInterface::LIVE_COUNT_TTL_SECONDS, 60).' minutes. Environment names are the ones the pipeline passed, not APP_ENV.';
    }

    protected function getHeaderActions(): array
    {
        $runs = $this->reader()->runs();

        return [
            ActionGroup::make(array_map(
                fn (ImportRunDTO $run): Action => Action::make('environment_'.md5($run->environment))
                    ->label($run->environment)
                    ->action(fn () => $this->environment = $run->environment),
                $runs,
            ))
                ->label('Compare with: '.($this->selectedRun()?->environment ?? 'no import yet'))
                ->button()
                ->color('gray')
                ->visible($runs !== []),
            Action::make('refreshCounts')
                ->label('Recount rows')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->action(function (): void {
                    $this->reader()->refreshLiveCounts();
                    Notification::make()->success()->title('Row counts refreshed.')->send();
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        $runs = $this->reader()->runs();
        $health = $this->reader()->datasetHealth($this->selectedRun()?->environment);
        $missing = array_values(array_filter($health, static fn (DatasetHealthDTO $dataset): bool => ! $dataset->sourcePresent));

        return $schema->components([
            Section::make('Import runs')
                ->description('The latest completed import per environment. Only the latest run is kept.')
                ->schema([
                    RepeatableEntry::make('runs')
                        ->hiddenLabel()
                        ->state(array_map(static fn (ImportRunDTO $run): array => [
                            'environment' => $run->environment,
                            'completed_at' => $run->completedAt,
                            'tables' => count($run->datasets),
                            'rows' => number_format($run->totalRows()),
                        ], $runs))
                        ->placeholder('No import has completed in this database.')
                        ->table([
                            TableColumn::make('Environment'),
                            TableColumn::make('Completed'),
                            TableColumn::make('Tables'),
                            TableColumn::make('Rows written'),
                        ])
                        ->schema([
                            TextEntry::make('environment'),
                            TextEntry::make('completed_at')->dateTime()->placeholder('Not completed'),
                            TextEntry::make('tables'),
                            TextEntry::make('rows'),
                        ]),
                ]),
            Section::make('Dataset health')
                ->description('Recorded rows come from the selected run; live rows are counted now. More rows than recorded is expected for sentences, which users also write.')
                ->schema([
                    RepeatableEntry::make('datasets')
                        ->hiddenLabel()
                        ->state(array_map(static fn (DatasetHealthDTO $dataset): array => [
                            'table' => $dataset->table,
                            'recorded' => $dataset->recordedRows === null ? '-' : number_format($dataset->recordedRows),
                            'live' => $dataset->liveRows === null ? '-' : number_format($dataset->liveRows),
                            'drift' => $dataset->drift,
                            'sequence' => match ($dataset->sequenceBehind) {
                                true => 'Behind the data',
                                false => 'OK',
                                null => '-',
                            },
                        ], $health))
                        ->table([
                            TableColumn::make('Table'),
                            TableColumn::make('Recorded'),
                            TableColumn::make('Live'),
                            TableColumn::make('State'),
                            TableColumn::make('Id sequence'),
                        ])
                        ->schema([
                            TextEntry::make('table')->fontFamily('mono'),
                            TextEntry::make('recorded'),
                            TextEntry::make('live'),
                            TextEntry::make('drift')
                                ->badge()
                                ->formatStateUsing(static fn (DatasetDrift $state): string => $state->label())
                                ->color(static fn (DatasetDrift $state): string => match (true) {
                                    $state === DatasetDrift::InSync => 'success',
                                    $state->needsAttention() => 'danger',
                                    default => 'warning',
                                })
                                ->icon(static fn (DatasetDrift $state): Heroicon => $state->needsAttention()
                                    ? Heroicon::OutlinedExclamationTriangle
                                    : Heroicon::OutlinedCheckCircle),
                            TextEntry::make('sequence')
                                ->color(static fn (string $state): ?string => $state === 'Behind the data' ? 'danger' : null),
                        ]),
                ]),
            Section::make('Dump files')
                ->description('Every source in the manifest must exist on disk before a rerun.')
                ->schema([
                    TextEntry::make('sources_summary')
                        ->hiddenLabel()
                        ->state($missing === []
                            ? 'All '.count($health).' dump files are present.'
                            : count($missing).' of '.count($health).' dump files are missing:')
                        ->color($missing === [] ? 'success' : 'danger')
                        ->icon($missing === [] ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedExclamationTriangle),
                    TextEntry::make('missing_sources')
                        ->hiddenLabel()
                        ->state(array_map(static fn (DatasetHealthDTO $dataset): string => $dataset->source, $missing))
                        ->listWithLineBreaks()
                        ->bulleted()
                        ->fontFamily('mono')
                        ->visible($missing !== []),
                ]),
        ]);
    }

    private function selectedRun(): ?ImportRunDTO
    {
        $runs = $this->reader()->runs();

        foreach ($runs as $run) {
            if ($run->environment === $this->environment) {
                return $run;
            }
        }

        return $runs[0] ?? null;
    }

    private function reader(): ImportStatusReaderInterface
    {
        return app(ImportStatusReaderInterface::class);
    }
}
