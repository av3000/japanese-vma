<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\JapaneseMaterial\Radicals\Models\Radical as DomainRadical;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiCharacter;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\Kanji as PersistenceKanji;
use App\Infrastructure\Persistence\Models\Radical as PersistenceRadical;

class RadicalMapper
{
    public function __construct(
        private readonly KanjiMapper $kanjiMapper,
    ) {}

    public function mapToDomain(PersistenceRadical $persistenceRadical): DomainRadical
    {
        $kanjis = [];

        if ($persistenceRadical->relationLoaded('kanjis')) {
            $kanjis = $persistenceRadical->kanjis
                ->filter(fn (PersistenceKanji $kanji) => KanjiCharacter::isValid($kanji->kanji))
                ->map(fn (PersistenceKanji $kanji) => $this->kanjiMapper->mapToDomain($kanji))
                ->all();
        }

        return new DomainRadical(
            id: (int) $persistenceRadical->id,
            uuid: new EntityId((string) $persistenceRadical->uuid),
            radical: $persistenceRadical->radical,
            strokes: $persistenceRadical->strokes === null ? null : (int) $persistenceRadical->strokes,
            meaning: $persistenceRadical->meaning,
            hiragana: $persistenceRadical->hiragana,
            kanjis: $kanjis,
        );
    }
}
