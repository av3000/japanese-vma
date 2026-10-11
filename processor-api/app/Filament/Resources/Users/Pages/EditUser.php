<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Application\Users\Services\UserAdminServiceInterface;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Users\DTOs\UserAccountWriteDTO;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Support\PanelActor;
use App\Infrastructure\Persistence\Models\User;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        $result = app(UserAdminServiceInterface::class)->updateUser(
            new EntityId((string) $record->uuid),
            new UserAccountWriteDTO(
                name: $data['name'],
                email: $data['email'],
                password: filled($data['password'] ?? null) ? $data['password'] : null,
            ),
            PanelActor::current(),
        );

        if ($result->isFailure()) {
            throw ValidationException::withMessages([
                'data.email' => $result->getError()->errorMessage ?? $result->getError()->description,
            ]);
        }

        return $record->refresh();
    }
}
