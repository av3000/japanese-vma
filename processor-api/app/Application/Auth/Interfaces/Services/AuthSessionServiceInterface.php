<?php

declare(strict_types=1);

namespace App\Application\Auth\Interfaces\Services;

use App\Domain\Shared\ValueObjects\UserId;

interface AuthSessionServiceInterface
{
    /**
     * Check if current request is authenticated
     */
    public function isAuthenticated(): bool;

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
