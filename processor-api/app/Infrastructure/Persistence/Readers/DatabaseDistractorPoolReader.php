<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Readers;

use App\Application\Study\Interfaces\Readers\DistractorPoolReaderInterface;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Study\Enums\FlashcardField;
use App\Domain\Study\Factories\FlashcardFactory;
use App\Domain\Study\Models\Flashcard;
use App\Domain\Study\ValueObjects\FlashcardQuestion;
use App\Infrastructure\Persistence\Models\Kanji as PersistenceKanji;
use App\Infrastructure\Persistence\Models\Radical as PersistenceRadical;
use App\Infrastructure\Persistence\Models\Word as PersistenceWord;
use App\Infrastructure\Persistence\Repositories\KanjiMapper;
use App\Infrastructure\Persistence\Repositories\RadicalMapper;
use App\Infrastructure\Persistence\Repositories\WordMapper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Two bounded reads at most: one for the preferred level (JLPT for kanji and words, stroke
 * count for radicals), one without the preference for whatever is still missing. Rows are
 * ordered by `md5(id || seed)`, a per-seed pseudo-random order, so two requests with the same
 * seed get the same pool and the deck stays reproducible even when it needs the dictionary.
 * The dictionary tables are read-only reference data, so the full-table sort is acceptable
 * for the pool sizes here.
 */
final class DatabaseDistractorPoolReader implements DistractorPoolReaderInterface
{
    private const EMPTY_MARKER = '-';

    public function __construct(
        private readonly KanjiMapper $kanjiMapper,
        private readonly WordMapper $wordMapper,
        private readonly RadicalMapper $radicalMapper,
        private readonly FlashcardFactory $flashcardFactory,
    ) {
    }

    public function sample(
        SavedListType $baseType,
        FlashcardQuestion $question,
        int $seed,
        array $excludeItemIds,
        ?string $preferJlpt,
        ?int $preferStrokes,
        int $limit,
    ): array {
        if ($limit <= 0) {
            return [];
        }

        $preferred = $this->fetch($baseType, $question, $seed, $excludeItemIds, $preferJlpt, $preferStrokes, $limit);

        if (count($preferred) >= $limit || ($preferJlpt === null && $preferStrokes === null)) {
            return array_slice($preferred, 0, $limit);
        }

        $taken = array_map(static fn (Flashcard $card): int => $card->itemId, $preferred);
        $rest = $this->fetch($baseType, $question, $seed, [...$excludeItemIds, ...$taken], null, null, $limit - count($preferred));

        return [...$preferred, ...$rest];
    }

    /**
     * @param int[] $excludeItemIds
     *
     * @return list<Flashcard>
     */
    private function fetch(
        SavedListType $baseType,
        FlashcardQuestion $question,
        int $seed,
        array $excludeItemIds,
        ?string $jlpt,
        ?int $strokes,
        int $limit,
    ): array {
        $query = match ($baseType) {
            SavedListType::KANJIS => $this->kanjiQuery($question->answer, $jlpt),
            SavedListType::WORDS => $this->wordQuery($question->answer, $jlpt),
            SavedListType::RADICALS => $this->radicalQuery($question->answer, $strokes),
            default => null,
        };

        if ($query === null) {
            return [];
        }

        if ($excludeItemIds !== []) {
            $query->whereNotIn('id', $excludeItemIds);
        }

        // The factory may still reject a row the column filter let through (a `sense` with
        // no gloss, say), so read a little more than asked and cut after mapping.
        $rows = $query
            ->orderByRaw('md5(id::text || ?)', [(string) $seed])
            ->limit($limit * 2)
            ->get();

        $cards = [];

        foreach ($rows as $row) {
            $card = $this->toCard($baseType, $row, $question);

            if ($card !== null) {
                $cards[] = $card;
            }

            if (count($cards) >= $limit) {
                break;
            }
        }

        return $cards;
    }

    private function kanjiQuery(FlashcardField $answer, ?string $jlpt): Builder
    {
        $column = match ($answer) {
            FlashcardField::CHARACTER => 'kanji',
            FlashcardField::MEANING => 'meaning',
            FlashcardField::ONYOMI => 'onyomi',
            FlashcardField::KUNYOMI => 'kunyomi',
            FlashcardField::READING => 'kunyomi',
        };

        $query = PersistenceKanji::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->where($column, '!=', self::EMPTY_MARKER);

        if ($jlpt !== null) {
            $query->where('jlpt', $jlpt);
        }

        return $query;
    }

    private function wordQuery(FlashcardField $answer, ?string $jlpt): Builder
    {
        $query = PersistenceWord::query();

        match ($answer) {
            FlashcardField::READING => $query
                ->whereNotNull('furigana')
                ->where('furigana', '!=', '')
                ->where('furigana', '!=', self::EMPTY_MARKER)
                ->whereColumn('furigana', '!=', 'word'),
            FlashcardField::MEANING => $query
                ->whereNotNull('sense')
                ->where('sense', '!=', '')
                ->where('sense', '!=', '[]'),
            default => $query->whereNotNull('word')->where('word', '!=', ''),
        };

        if ($jlpt !== null && $jlpt !== self::EMPTY_MARKER) {
            $query->where('jlpt', $jlpt);
        }

        return $query;
    }

    private function radicalQuery(FlashcardField $answer, ?int $strokes): Builder
    {
        $column = match ($answer) {
            FlashcardField::CHARACTER => 'radical',
            FlashcardField::MEANING => 'meaning',
            default => 'hiragana',
        };

        $query = PersistenceRadical::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->where($column, '!=', self::EMPTY_MARKER);

        if ($strokes !== null) {
            $query->where('strokes', $strokes);
        }

        return $query;
    }

    private function toCard(SavedListType $baseType, Model $row, FlashcardQuestion $question): ?Flashcard
    {
        return match (true) {
            $row instanceof PersistenceKanji => $this->flashcardFactory->fromKanji($this->kanjiMapper->mapToDomain($row), $question),
            $row instanceof PersistenceWord => $this->flashcardFactory->fromWord($this->wordMapper->mapToDomain($row), $question),
            $row instanceof PersistenceRadical => $this->flashcardFactory->fromRadical($this->radicalMapper->mapToDomain($row), $question),
            default => null,
        };
    }
}
