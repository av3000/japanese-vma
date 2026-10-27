<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Sentences\Models;

use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji as DomainKanji;
use App\Domain\JapaneseMaterial\Sentences\ValueObjects\SentenceAuthor;
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
        private ?SentenceAuthor $author,
        private array $kanjis = [],
        private array $words = [],
    ) {
    }

    /**
     * The writer of a user-written sentence. Required, so `null` always means an imported
     * Tatoeba sentence and never an author the caller forgot to pass.
     */
    public function getAuthor(): ?SentenceAuthor
    {
        return $this->author;
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
