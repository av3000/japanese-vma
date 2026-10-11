<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogues\Actions;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Catalogues\Policies\CatalogueModerationPolicy;
use App\Application\Catalogues\Services\CatalogueModerationServiceInterface;
use App\Domain\Catalogues\Models\Catalogue as DomainCatalogue;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Filament\Resources\Catalogues\CatalogueResource;
use App\Filament\Support\PanelActor;
use App\Filament\Support\ResultNotifications;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Repositories\CatalogueMapper;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

/**
 * Publicity and removal through CatalogueModerationService. Whether the button shows is decided by
 * the same CatalogueModerationPolicy the service applies, so Known lists never offer a delete.
 */
final class CatalogueModerationActions
{
    public static function togglePublicity(): Action
    {
        return Action::make('togglePublicity')
            ->label(static fn (Catalogue $record): string => $record->publicity ? 'Make private' : 'Make public')
            ->icon(static fn (Catalogue $record): Heroicon => $record->publicity ? Heroicon::OutlinedLockClosed : Heroicon::OutlinedGlobeAlt)
            ->color('gray')
            ->requiresConfirmation()
            ->visible(static fn (): bool => PanelActor::may(
                'catalogues.update',
                static fn (AuthenticatedUser $actor): bool => app(CatalogueModerationPolicy::class)->canModerate($actor),
            ))
            ->action(static function (Catalogue $record): void {
                $target = $record->publicity ? PublicityStatus::PRIVATE : PublicityStatus::PUBLIC;
                $result = app(CatalogueModerationServiceInterface::class)->setPublicity(
                    new EntityId((string) $record->uuid),
                    $target,
                    PanelActor::current(),
                );

                if (ResultNotifications::send($result, 'The catalogue is now '.mb_strtolower($target->label()).'.')) {
                    $record->refresh();
                }
            });
    }

    public static function delete(): Action
    {
        return Action::make('deleteCatalogue')
            ->label('Delete')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Delete this catalogue?')
            ->modalDescription('The catalogue, its items, comments, likes, views and hashtag links are removed. The items themselves stay. This cannot be undone.')
            ->modalSubmitActionLabel('Delete catalogue')
            ->visible(static fn (Catalogue $record): bool => PanelActor::may(
                'catalogues.delete',
                static fn (AuthenticatedUser $actor): bool => app(CatalogueModerationPolicy::class)->canRemove($actor, self::domain($record)),
            ))
            ->action(static function (Catalogue $record, Action $action): void {
                $result = app(CatalogueModerationServiceInterface::class)->remove(
                    new EntityId((string) $record->uuid),
                    PanelActor::current(),
                );

                if (ResultNotifications::send($result, 'Catalogue deleted.')) {
                    $action->redirect(CatalogueResource::getUrl('index'));
                }
            });
    }

    private static function domain(Catalogue $record): DomainCatalogue
    {
        return app(CatalogueMapper::class)->mapToDomain($record->loadMissing('user'));
    }
}
