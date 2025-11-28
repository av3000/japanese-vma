<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Users\Interfaces\Repositories\UserRepositoryInterface;
use App\Infrastructure\Persistence\Models\User as PersistenceUser;
use App\Domain\Users\Models\User as DomainUser;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use Illuminate\Support\Facades\Hash;

class UserRepository implements UserRepositoryInterface
{
    /**
     * Find user by UUID with role relationship.
     *
     * @param EntityId $userUuid The user's public UUID
     * @return DomainUser|null The domain user if found, null if not found
     */
    public function findByUuid(EntityId $userUuid): ?DomainUser
    {
        $persistenceUser = PersistenceUser::with('roles')
            ->where('uuid', $userUuid->value())
            ->first();

        return $persistenceUser ? UserMapper::mapToDomain($persistenceUser) : null;
    }

    /**
     * Create a new user
     *
     * @return array{userId: UserId, uuid: EntityId, user: PersistenceUser}
     */
    public function create(
        string $uuid,
        string $name,
        string $email,
        string $hashedPassword
    ): array {
        $user = PersistenceUser::create([
            'uuid' => $uuid,
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
        ]);

        return [
            'userId' => new UserId($user->id),
            'uuid' => new EntityId($user->uuid),
        ];
    }

    /**
     * Generate API access token for user
     * Encapsulates Passport token generation (infrastructure concern)
     */
    public function generateAccessToken(UserId $userId): string
    {
        $user = PersistenceUser::findOrFail($userId->value());

        $tokenResult = $user->createToken('authToken');

        return $tokenResult->accessToken;
    }


    /**
     * Revoke a specific access token
     */
    public function revokeToken(UserId $userId, string $tokenId): void
    {
        $user = PersistenceUser::findOrFail($userId->value());

        $user->tokens()
            ->where('id', $tokenId)
            ->update(['revoked' => true]);
    }

    /**
     * Revoke all access tokens for user
     */
    public function revokeAllTokens(UserId $userId): void
    {
        $user = PersistenceUser::findOrFail($userId->value());

        $user->tokens()->update(['revoked' => true]);
    }

    /**
     * Update an existing user
     */
    public function update(DomainUser $user): void
    {
        $entityUser = PersistenceUser::where('uuid', $user->getUuid()->value())
            ->firstOrFail();

        UserMapper::mapToExistingEntity($user, $entityUser);

        $entityUser->save();
    }

    /**
     * Find user by email
     */
    public function findByEmailForAuth(string $email): ?array
    {
        $persistenceUser = PersistenceUser::with('roles')
            ->where('email', $email)
            ->first();

        if (!$persistenceUser) {
            return null;
        }

        return [
            'user' => UserMapper::mapToDomain($persistenceUser),
            'passwordHash' => $persistenceUser->password,
        ];
    }

    /**
     * Verify password for user
     */
    public function verifyPassword(UserId $userId, string $password): bool
    {
        $user = PersistenceUser::find($userId->value());

        if (!$user) {
            return false;
        }

        return Hash::check($password, $user->password);
    }
}
