<?php
namespace App\Domain\Catalogues\ValueObjects;

use InvalidArgumentException;

readonly class CatalogueTitle
{
    private const MIN_LENGTH = 2;
    private const MAX_LENGTH = 255;

    public function __construct(public string $value)
    {
        $this->validate();
    }

    public static function fromInput(string $input): self
    {
        $trimmed = trim($input);

        if (empty($trimmed)) {
            throw new InvalidArgumentException('List title cannot be empty');
        }

        return new self($trimmed);
    }

    private function validate(): void
    {
        if (strlen($this->value) < self::MIN_LENGTH) {
            throw new InvalidArgumentException(
                'List title must be at least ' . self::MIN_LENGTH . ' characters'
            );
        }

        if (strlen($this->value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                'List title cannot exceed ' . self::MAX_LENGTH . ' characters'
            );
        }
    }

    public function equals(CatalogueTitle $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
