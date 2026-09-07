<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\JapaneseMaterial\Sentences\Models\Sentence as DomainSentence;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\Kanji as PersistenceKanji;
use App\Infrastructure\Persistence\Models\Sentence as PersistenceSentence;
use App\Infrastructure\Persistence\Models\Word as PersistenceWord;

class SentenceMapper
{
    public function __construct(
        private readonly KanjiMapper $kanjiMapper,
        private readonly WordMapper $wordMapper,
    ) {
    }

    public function mapToDomain(PersistenceSentence $persistenceSentence): DomainSentence
    {
        $kanjis = [];

        if ($persistenceSentence->relationLoaded('kanjis')) {
            $kanjis = $persistenceSentence->kanjis
                ->map(fn (PersistenceKanji $kanji) => $this->kanjiMapper->mapToDomain($kanji))
                ->all();
        }

        $words = [];

        if ($persistenceSentence->relationLoaded('words')) {
            $words = $persistenceSentence->words
                ->map(fn (PersistenceWord $word) => $this->wordMapper->mapToDomain($word))
                ->all();
        }

        return new DomainSentence(
            id: (int) $persistenceSentence->id,
            uuid: new EntityId((string) $persistenceSentence->uuid),
            userId: $persistenceSentence->user_id === null ? null : (int) $persistenceSentence->user_id,
            tatoebaEntry: $persistenceSentence->tatoeba_entry,
            content: (string) $persistenceSentence->content,
            kanjis: $kanjis,
            words: $words,
        );
    }
}
