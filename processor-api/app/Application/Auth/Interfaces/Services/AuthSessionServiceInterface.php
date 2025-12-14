<?php

declare(strict_types=1);

namespace App\Application\Auth\Interfaces\Services;

use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Users\Models\User as DomainUser;

interface AuthSessionServiceInterface
{
    /**
     * Check if current request is authenticated
     */
    public function isAuthenticated(): bool;

    /**
     * Get the authenticated user as a DomainUser.
     * This method should map the PersistenceUser and internally cache the DomainUser instance.
     */
    public function getAuthenticatedDomainUser(): ?DomainUser;

    /**
     * Get authenticated user's ID
     */
    public function getUserId(): ?UserId;

    /**
     * Get authenticated user's UUID
     */
    public function getUserUuid(): ?string;

    /**
     * Get current access token ID
     */
    public function getTokenId(): ?string;

    /**
     * Clear session (logout)
     */
    public function clear(): void;
}
