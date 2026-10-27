<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Application\Operations\Interfaces\Readers\ImportStatusReaderInterface;
use App\Application\Operations\Services\EnvironmentHealthServiceInterface;
use App\Domain\Operations\DTOs\HealthCheckResultDTO;
use App\Domain\Operations\DTOs\QueueWorkerCheckDTO;
use App\Domain\Operations\Enums\HealthStatus;
use App\Filament\AdminNavigationGroup;
use App\Filament\Support\PanelActor;
use App\Filament\Support\ResultNotifications;
use BackedEnum;
use Composer\InstalledVersions;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use UnitEnum;

/**
 * Post-deploy verification and incident triage without pipeline logs or SSH. Read-only apart from
 * the worker check. Secrets are shown as set or missing, never as values; the page does not poll,
 * so each view costs one Redis command on Upstash.
 */
class EnvironmentHealth extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Operations;

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Environment';

    protected static ?string $title = 'Environment and health';

    protected static ?string $slug = 'operations/environment';

    public static function canAccess(): bool
    {
        return PanelActor::may('operations.view');
    }

    public function mount(): void
    {
        $this->service()->recordViewed(PanelActor::current());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runWorkerCheck')
                ->label('Run worker check')
                ->icon(Heroicon::OutlinedPlay)
                ->requiresConfirmation()
                ->modalDescription('Puts one smoke job on the default queue and waits up to '.EnvironmentHealthServiceInterface::WORKER_CHECK_TIMEOUT_SECONDS.' seconds for a worker to run it.')
                ->visible(static fn (): bool => PanelActor::may('operations.manage'))
                ->action(function (): void {
                    $outcome = $this->service()->runWorkerCheck(PanelActor::current());

                    if ($outcome->isFailure()) {
                        ResultNotifications::failure($outcome);

                        return;
                    }

                    /** @var QueueWorkerCheckDTO $result */
                    $result = $outcome->getData();

                    $notification = Notification::make();
                    ($result->succeeded
                        ? $notification->success()->title("A worker ran the smoke job in {$result->waitedMs} ms.")
                        : $notification->danger()->title('No worker ran the smoke job within '.EnvironmentHealthServiceInterface::WORKER_CHECK_TIMEOUT_SECONDS.' seconds.'))
                        ->send();
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        $snapshot = $this->service()->snapshot();
        $dependencies = $snapshot->dependencies;
        $worker = $snapshot->lastWorkerCheck;
        $latestImport = app(ImportStatusReaderInterface::class)->runs()[0] ?? null;

        return $schema->components([
            Section::make('Release')
                ->schema([
                    TextEntry::make('release')->state((string) (config('app.release') ?: 'not set')),
                    TextEntry::make('environment')->label('APP_ENV')->state((string) config('app.env')),
                    TextEntry::make('php')->label('PHP')->state(PHP_VERSION),
                    TextEntry::make('laravel')->label('Laravel')->state(app()->version()),
                    TextEntry::make('filament')->label('Filament')->state((string) InstalledVersions::getPrettyVersion('filament/filament')),
                    TextEntry::make('debug')->label('Debug mode')->state(config('app.debug') ? 'On' : 'Off')
                        ->color(config('app.debug') && config('app.env') === 'production' ? 'danger' : null),
                ])
                ->columns(3),
            Section::make('Drivers')
                ->schema([
                    TextEntry::make('queue')->label('Queue')->state((string) config('queue.default')),
                    TextEntry::make('cache')->label('Cache')->state((string) config('cache.default')),
                    TextEntry::make('session')->label('Session')->state((string) config('session.driver'))
                        ->helperText(config('session.driver') === 'file' ? 'File sessions are lost when an ephemeral disk is replaced on redeploy.' : null),
                    TextEntry::make('broadcast')->label('Broadcasting')->state((string) config('broadcasting.default')),
                    TextEntry::make('database')->label('Database')->state(DB::getDriverName().' on '.(string) config('database.connections.'.config('database.default').'.host')),
                    TextEntry::make('mail')->label('Mail')->state((string) config('mail.default')),
                    TextEntry::make('secrets')
                        ->label('Secrets')
                        ->state($snapshot->secretPresence)
                        ->listWithLineBreaks()
                        ->columnSpanFull(),
                ])
                ->columns(3),
            Section::make('Connectivity')
                ->description('Each check runs on this page view with a '.EnvironmentHealthServiceInterface::CHECK_BUDGET_MS.' ms budget. A failed check never fails the page.')
                ->schema([
                    RepeatableEntry::make('dependencies')
                        ->hiddenLabel()
                        ->state(array_map(static fn (HealthCheckResultDTO $result): array => [
                            'name' => $result->name,
                            'status' => $result->status,
                            'detail' => $result->detail,
                            'duration' => $result->durationMs === null ? '-' : "{$result->durationMs} ms",
                        ], $dependencies))
                        ->table([
                            TableColumn::make('Dependency'),
                            TableColumn::make('Status'),
                            TableColumn::make('Detail'),
                            TableColumn::make('Took'),
                        ])
                        ->schema([
                            TextEntry::make('name'),
                            TextEntry::make('status')
                                ->badge()
                                ->formatStateUsing(static fn (HealthStatus $state): string => $state->label())
                                ->color(static fn (HealthStatus $state): string => match ($state) {
                                    HealthStatus::Ok => 'success',
                                    HealthStatus::Failed => 'danger',
                                    HealthStatus::NotConfigured => 'warning',
                                })
                                ->icon(static fn (HealthStatus $state): Heroicon => $state === HealthStatus::Ok
                                    ? Heroicon::OutlinedCheckCircle
                                    : Heroicon::OutlinedExclamationTriangle),
                            TextEntry::make('detail'),
                            TextEntry::make('duration'),
                        ]),
                ]),
            Section::make('Worker')
                ->schema([
                    TextEntry::make('worker_last')
                        ->label('Last smoke check')
                        ->state($worker === null
                            ? 'No check has run yet'
                            : ($worker->succeeded ? 'Passed' : 'Failed').' on queue '.$worker->queue.' after '.$worker->waitedMs.' ms')
                        ->color($worker === null ? 'gray' : ($worker->succeeded ? 'success' : 'danger')),
                    TextEntry::make('worker_checked_at')
                        ->label('Checked')
                        ->state($worker?->checkedAt)
                        ->dateTime()
                        ->placeholder('-'),
                ])
                ->columns(2),
            Section::make('Data')
                ->schema([
                    TextEntry::make('import')
                        ->label('Latest dictionary import')
                        ->state($latestImport === null
                            ? 'No import has completed in this database'
                            : $latestImport->environment.', completed '.($latestImport->completedAt ?? 'never'))
                        ->url(ImportStatus::canAccess() ? ImportStatus::getUrl() : null),
                ]),
        ]);
    }

    private function service(): EnvironmentHealthServiceInterface
    {
        return app(EnvironmentHealthServiceInterface::class);
    }
}
