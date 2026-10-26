<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Radicals\DTOs;

final readonly class RadicalDetailIncludes
{
    public const KANJIS = 'kanjis';

    public const ALLOWED = [self::KANJIS];

    public function __construct(
        public bool $kanjis = false,
    ) {
    }

    public static function fromCsv(?string $include): self
    {
        $values = $include === null
            ? []
            : array_values(array_filter(array_map('trim', explode(',', $include))));

        return new self(
            kanjis: in_array(self::KANJIS, $values, true),
        );
    }
}
