<?php

namespace App\Domain\Articles\DTOs;

readonly class AuthorDTO
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}

    public static function fromModel($user): self
    {
        return new self($user->id, $user->name);
    }

    public function toArray(): array
    {
        return ['id' => $this->id, 'name' => $this->name];
    }
}
