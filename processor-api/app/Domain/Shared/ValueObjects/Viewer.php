<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

readonly class Viewer
{
    public function __construct(
        private ?UserId $userId,
        private string $ipAddress,
    ) {
    }

    public function isAuthenticated(): bool
    {
        return $this->userId !== null;
    }

    public function userId(): ?UserId
    {
        return $this->userId;
    }

    public function ipAddress(): string
    {
        return $this->ipAddress;
    }
}
