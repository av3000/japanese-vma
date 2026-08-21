<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Kanjis\Models;

use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\JlptLevel;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiCharacter;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiGrade;
use App\Domain\Shared\ValueObjects\EntityId;

final readonly class Kanji
{
    /**
     * @param array<int, string> $onyomi
     * @param array<int, string> $kunyomi
     * @param array<int, string> $meanings
     * @param array<int, string> $nanori
     * @param array<int, string> $radicals
     * @param array<int, string> $radicalParts
     */
    public function __construct(
        private int $id,
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
    ) {
    }

    public function getUuid(): EntityId
    {
        return $this->uuid;
    }

    public function getIdValue(): int
    {
        return $this->id;
    }

    public function getCharacter(): KanjiCharacter
    {
        return $this->character;
    }

    /**
     * @return array<int, string>
     */
    public function getOnyomi(): array
    {
        return $this->onyomi;
    }

    /**
     * @return array<int, string>
     */
    public function getKunyomi(): array
    {
        return $this->kunyomi;
    }

    /**
     * @return array<int, string>
     */
    public function getMeanings(): array
    {
        return $this->meanings;
    }

    /**
     * @return array<int, string>
     */
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

    /**
     * @return array<int, string>
     */
    public function getRadicals(): array
    {
        return $this->radicals;
    }

    /**
     * @return array<int, string>
     */
    public function getRadicalParts(): array
    {
        return $this->radicalParts;
    }
}
