<?php
namespace App\Domain\Articles\DTOs;

class JlptLevelsDTO
{
    public function __construct(
        public readonly int $n1,
        public readonly int $n2,
        public readonly int $n3,
        public readonly int $n4,
        public readonly int $n5,
        public readonly int $uncommon
    ) {}

    public static function fromModel($model): self
    {
        return new self(
            n1: $model->n1,
            n2: $model->n2,
            n3: $model->n3,
            n4: $model->n4,
            n5: $model->n5,
            uncommon: $model->uncommon
        );
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
