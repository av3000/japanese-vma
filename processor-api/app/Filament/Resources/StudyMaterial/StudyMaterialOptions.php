<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudyMaterial;

use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\JlptLevel;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiGrade;
use App\Infrastructure\Persistence\Repositories\WordMapper;

/**
 * Filter vocabularies shared by the study material resources, taken from the same value objects
 * the public API validates against.
 */
final class StudyMaterialOptions
{
    public const ATTRIBUTION = 'Kanji and word data: EDRDG (KANJIDIC2, JMdict), CC BY-SA 4.0. Sentences: Tatoeba, CC BY 2.0 FR.';

    /**
     * @return array<string, string>
     */
    public static function jlpt(): array
    {
        $options = [];

        foreach (JlptLevel::VALID_LEVELS as $level) {
            $options[$level] = $level === JlptLevel::UNASSIGNED ? 'Not in the JLPT lists' : "N{$level}";
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public static function grades(): array
    {
        $options = [];

        foreach (KanjiGrade::VALID_GRADES as $grade) {
            $options[$grade] = $grade === KanjiGrade::UNASSIGNED ? 'Ungraded' : "Grade {$grade}";
        }

        return $options;
    }

    public static function jlptLabel(?string $level): string
    {
        return self::jlpt()[(string) $level] ?? (string) $level;
    }

    /**
     * A word's English glosses from its JMdict `sense` JSON, the way the public API reads them.
     */
    public static function meanings(?string $sense): string
    {
        return implode('; ', app(WordMapper::class)->extractMeanings($sense));
    }
}
