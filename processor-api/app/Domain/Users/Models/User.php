<?php

declare(strict_types=1);

namespace App\Domain\Users\Models;

use App\Domain\Shared\ValueObjects\{EntityId, UserId, Email, UserName};

class User
{
    public function __construct(
        private UserId $id,
        private EntityId $uuid,
        private UserName $name,
        private Email $email,
        private string $roleName,
        private \DateTimeImmutable $createdAt,
    ) {}

    public function getId(): UserId { return $this->id; }
    public function getUuid(): EntityId { return $this->uuid; }
    public function getName(): UserName { return $this->name; }
    public function getEmail(): Email { return $this->email; }
    public function getRoleName(): string { return $this->roleName; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
