<?php

declare(strict_types=1);

namespace App\Filament\Resources\Processing\Actions;

use App\Application\Articles\Actions\Processing\RequeueArticleProcessingAction;
use App\Application\Articles\Actions\Processing\RequeueArticleProcessingBatchAction;
use App\Application\Users\Support\PanelPermission;
use App\Domain\Processing\DTOs\ProcessingRetryBatchDTO;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Filament\Support\PanelActor;
use App\Filament\Support\ResultNotifications;
use App\Infrastructure\Persistence\Models\ProcessingState;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Checkbox;
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
            ->visible(static fn (ProcessingState $record): bool => $record->entity_type->isRetryable()
                && PanelActor::may(PanelPermission::OperationsManage))
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
            ->modalDescription('Runs still reporting progress are skipped. At most '.RequeueArticleProcessingBatchAction::MAX_BATCH.' can be retried at once.')
            ->visible(static fn (): bool => PanelActor::may(PanelPermission::OperationsManage))
            ->deselectRecordsAfterCompletion()
            ->action(static function (Collection $records): void {
                $result = app(RequeueArticleProcessingBatchAction::class)->execute(
                    $records->map(static fn (ProcessingState $record): array => [
                        'entityType' => $record->entity_type,
                        'entityId' => $record->entity_id,
                        'label' => (string) ($record->getAttribute('article_title') ?? $record->entity_id),
                    ])->values()->all(),
                    PanelActor::current(),
                );

                if ($result->isFailure()) {
                    ResultNotifications::failure($result);

                    return;
                }

                /** @var ProcessingRetryBatchDTO $batch */
                $batch = $result->getData();
                ResultNotifications::bulk($records->count(), $batch->failures, 'queued', $batch->skipped);
            });
    }
}
