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
}
