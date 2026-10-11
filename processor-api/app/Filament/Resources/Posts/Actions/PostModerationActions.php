<?php

declare(strict_types=1);

namespace App\Filament\Resources\Posts\Actions;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Community\Posts\Policies\PostPolicy;
use App\Application\Community\Posts\Services\PostWriteServiceInterface;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Support\PanelActor;
use App\Filament\Support\ResultNotifications;
use App\Infrastructure\Persistence\Models\Post;
use App\Shared\Results\Result;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Lock, unlock and delete through PostWriteService (#137). The panel never flips the `locked`
 * column itself.
 */
final class PostModerationActions
{
    public static function canModerate(): bool
    {
        return PanelActor::may('posts.moderate', static fn (AuthenticatedUser $actor): bool => app(PostPolicy::class)->canLock($actor));
    }

    public static function toggleLock(): Action
    {
        return Action::make('toggleLock')
            ->label(static fn (Post $record): string => $record->locked ? 'Unlock' : 'Lock')
            ->icon(static fn (Post $record): Heroicon => $record->locked ? Heroicon::OutlinedLockOpen : Heroicon::OutlinedLockClosed)
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription(PostResource::LOCKED_MEANING)
            ->visible(static fn (): bool => self::canModerate())
            ->action(static function (Post $record): void {
                $locking = ! $record->locked;
                $result = self::setLocked($record, $locking);

                if (ResultNotifications::send($result, $locking ? 'Post locked.' : 'Post unlocked.')) {
                    $record->refresh();
                }
            });
    }

    public static function bulkLock(): BulkAction
    {
        return self::bulkLocking('bulkLock', 'Lock', true, Heroicon::OutlinedLockClosed);
    }

    public static function bulkUnlock(): BulkAction
    {
        return self::bulkLocking('bulkUnlock', 'Unlock', false, Heroicon::OutlinedLockOpen);
    }

    public static function delete(): Action
    {
        return Action::make('deletePost')
            ->label('Delete')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Delete this post?')
            ->modalDescription('The post and its comments, likes, views and hashtag links are removed. This cannot be undone.')
            ->modalSubmitActionLabel('Delete post')
            ->visible(static fn (): bool => self::canModerate())
            ->action(static function (Post $record, Action $action): void {
                $result = app(PostWriteServiceInterface::class)->delete(
                    new EntityId((string) $record->uuid),
                    PanelActor::current(),
                );

                if (ResultNotifications::send($result, 'Post deleted.')) {
                    $action->redirect(PostResource::getUrl('index'));
                }
            });
    }

    private static function bulkLocking(string $name, string $label, bool $locked, Heroicon $icon): BulkAction
    {
        return BulkAction::make($name)
            ->label($label)
            ->icon($icon)
            ->requiresConfirmation()
            ->modalDescription(PostResource::LOCKED_MEANING)
            ->visible(static fn (): bool => self::canModerate())
            ->deselectRecordsAfterCompletion()
            ->action(static function (Collection $records) use ($locked): void {
                $failures = [];

                foreach ($records as $record) {
                    /** @var Post $record */
                    $result = self::setLocked($record, $locked);

                    if ($result->isFailure()) {
                        $failures[] = $record->title.': '.ResultNotifications::message($result);
                    }
                }

                ResultNotifications::bulk($records->count(), $failures, $locked ? 'locked' : 'unlocked');
            });
    }

    private static function setLocked(Post $record, bool $locked): Result
    {
        return app(PostWriteServiceInterface::class)->setLocked(
            new EntityId((string) $record->uuid),
            $locked,
            PanelActor::current(),
        );
    }
}
