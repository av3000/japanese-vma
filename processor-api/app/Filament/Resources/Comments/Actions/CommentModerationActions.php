<?php

declare(strict_types=1);

namespace App\Filament\Resources\Comments\Actions;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Comments\Policies\CommentPolicy;
use App\Application\Comments\Services\CommentServiceInterface;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Filament\Resources\Comments\CommentResource;
use App\Filament\Support\PanelActor;
use App\Filament\Support\ResultNotifications;
use App\Infrastructure\Persistence\Models\Comment;
use App\Shared\Results\Result;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Delete through CommentService (#139): the comment, every reply beneath it and their likes go in
 * one transaction. There is no soft delete, so the activity row keeps a content snapshot.
 */
final class CommentModerationActions
{
    public static function canModerate(): bool
    {
        return PanelActor::may('comments.delete', static fn (AuthenticatedUser $actor): bool => app(CommentPolicy::class)->canModerate($actor));
    }

    public static function delete(): Action
    {
        return Action::make('deleteComment')
            ->label('Delete')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Delete this comment?')
            ->modalDescription(static fn (Comment $record): string => self::consequence((int) ($record->replies_count ?? $record->replies()->count())))
            ->modalSubmitActionLabel('Delete comment')
            ->visible(static fn (): bool => self::canModerate())
            ->action(static function (Comment $record, Action $action, mixed $livewire): void {
                $result = self::deleteComment($record);

                if (ResultNotifications::send($result, 'Comment deleted.') && $livewire instanceof ViewRecord) {
                    $action->redirect(CommentResource::getUrl('index'));
                }
            });
    }

    public static function bulkDelete(): BulkAction
    {
        return BulkAction::make('bulkDeleteComments')
            ->label('Delete')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('Replies to the selected comments are deleted with them. This cannot be undone.')
            ->visible(static fn (): bool => self::canModerate())
            ->deselectRecordsAfterCompletion()
            ->action(static function (Collection $records): void {
                $failures = [];

                // Oldest first, so a selected reply whose parent was selected too is already gone
                // when its turn comes; that counts as deleted, not failed.
                foreach ($records->sortBy('id') as $record) {
                    /** @var Comment $record */
                    $result = self::deleteComment($record);

                    if ($result->isFailure() && $result->getError()->code !== 'Comments.NotFound') {
                        $failures[] = mb_strimwidth((string) $record->content, 0, 40, '…').': '.ResultNotifications::message($result);
                    }
                }

                ResultNotifications::bulk($records->count(), $failures, 'deleted');
            });
    }

    public static function consequence(int $replies): string
    {
        return match ($replies) {
            0 => 'The comment and its likes are removed. This cannot be undone.',
            1 => 'The comment, its reply (and any replies to that) and their likes are removed. This cannot be undone.',
            default => "The comment, its {$replies} replies (and any replies to those) and their likes are removed. This cannot be undone.",
        };
    }

    private static function deleteComment(Comment $record): Result
    {
        return app(CommentServiceInterface::class)->deleteComment(
            new EntityId((string) $record->uuid),
            PanelActor::current(),
        );
    }
}
