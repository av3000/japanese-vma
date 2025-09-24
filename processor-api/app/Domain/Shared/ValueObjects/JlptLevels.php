<?php
namespace App\Domain\Shared\ValueObjects;

readonly class JlptLevels
{
    public function __construct(
        public int $n1,
        public int $n2,
        public int $n3,
        public int $n4,
        public int $n5,
        public int $uncommon
    ) {
        foreach (['n1', 'n2', 'n3', 'n4', 'n5', 'uncommon'] as $level) {
            if ($this->$level < 0) {
                throw new \InvalidArgumentException("$level must be non-negative");
            }
        }
    }

    public function toArray(): array
    {
        return [
            'n1' => $this->n1,
            'n2' => $this->n2,
            'n3' => $this->n3,
            'n4' => $this->n4,
            'n5' => $this->n5,
            'uncommon' => $this->uncommon,
        ];
    }
}
