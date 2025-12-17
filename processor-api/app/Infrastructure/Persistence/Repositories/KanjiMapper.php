<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Infrastructure\Persistence\Models\Kanji as PersistenceKanji;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji as DomainKanji;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiCharacter;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiGrade;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\JlptLevel;

class KanjiMapper
{
    public function mapToDomain(PersistenceKanji $persistenceKanji): DomainKanji
    {
        $uuid = new EntityId($persistenceKanji->uuid);

        $onyomi = $this->parseStringToArray($persistenceKanji->onyomi);
        $kunyomi = $this->parseStringToArray($persistenceKanji->kunyomi);
        $meanings = $this->parseStringToArray($persistenceKanji->meaning);
        $nanori = $this->parseStringToArray($persistenceKanji->nanori);
        $radicals = $this->parseStringToArray($persistenceKanji->radicals);
        $radicalParts = $this->parseStringToArray($persistenceKanji->radical_parts);

        $grade = ($persistenceKanji->grade !== KanjiGrade::UNASSIGNED)
            ? new KanjiGrade($persistenceKanji->grade)
            : null;
        $jlpt = ($persistenceKanji->jlpt !== JlptLevel::UNASSIGNED)
            ? new JlptLevel($persistenceKanji->jlpt)
            : null;

        $frequency = ($persistenceKanji->frequency === '-')
            ? null
            : (int) $persistenceKanji->frequency;

        return new DomainKanji(
            uuid: $uuid,
            character: new KanjiCharacter($persistenceKanji->kanji),
            onyomi: $onyomi,
            kunyomi: $kunyomi,
            meanings: $meanings,
            nanori: $nanori,
            grade: $grade,
            strokeCount: $persistenceKanji->stroke_count,
            jlpt: $jlpt,
            frequency: $frequency,
            radicals: $radicals,
            radicalParts: $radicalParts,
        );
    }

    private function parseStringToArray(?string $input): array
    {
        if (empty($input)) {
            return [];
        }
        // Split by comma, pipe or semicolon, trim whitespace, and filter empty values
        return array_values(array_filter(array_map('trim', preg_split('/[;|,]/', $input))));
    }
}
