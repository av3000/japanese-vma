<?php

declare(strict_types=1);

namespace App\Filament\Resources\Processing\Actions;

use App\Application\Articles\Actions\Processing\RequeueArticleProcessingAction;
use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Filament\Resources\Processing\ProcessingStateResource;
use App\Filament\Support\PanelActor;
use App\Filament\Support\ResultNotifications;
use App\Infrastructure\Persistence\Models\ProcessingState;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Retry through RequeueArticleProcessingAction, the same path the article page uses: same job
 * class, same constructor arguments as ArticleService, so the worker on the GCP VM sees nothing new.
 */
final class RetryProcessingActions
{
    public static function retry(): Action
    {
        return Action::make('retry')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->visible(static fn (ProcessingState $record): bool => $record->entity_type === ProcessingEntityType::Article
                && PanelActor::may('operations.manage'))
            ->schema([
                Checkbox::make('force')
                    ->label('Retry even if the run is still reporting progress'),
            ])
            ->modalDescription('Kanji, words and JLPT levels are extracted again from the article\'s current content.')
            ->action(static function (ProcessingState $record, array $data): void {
                $result = app(RequeueArticleProcessingAction::class)->execute(
                    new EntityId($record->entity_id),
                    PanelActor::current(),
                    (bool) ($data['force'] ?? false),
                );

                if (ResultNotifications::send($result, 'Processing was queued again.')) {
                    $record->refresh();
                }
            });
    }

    public static function bulkRetry(): BulkAction
    {
        return BulkAction::make('bulkRetry')
            ->label('Retry')
            ->icon(Heroicon::OutlinedArrowPath)
            ->requiresConfirmation()
            ->modalDescription('Runs still reporting progress are skipped. At most '.ProcessingStateResource::RETRY_LIMIT.' can be retried at once.')
            ->visible(static fn (): bool => PanelActor::may('operations.manage'))
            ->deselectRecordsAfterCompletion()
            ->action(static function (Collection $records): void {
                if ($records->count() > ProcessingStateResource::RETRY_LIMIT) {
                    Notification::make()
                        ->danger()
                        ->title('Select at most '.ProcessingStateResource::RETRY_LIMIT.' runs to retry; '.$records->count().' were selected.')
                        ->send();

                    return;
                }

                $failures = [];

                foreach ($records as $record) {
                    /** @var ProcessingState $record */
                    if ($record->entity_type !== ProcessingEntityType::Article) {
                        $failures[] = $record->entity_id.': only article processing can be retried';

                        continue;
                    }

                    $result = app(RequeueArticleProcessingAction::class)->execute(
                        new EntityId($record->entity_id),
                        PanelActor::current(),
                    );

                    if ($result->isFailure()) {
                        $failures[] = ($record->getAttribute('article_title') ?? $record->entity_id).': '.ResultNotifications::message($result);
                    }
                }

                ResultNotifications::bulk($records->count(), $failures, 'queued');
            });
    }
}
