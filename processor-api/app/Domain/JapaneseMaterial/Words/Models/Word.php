<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Words\Models;

use App\Domain\Shared\ValueObjects\EntityId;

final readonly class Word
{
    /**
     * @param array<int, string> $wordTypes
     * @param array<int, string> $writingElements
     * @param array<int, string> $readingElements
     * @param array<int, string> $meanings
     */
    public function __construct(
        private int $id,
        private EntityId $uuid,
        private string $surface,
        private string $furigana,
        private ?string $jlpt,
        private array $wordTypes,
        private array $writingElements,
        private array $readingElements,
        private array $meanings,
        private ?string $rawWordType,
        private ?string $rawWritingElements,
        private ?string $rawReadingElements,
        private ?string $rawSense,
    ) {
    }

    public function getIdValue(): int
    {
        return $this->id;
    }

    public function getUuid(): EntityId
    {
        return $this->uuid;
    }

    public function getSurface(): string
    {
        return $this->surface;
    }

    public function getFurigana(): string
    {
        return $this->furigana;
    }

    public function getJlpt(): ?string
    {
        return $this->jlpt;
    }

    /**
     * @return array<int, string>
     */
    public function getWordTypes(): array
    {
        return $this->wordTypes;
    }

    /**
     * @return array<int, string>
     */
    public function getWritingElements(): array
    {
        return $this->writingElements;
    }

    /**
     * @return array<int, string>
     */
    public function getReadingElements(): array
    {
        return $this->readingElements;
    }

    /**
     * @return array<int, string>
     */
    public function getMeanings(): array
    {
        return $this->meanings;
    }

    public function getRawWordType(): ?string
    {
        return $this->rawWordType;
    }

    public function getRawWritingElements(): ?string
    {
        return $this->rawWritingElements;
    }

    public function getRawReadingElements(): ?string
    {
        return $this->rawReadingElements;
    }

    public function getRawSense(): ?string
    {
        return $this->rawSense;
    }
}
