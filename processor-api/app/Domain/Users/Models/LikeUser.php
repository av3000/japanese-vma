<?php

declare(strict_types=1);

namespace App\Domain\Users\Models;

use App\Domain\Shared\ValueObjects\{EntityId, UserId, UserName};

class LikeUser
{
    public function __construct(
        private UserId $id,
        private EntityId $uuid,
        private UserName $name,
    ) {}

    public function getIdValue(): int
    {
        return $this->id->value();
    }
    public function getUuid(): EntityId
    {
        return $this->uuid;
    }
    public function getName(): UserName
    {
        return $this->name;
    }
}
