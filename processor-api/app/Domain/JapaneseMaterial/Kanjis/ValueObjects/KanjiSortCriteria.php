<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Kanjis\ValueObjects;

use App\Domain\Shared\Enums\KanjiSortField;
use App\Domain\Shared\Enums\SortDirection;

// TODO: has some query serializations issue when used on kanji database field.
final readonly class KanjiSortCriteria
{
    public function __construct(
        public KanjiSortField $field,
        public SortDirection $direction = SortDirection::DESC
    ) {}

    public static function byStrokeCountAsc(): self
    {
        return new self(KanjiSortField::STROKE_COUNT, SortDirection::ASC);
    }

    public static function byStrokeCountDesc(): self
    {
        return new self(KanjiSortField::STROKE_COUNT, SortDirection::DESC);
    }

    public static function byFrequencyAsc(): self
    {
        return new self(KanjiSortField::FREQUENCY, SortDirection::ASC);
    }

    public static function byFrequencyDesc(): self
    {
        return new self(KanjiSortField::FREQUENCY, SortDirection::DESC);
    }

    public static function byGradeAsc(): self
    {
        return new self(KanjiSortField::GRADE, SortDirection::ASC);
    }

    public static function byGradeDesc(): self
    {
        return new self(KanjiSortField::GRADE, SortDirection::DESC);
    }

    public static function byJlptAsc(): self
    {
        return new self(KanjiSortField::JLPT, SortDirection::ASC);
    }

    public static function byJlptDesc(): self
    {
        return new self(KanjiSortField::JLPT, SortDirection::DESC);
    }
}
