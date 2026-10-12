<?php

declare(strict_types=1);

namespace App\Application\Users\Interfaces\Repositories;

use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Users\DTOs\UserAccountDTO;
use App\Domain\Users\DTOs\UserAccountWriteDTO;
use DateTimeImmutable;

/**
 * Account writes made by administrators. Kept apart from UserRepositoryInterface, which serves
 * registration and authentication.
 */
interface UserAccountRepositoryInterface
{
    public function find(EntityId $uuid): ?UserAccountDTO;

    public function emailBelongsToAnotherAccount(string $email, ?EntityId $except = null): bool;

    public function create(UserAccountWriteDTO $account): UserAccountDTO;

    public function update(EntityId $uuid, UserAccountWriteDTO $account, bool $clearEmailVerification): UserAccountDTO;

    public function markEmailVerified(EntityId $uuid, DateTimeImmutable $verifiedAt): UserAccountDTO;
}
