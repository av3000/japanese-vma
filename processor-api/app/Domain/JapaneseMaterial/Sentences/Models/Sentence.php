<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Sentences\Models;

use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji as DomainKanji;
use App\Domain\JapaneseMaterial\Words\Models\Word as DomainWord;
use App\Domain\Shared\ValueObjects\EntityId;

final readonly class Sentence
{
    /**
     * @param array<int, DomainKanji> $kanjis
     * @param array<int, DomainWord> $words
     */
    public function __construct(
        private int $id,
        private EntityId $uuid,
        private ?int $userId,
        private ?string $tatoebaEntry,
        private string $content,
        private array $kanjis = [],
        private array $words = [],
        private ?string $authorName = null,
        private ?EntityId $authorUuid = null,
    ) {
    }

    /**
     * The writer of a user-written sentence. Imported Tatoeba sentences have none.
     */
    public function getAuthorName(): ?string
    {
        return $this->authorName;
    }

    public function getAuthorUuid(): ?EntityId
    {
        return $this->authorUuid;
    }

    public function getIdValue(): int
    {
        return $this->id;
    }

    public function getUuid(): EntityId
    {
        return $this->uuid;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getTatoebaEntry(): ?string
    {
        return $this->tatoebaEntry;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return array<int, DomainKanji>
     */
    public function getKanjis(): array
    {
        return $this->kanjis;
    }

    /**
     * @return array<int, DomainWord>
     */
    public function getWords(): array
    {
        return $this->words;
    }
}
