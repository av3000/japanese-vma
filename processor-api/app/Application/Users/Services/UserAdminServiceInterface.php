<?php

declare(strict_types=1);

namespace App\Application\Users\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Users\DTOs\UserAccountWriteDTO;
use App\Shared\Results\Result;

/**
 * Account administration. Panel-only: there is no API surface for these use cases, so the
 * caller's panel permission is the authorisation and this service records what was done.
 */
interface UserAdminServiceInterface
{
    /**
     * @return Result Success payload: UserAccountDTO
     */
    public function createUser(UserAccountWriteDTO $account, AuthenticatedUser $actor): Result;

    /**
     * Changing the email address clears its verification.
     *
     * @return Result Success payload: UserAccountDTO
     */
    public function updateUser(EntityId $uuid, UserAccountWriteDTO $account, AuthenticatedUser $actor): Result;

    /**
     * Idempotent: an already verified address keeps its original timestamp and records nothing.
     *
     * @return Result Success payload: UserAccountDTO
     */
    public function markEmailVerified(EntityId $uuid, AuthenticatedUser $actor): Result;

    /**
     * Signs the user out of every API session.
     *
     * @return Result Success payload: null
     */
    public function revokeAllTokens(EntityId $uuid, AuthenticatedUser $actor): Result;
}
