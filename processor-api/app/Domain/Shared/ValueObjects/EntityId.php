<?php
namespace App\Domain\Shared\ValueObjects;

use Illuminate\Support\Str;

readonly class EntityId
{
    public function __construct(
        private string $value
    ) {
        if (empty($this->value)) {
            throw new \InvalidArgumentException('Entity ID cannot be empty');
        }
    }

    public static function generate(): self
    {
        return new self(\Str::uuid()->toString());
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(EntityId $other): bool
    {
        return $this->value === $other->value;
    }
}
