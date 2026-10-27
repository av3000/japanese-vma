<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\SavedListType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SavedListTypeTest extends TestCase
{
    /**
     * @return array<string, array{SavedListType, ObjectTemplateType|null}>
     */
    public static function itemTypes(): array
    {
        return [
            'known radicals' => [SavedListType::KNOWNRADICALS, ObjectTemplateType::RADICAL],
            'known kanji' => [SavedListType::KNOWNKANJIS, ObjectTemplateType::KANJI],
            'known words' => [SavedListType::KNOWNWORDS, ObjectTemplateType::WORD],
            'known sentences' => [SavedListType::KNOWNSENTENCES, ObjectTemplateType::SENTENCE],
            'radicals' => [SavedListType::RADICALS, ObjectTemplateType::RADICAL],
            'kanji' => [SavedListType::KANJIS, ObjectTemplateType::KANJI],
            'words' => [SavedListType::WORDS, ObjectTemplateType::WORD],
            'sentences' => [SavedListType::SENTENCES, ObjectTemplateType::SENTENCE],
            'articles' => [SavedListType::ARTICLES, ObjectTemplateType::ARTICLE],
            'lyrics' => [SavedListType::LYRICS, null],
            'artists' => [SavedListType::ARTISTS, null],
        ];
    }

    #[DataProvider('itemTypes')]
    public function test_each_catalogue_type_names_what_it_holds(SavedListType $type, ?ObjectTemplateType $expected): void
    {
        self::assertSame($expected, $type->itemType());
    }

    public function test_every_case_is_covered(): void
    {
        self::assertCount(count(SavedListType::cases()), self::itemTypes());
    }
}
