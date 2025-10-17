<?php

namespace App\Domain\Shared\ValueObjects;

readonly class Viewer
{
    public function __construct(
        private ?int $userId,
        private string $ipAddress
    ) {}

    public static function fromRequest(): self
    {
        return new self(auth()->id(), request()->ip());
    }

    public function isAuthenticated(): bool
    {
        return $this->userId !== null;
    }

    public function userId(): ?int
    {
        return $this->userId;
    }

    public function ipAddress(): string
    {
        return $this->ipAddress;
    }
}
