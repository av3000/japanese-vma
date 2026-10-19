<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Users\Interfaces\Repositories\UserAccountRepositoryInterface;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Users\DTOs\UserAccountDTO;
use App\Domain\Users\DTOs\UserAccountWriteDTO;
use App\Infrastructure\Persistence\Models\User;
use DateTimeImmutable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class UserAccountRepository implements UserAccountRepositoryInterface
{
    public function find(EntityId $uuid): ?UserAccountDTO
    {
        $user = User::query()->where('uuid', $uuid->value())->first();

        return $user === null ? null : $this->toDTO($user);
    }

    public function emailBelongsToAnotherAccount(string $email, ?EntityId $except = null): bool
    {
        return User::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->when($except !== null, static fn ($query) => $query->where('uuid', '!=', $except?->value()))
            ->exists();
    }

    public function create(UserAccountWriteDTO $account): UserAccountDTO
    {
        $user = User::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => $account->name,
            'email' => $account->email,
            'password' => Hash::make((string) $account->password),
        ]);

        return $this->toDTO($user->refresh());
    }

    public function update(EntityId $uuid, UserAccountWriteDTO $account, bool $clearEmailVerification): UserAccountDTO
    {
        $user = User::query()->where('uuid', $uuid->value())->firstOrFail();
        $user->name = $account->name;
        $user->email = $account->email;

        if ($account->password !== null) {
            $user->password = Hash::make($account->password);
        }

        if ($clearEmailVerification) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $this->toDTO($user);
    }

    public function markEmailVerified(EntityId $uuid, DateTimeImmutable $verifiedAt): UserAccountDTO
    {
        $user = User::query()->where('uuid', $uuid->value())->firstOrFail();
        $user->email_verified_at = $verifiedAt;
        $user->save();

        return $this->toDTO($user);
    }

    private function toDTO(User $user): UserAccountDTO
    {
        return new UserAccountDTO(
            id: new UserId((int) $user->id),
            uuid: new EntityId((string) $user->uuid),
            name: (string) $user->name,
            email: (string) $user->email,
            emailVerifiedAt: $user->email_verified_at?->toDateTimeImmutable(),
        );
    }
}
