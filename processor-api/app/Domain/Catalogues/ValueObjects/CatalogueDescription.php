<?php
namespace App\Domain\Catalogues\ValueObjects;

use InvalidArgumentException;

readonly class CatalogueDescription
{
    private const MAX_LENGTH = 500;

    public function __construct(public ?string $value)
    {
        $this->validate();
    }

    public static function fromInput(?string $input): self
    {
        if ($input === null || $input === '') {
            return new self(null);
        }

        $trimmed = trim($input);

        if (empty($trimmed)) {
            return new self(null);
        }

        return new self($trimmed);
    }

    public static function empty(): self
    {
        return new self(null);
    }

    private function validate(): void
    {
        if ($this->value !== null && strlen($this->value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                'Catalogue description cannot exceed ' . self::MAX_LENGTH . ' characters'
            );
        }
    }

    public function isEmpty(): bool
    {
        return $this->value === null;
    }

    public function equals(CatalogueDescription $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value ?? '';
    }
}
