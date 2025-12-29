<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Users\Interfaces\Repositories\UserRepositoryInterface;
use App\Infrastructure\Persistence\Models\User as PersistenceUser;
use App\Domain\Users\Models\User as DomainUser;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Users\Queries\UserQueryCriteria;
use App\Domain\Shared\ValueObjects\UserSortCriteria;
use App\Domain\Users\Models\Users;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly UserMapper $userMapper
    ) {}

    /**
     * Find user ID by user UUID
     */
    public function getIdByUuid(EntityId $entityUuid): ?UserId
    {
        $userId = PersistenceUser::where('uuid', $entityUuid->value())->value('id');
        return UserId::from($userId);
    }

    /**
     * Find user by UUID
     *
     * @param EntityId $userUuid The user's public UUID
     * @return DomainUser|null The domain user if found, null if not found
     */
    public function findByUuid(EntityId $userUuid): ?DomainUser
    {
        $persistenceUser = PersistenceUser::with('roles')
            ->where('uuid', $userUuid->value())
            ->first();

        return $persistenceUser ? $this->userMapper->mapToDomain($persistenceUser) : null;
    }

    /**
     * Finds users based on the given criteria.
     *
     * @param UserQueryCriteria|null $criteria Optional criteria for filtering.
     * @return Users
     */
    public function find(?UserQueryCriteria $criteria = null): Users
    {
        $query = PersistenceUser::query()->with('roles');

        if ($criteria) {
            $this->applyFilters($query, $criteria);
            $this->applySorting($query, $criteria->sort);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = $criteria?->pagination?->per_page ?? Pagination::DEFAULT_PER_PAGE;
        $page = $criteria?->pagination?->page ?? Pagination::MIN_PAGE;


        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginatedResults */
        $paginatedResults = $query->paginate($perPage, ['*'], 'page', $page);

        $domainUsers = $paginatedResults->getCollection()
            ->map(fn(PersistenceUser $persistenceUser) => $this->userMapper->mapToDomain($persistenceUser));

        $paginatedResults->setCollection($domainUsers);

        return Users::fromEloquentPaginator($paginatedResults);
    }

    // TODO: Consider UserQueryBuilder class pattern
    /**
     * Apply filters from UserQueryCriteria to the Eloquent query builder.
     *
     * @param Builder $query
     * @param UserQueryCriteria $criteria
     * @return void
     */
    private function applyFilters(Builder $query, UserQueryCriteria $criteria): void
    {
        if ($criteria->uuid !== null) {
            $query->where('uuid', $criteria->uuid->value());
        }

        if ($criteria->email !== null) {
            $query->where('email', $criteria->email->value());
        }

        if ($criteria->name !== null) {
            $query->where('name', 'LIKE', '%' . $criteria->name->value() . '%');
        }

        if ($criteria->role !== null) {
            $query->whereHas('roles', fn($q) => $q->where('name', $criteria->role));
        }

        if ($criteria->includeInactive) {
            // This assumes a column like 'is_active' or a global scope for active users.
            // Example: $query->where('is_active', false);
            // Or if using soft deletes for "inactive": $query->onlyTrashed();
        }

        if ($criteria->publicOnly) {
            // TODO: apply if needed later
            // $query->where('is_private', true);
        }
    }

    /**
     * Apply sorting from UserSortCriteria to the Eloquent query builder.
     *
     * @param Builder $query
     * @param UserSortCriteria|null $sort
     * @return void
     */
    private function applySorting(Builder $query, ?UserSortCriteria $sort): void
    {
        if ($sort !== null) {
            $query->orderBy($sort->field->value, $sort->direction->value);
        }
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

        $this->userMapper->mapToExistingEntity($user, $entityUser);

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
            'user' => $this->userMapper->mapToDomain($persistenceUser),
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
