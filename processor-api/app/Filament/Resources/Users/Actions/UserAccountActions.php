<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Actions;

use App\Application\Users\Services\RoleServiceInterface;
use App\Application\Users\Services\UserAdminServiceInterface;
use App\Domain\Shared\Enums\UserRole;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Users\Models\Role as DomainRole;
use App\Filament\Support\PanelActor;
use App\Infrastructure\Persistence\Models\User;
use App\Shared\Results\Result;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;

/**
 * Support actions on one account. Each goes through an application service, so the RoleService
 * guards (common is never removed, admin is never removed from an admin) apply in the panel too.
 */
final class UserAccountActions
{
    /**
     * @return list<Action>
     */
    public static function all(): array
    {
        return [
            self::assignRole(),
            self::removeRole(),
            self::markEmailVerified(),
            self::revokeTokens(),
        ];
    }

    public static function assignRole(): Action
    {
        return Action::make('assignRole')
            ->label('Assign role')
            ->icon(Heroicon::OutlinedUserPlus)
            ->visible(static fn (User $record): bool => Gate::allows('assignRoles', $record))
            ->schema([
                Select::make('role')
                    ->label('Role')
                    ->required()
                    ->native(false)
                    ->options(static fn (User $record): array => self::assignableRoles($record)),
            ])
            ->action(static function (User $record, array $data): void {
                $role = (string) $data['role'];

                self::notify(
                    app(RoleServiceInterface::class)->assignRole(new EntityId((string) $record->uuid), $role, PanelActor::current()),
                    "Assigned the {$role} role.",
                );
                self::refreshRoles($record);
            });
    }

    public static function removeRole(): Action
    {
        return Action::make('removeRole')
            ->label('Remove role')
            ->icon(Heroicon::OutlinedUserMinus)
            ->color('gray')
            ->visible(static fn (User $record): bool => Gate::allows('assignRoles', $record))
            ->schema([
                Select::make('role')
                    ->label('Role')
                    ->required()
                    ->native(false)
                    ->options(static fn (User $record): array => $record->roles->pluck('name', 'name')->all()),
            ])
            ->action(static function (User $record, array $data): void {
                $role = (string) $data['role'];

                self::notify(
                    app(RoleServiceInterface::class)->removeRole(new EntityId((string) $record->uuid), $role, PanelActor::current()),
                    "Removed the {$role} role.",
                );
                self::refreshRoles($record);
            });
    }

    public static function markEmailVerified(): Action
    {
        return Action::make('markEmailVerified')
            ->label('Mark email verified')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription('Use this only after confirming the person controls this address.')
            ->visible(static fn (User $record): bool => $record->email_verified_at === null && Gate::allows('update', $record))
            ->action(static function (User $record): void {
                self::notify(
                    app(UserAdminServiceInterface::class)->markEmailVerified(new EntityId((string) $record->uuid), PanelActor::current()),
                    'Email address marked as verified.',
                );
                $record->refresh();
            });
    }

    public static function revokeTokens(): Action
    {
        return Action::make('revokeTokens')
            ->label('Sign out everywhere')
            ->icon(Heroicon::OutlinedArrowRightStartOnRectangle)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Sign this user out everywhere?')
            ->modalDescription('Every API token is revoked, so the user is signed out on every device and must sign in again.')
            ->modalSubmitActionLabel('Sign out everywhere')
            ->visible(static fn (User $record): bool => Gate::allows('update', $record))
            ->action(static function (User $record): void {
                self::notify(
                    app(UserAdminServiceInterface::class)->revokeAllTokens(new EntityId((string) $record->uuid), PanelActor::current()),
                    'All sessions were revoked.',
                );
            });
    }

    /**
     * Roles the user does not hold yet. `admin` is offered only to administrators, so holding
     * `roles.assign` is not enough to promote anyone, yourself included, to superuser. This is
     * only the UX: RoleService::assignRole() enforces the same rule for every caller.
     *
     * @return array<string, string>
     */
    private static function assignableRoles(User $record): array
    {
        $held = $record->roles->pluck('name')->all();
        $mayGrantAdmin = Gate::allows('assignAdminRole', User::class);
        $options = [];

        foreach (app(RoleServiceInterface::class)->getAllRoles() as $role) {
            /** @var DomainRole $role */
            $name = $role->getName();

            if (in_array($name, $held, true) || ($name === UserRole::ADMIN->value && ! $mayGrantAdmin)) {
                continue;
            }

            $options[$name] = $name;
        }

        return $options;
    }

    private static function refreshRoles(User $record): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $record->unsetRelation('roles')->load('roles');
    }

    private static function notify(Result $result, string $successTitle): void
    {
        if ($result->isFailure()) {
            Notification::make()
                ->danger()
                ->title($result->getError()->errorMessage ?? $result->getError()->description)
                ->send();

            return;
        }

        Notification::make()->success()->title($successTitle)->send();
    }
}
