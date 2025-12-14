<?php

namespace App\Application\Users\Interfaces\Repositories;

use App\Domain\Users\Models\User as DomainUser;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Users\Models\Users;
use App\Domain\Users\Queries\UserQueryCriteria;

interface UserRepositoryInterface
{
    /**
     * Find user ID by user UUID
     */
    public function getIdByUuid(EntityId $entityUuid): ?UserId;

    /**
     * Find user by UUID with role relationship.
     *
     * @param EntityId $userUuid The user's public UUID
     * @return DomainUser|null The domain user if found, null if not found
     */
    public function findByUuid(EntityId $userUuid): ?DomainUser;

    /**
     * Finds users based on the given criteria.
     *
     * @param UserQueryCriteria|null $criteria Optional criteria for filtering.
     * @return Users
     */
    public function find(?UserQueryCriteria $criteria = null): Users;

    /**
     * Create a new user
     *
     * @return array{userId: UserId, uuid: EntityId, user: \App\Infrastructure\Persistence\Models\User}
     */
    public function create(
        string $uuid,
        string $name,
        string $email,
        string $hashedPassword
    ): array;

    /**
     * Generate API access token for user
     *
     * @param UserId $userId
     * @return string Access token
     */
    public function generateAccessToken(UserId $userId): string;

    /**
     * Revoke a specific access token
     */
    public function revokeToken(UserId $userId, string $tokenId): void;

    /**
     * Revoke all access tokens for user
     */
    public function revokeAllTokens(UserId $userId): void;

    /**
     * Update an existing user
     */
    public function update(DomainUser $user): void;

    /**
     * Find user by email for authentication
     * Returns domain user with password hash for verification
     *
     * @return array{user: DomainUser, passwordHash: string}|null
     */
    public function findByEmailForAuth(string $email): ?array;

    /**
     * Verify password for user
     */
    public function verifyPassword(UserId $userId, string $password): bool;
}
