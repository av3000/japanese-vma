<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Kanjis\Models;

use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiCharacter;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiGrade;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\JlptLevel;

final readonly class Kanji
{
    public function __construct(
        private EntityId $uuid,
        private KanjiCharacter $character,
        private array $onyomi,
        private array $kunyomi,
        private array $meanings,
        private array $nanori,
        private ?KanjiGrade $grade,
        private int $strokeCount,
        private ?JlptLevel $jlpt,
        private ?int $frequency,
        private array $radicals,
        private array $radicalParts,
    ) {}

    public function getUuid(): EntityId
    {
        return $this->uuid;
    }

    public function getCharacter(): KanjiCharacter
    {
        return $this->character;
    }

    public function getOnyomi(): array
    {
        return $this->onyomi;
    }

    public function getKunyomi(): array
    {
        return $this->kunyomi;
    }

    public function getMeanings(): array
    {
        return $this->meanings;
    }

    public function getNanori(): array
    {
        return $this->nanori;
    }

    public function getGrade(): ?KanjiGrade
    {
        return $this->grade;
    }

    public function getStrokeCount(): int
    {
        return $this->strokeCount;
    }

    public function getJlpt(): ?JlptLevel
    {
        return $this->jlpt;
    }

    public function getFrequency(): ?int
    {
        return $this->frequency;
    }

    public function getRadicals(): array
    {
        return $this->radicals;
    }

    public function getRadicalParts(): array
    {
        return $this->radicalParts;
    }
}
