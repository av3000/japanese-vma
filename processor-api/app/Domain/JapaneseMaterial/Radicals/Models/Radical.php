<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Radicals\Models;

use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji as DomainKanji;
use App\Domain\Shared\ValueObjects\EntityId;

final readonly class Radical
{
    /**
     * @param array<int, DomainKanji> $kanjis
     */
    public function __construct(
        private int $id,
        private EntityId $uuid,
        private ?string $radical,
        private ?int $strokes,
        private ?string $meaning,
        private ?string $hiragana,
        private array $kanjis = [],
    ) {}

    public function getIdValue(): int
    {
        return $this->id;
    }

    public function getUuid(): EntityId
    {
        return $this->uuid;
    }

    public function getRadical(): ?string
    {
        return $this->radical;
    }

    public function getStrokes(): ?int
    {
        return $this->strokes;
    }

    public function getMeaning(): ?string
    {
        return $this->meaning;
    }

    public function getHiragana(): ?string
    {
        return $this->hiragana;
    }

    /**
     * @return array<int, DomainKanji>
     */
    public function getKanjis(): array
    {
        return $this->kanjis;
    }
}
