<?php

namespace App\Domain\Engagement\Models;

use App\Domain\Users\Models\LikeUser;

readonly class Like
{
    public function __construct(
        public int $id,
        public int $value,
        public \DateTimeImmutable $created_at,
        public LikeUser $user,
    ) {}

    public function getIdValue(): int
    {
        return $this->id;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getUser(): LikeUser
    {
        return $this->user;
    }
}
