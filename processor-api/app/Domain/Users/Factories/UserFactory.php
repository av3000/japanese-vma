<?php

declare(strict_types=1);

namespace App\Domain\Users\Factories;

use App\Domain\Users\Models\User;
use App\Domain\Users\DTOs\RegisterUserDTO;
use App\Domain\Shared\ValueObjects\{EntityId};
use Illuminate\Support\Facades\Hash;

class UserFactory
{
    /**
     * Create domain user from registration DTO
     * Returns data ready for repository creation
     *
     * @return array{uuid: string, name: string, email: string, hashedPassword: string}
     */
    public static function createFromDTO(RegisterUserDTO $dto): array
    {
        return [
            'uuid' => EntityId::generate()->value(),
            'name' => $dto->name,
            'email' => $dto->email,
            'hashedPassword' => Hash::make($dto->password),
        ];
    }
}
