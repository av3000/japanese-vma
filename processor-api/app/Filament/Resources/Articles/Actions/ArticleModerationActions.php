<?php

declare(strict_types=1);

namespace App\Filament\Resources\Articles\Actions;

use App\Application\Articles\Actions\Processing\RequeueArticleProcessingAction;
use App\Application\Articles\Policies\ArticlePolicy;
use App\Application\Articles\Services\ArticleModerationServiceInterface;
use App\Application\Articles\Services\ArticleServiceInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Processing\Enums\ProcessingStatus;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Support\PanelActor;
use App\Infrastructure\Persistence\Models\Article;
use App\Shared\Results\Result;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Article moderation actions. Each one calls the application layer; the panel only decides who
 * sees the button (panel permission plus the application ArticlePolicy).
 */
final class ArticleModerationActions
{
    public static function canModerate(string $permission): bool
    {
        return PanelActor::may($permission, static fn (AuthenticatedUser $actor): bool => app(ArticlePolicy::class)->canModerate($actor));
    }

    public static function setStatus(): Action
    {
        return Action::make('setStatus')
            ->label('Set status')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->visible(static fn (): bool => self::canModerate('articles.update'))
            ->fillForm(static fn (Article $record): array => ['status' => $record->status->value])
            ->schema(static fn (Article $record): array => self::statusSchema([$record]))
            ->action(static function (Article $record, array $data): void {
                $result = self::changeStatus($record, $data);

                if ($result->isFailure()) {
                    self::notifyFailure($result);

                    return;
                }

                Notification::make()->success()->title('Status set to '.ArticleStatus::from((int) $data['status'])->label().'.')->send();
            });
    }

    public static function bulkSetStatus(): BulkAction
    {
        return BulkAction::make('bulkSetStatus')
            ->label('Set status')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->visible(static fn (): bool => self::canModerate('articles.update'))
            ->schema(static fn (Collection $records): array => self::statusSchema($records->all()))
            ->deselectRecordsAfterCompletion()
            ->action(static function (Collection $records, array $data): void {
                $failures = [];

                foreach ($records as $record) {
                    /** @var Article $record */
                    $result = self::changeStatus($record, $data);

                    if ($result->isFailure()) {
                        $failures[] = $record->title_jp.': '.($result->getError()->errorMessage ?? $result->getError()->description);
                    }
                }

                $succeeded = $records->count() - count($failures);

                Notification::make()
                    ->title("{$succeeded} changed, ".count($failures).' failed')
                    ->body($failures === [] ? null : implode("\n", array_slice($failures, 0, 5)))
                    ->status($failures === [] ? 'success' : 'warning')
                    ->send();
            });
    }

    public static function togglePublicity(): Action
    {
        return Action::make('togglePublicity')
            ->label(static fn (Article $record): string => $record->publicity === PublicityStatus::PUBLIC ? 'Make private' : 'Make public')
            ->icon(static fn (Article $record): Heroicon => $record->publicity === PublicityStatus::PUBLIC ? Heroicon::OutlinedLockClosed : Heroicon::OutlinedGlobeAlt)
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription('Publicity is separate from the moderation status.')
            ->visible(static fn (): bool => self::canModerate('articles.update'))
            ->action(static function (Article $record): void {
                $target = $record->publicity === PublicityStatus::PUBLIC ? PublicityStatus::PRIVATE : PublicityStatus::PUBLIC;
                $result = app(ArticleModerationServiceInterface::class)->setPublicity(
                    new EntityId((string) $record->uuid),
                    $target,
                    PanelActor::current(),
                );

                if ($result->isFailure()) {
                    self::notifyFailure($result);

                    return;
                }

                $record->refresh();
                Notification::make()->success()->title('The article is now '.mb_strtolower($target->label()).'.')->send();
            });
    }

    public static function reprocess(): Action
    {
        return Action::make('reprocess')
            ->label('Reprocess content')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->visible(static fn (): bool => PanelActor::may('operations.manage'))
            ->schema([
                Checkbox::make('force')
                    ->label('Retry even if a run is still reporting progress'),
            ])
            ->modalDescription('Kanji, words and JLPT levels are extracted again from the current content.')
            ->action(static function (Article $record, array $data): void {
                $result = app(RequeueArticleProcessingAction::class)->execute(
                    new EntityId((string) $record->uuid),
                    PanelActor::current(),
                    (bool) ($data['force'] ?? false),
                );

                if ($result->isFailure()) {
                    self::notifyFailure($result);

                    return;
                }

                Notification::make()->success()->title('Processing was queued again.')->send();
            });
    }

    public static function delete(): Action
    {
        return Action::make('deleteArticle')
            ->label('Delete')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->visible(static fn (): bool => self::canModerate('articles.delete'))
            ->modalHeading('Delete this article?')
            ->modalDescription('The article, its comments, likes, views, downloads, hashtag links and catalogue entries are removed. This cannot be undone.')
            ->schema([
                TextInput::make('confirmation')
                    ->label('Type DELETE to confirm')
                    ->required()
                    ->in(['DELETE'])
                    ->validationMessages(['in' => 'Type DELETE in capitals to confirm.']),
            ])
            ->modalSubmitActionLabel('Delete article')
            ->action(static function (Article $record, Action $action): void {
                $result = app(ArticleServiceInterface::class)->deleteArticle(
                    new EntityId((string) $record->uuid),
                    PanelActor::current(),
                );

                if ($result->isFailure()) {
                    self::notifyFailure($result);

                    return;
                }

                Notification::make()->success()->title('Article deleted.')->send();
                $action->redirect(ArticleResource::getUrl('index'));
            });
    }

    /**
     * @param list<Article> $records
     *
     * @return list<\Filament\Schemas\Components\Component|\Filament\Forms\Components\Field>
     */
    private static function statusSchema(array $records): array
    {
        $unfinished = array_filter($records, static fn (Article $record): bool => self::processingUnfinished($record));

        return [
            Select::make('status')
                ->label('Status')
                ->required()
                ->native(false)
                ->live()
                ->options(self::statusOptions()),
            Textarea::make('reason')
                ->label('Reason (kept in the activity log)')
                ->rows(2)
                ->maxLength(500),
            Checkbox::make('approve_anyway')
                ->label(count($unfinished) === 1 && count($records) === 1
                    ? 'Approve even though processing has not completed'
                    : 'Approve even though processing has not completed for '.count($unfinished).' of these articles')
                ->helperText('Kanji, words and JLPT levels may be missing or wrong until processing completes.')
                ->visible(static fn (Get $get): bool => $unfinished !== [] && (int) $get('status') === ArticleStatus::APPROVED->value)
                ->accepted(),
        ];
    }

    /**
     * @param array{status: int|string, reason?: string|null} $data
     */
    private static function changeStatus(Article $record, array $data): Result
    {
        return app(ArticleModerationServiceInterface::class)->updateStatus(
            new EntityId((string) $record->uuid),
            ArticleStatus::from((int) $data['status']),
            PanelActor::current(),
            $data['reason'] ?? null,
        );
    }

    private static function processingUnfinished(Article $record): bool
    {
        $status = $record->getAttribute('processing_status');

        return $status === null || ProcessingStatus::tryFrom((string) $status) !== ProcessingStatus::COMPLETED;
    }

    /**
     * @return array<int, string>
     */
    private static function statusOptions(): array
    {
        $options = [];

        foreach (ArticleStatus::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }

    private static function notifyFailure(Result $result): void
    {
        Notification::make()
            ->danger()
            ->title($result->getError()->errorMessage ?? $result->getError()->description)
            ->send();
    }
}
