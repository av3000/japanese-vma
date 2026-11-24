<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp;

use App\Domain\Shared\Enums\SavedListType;
use App\Mcp\Support\DeckKind;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DeckKindTest extends TestCase
{
    public function test_the_names_are_the_three_kinds_a_client_can_ask_for(): void
    {
        $this->assertSame(['kanji', 'words', 'radicals'], DeckKind::names());
    }

    /**
     * @return array<string, array{DeckKind, SavedListType}>
     */
    public static function kinds(): array
    {
        return [
            'kanji' => [DeckKind::KANJI, SavedListType::KANJIS],
            'words' => [DeckKind::WORDS, SavedListType::WORDS],
            'radicals' => [DeckKind::RADICALS, SavedListType::RADICALS],
        ];
    }

    #[DataProvider('kinds')]
    public function test_a_kind_maps_to_its_list_type_and_back(DeckKind $kind, SavedListType $type): void
    {
        $this->assertSame($type, $kind->toListType());
        $this->assertSame($kind, DeckKind::fromListType($type));
    }

    /**
     * @return array<string, array{SavedListType}>
     */
    public static function listTypesWithoutAKind(): array
    {
        return [
            'known radicals' => [SavedListType::KNOWNRADICALS],
            'known kanji' => [SavedListType::KNOWNKANJIS],
            'known words' => [SavedListType::KNOWNWORDS],
            'known sentences' => [SavedListType::KNOWNSENTENCES],
            'sentences' => [SavedListType::SENTENCES],
            'articles' => [SavedListType::ARTICLES],
            'lyrics' => [SavedListType::LYRICS],
            'artists' => [SavedListType::ARTISTS],
        ];
    }

    #[DataProvider('listTypesWithoutAKind')]
    public function test_a_list_type_that_cannot_be_drilled_has_no_kind(SavedListType $type): void
    {
        $this->assertNull(DeckKind::fromListType($type));
    }
}
