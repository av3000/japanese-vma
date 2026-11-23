<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Application\Users\Services\UserAdminServiceInterface;
use App\Domain\Users\DTOs\UserAccountDTO;
use App\Domain\Users\DTOs\UserAccountWriteDTO;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Support\PanelActor;
use App\Infrastructure\Persistence\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $result = app(UserAdminServiceInterface::class)->createUser(
            new UserAccountWriteDTO(
                name: $data['name'],
                email: $data['email'],
                password: $data['password'],
            ),
            PanelActor::current(),
        );

        if ($result->isFailure()) {
            throw ValidationException::withMessages([
                'data.email' => $result->getError()->errorMessage ?? $result->getError()->description,
            ]);
        }

        /** @var UserAccountDTO $account */
        $account = $result->getData();

        return User::query()->findOrFail($account->id->value());
    }
}
