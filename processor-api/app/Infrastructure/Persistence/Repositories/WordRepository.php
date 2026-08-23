<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\JapaneseMaterial\Words\Interfaces\Repositories\WordRepositoryInterface;
use App\Domain\JapaneseMaterial\Words\DTOs\WordListResultDTO;
use App\Domain\JapaneseMaterial\Words\Models\Word as DomainWord;
use App\Domain\JapaneseMaterial\Words\Queries\WordQueryCriteria;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\Word as PersistenceWord;
use Illuminate\Database\Eloquent\Builder;

class WordRepository implements WordRepositoryInterface
{
    public function __construct(
        private readonly WordMapper $wordMapper,
        private readonly KanjiMapper $kanjiMapper,
    ) {
    }

    public function find(WordQueryCriteria $criteria): WordListResultDTO
    {
        $query = PersistenceWord::query()->orderBy('id');

        $this->applyFilters($query, $criteria);

        $paginator = $query->paginate(
            $criteria->pagination->per_page,
            ['*'],
            'page',
            $criteria->pagination->page,
        );

        $items = $paginator->getCollection()
            ->map(fn (PersistenceWord $word): DomainWord => $this->wordMapper->mapToDomain($word))
            ->all();

        return new WordListResultDTO(
            items: $items,
            pagination: [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
        );
    }

    public function findByUuid(EntityId $uuid): ?DomainWord
    {
        $word = PersistenceWord::query()
            ->where('uuid', $uuid->value())
            ->first();

        return $word ? $this->wordMapper->mapToDomain($word) : null;
    }

    public function findBySurface(string $surface): ?DomainWord
    {
        $word = PersistenceWord::query()
            ->where('word', $surface)
            ->orderBy('id')
            ->first();

        return $word ? $this->wordMapper->mapToDomain($word) : null;
    }

    public function hasWordStartingWith(string $prefix): bool
    {
        return PersistenceWord::query()
            ->where('word', 'like', $prefix.'%')
            ->exists();
    }

    public function findIdByWord(string $word): ?int
    {
        $id = PersistenceWord::query()
            ->where('word', $word)
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    public function findRelatedKanjis(int $wordId, int $limit): array
    {
        $word = PersistenceWord::query()->find($wordId);

        if ($word === null) {
            return [];
        }

        return $word->kanjis()
            ->orderBy('japanese_kanji_bank_long.id')
            ->limit($limit)
            ->get()
            ->map(fn ($kanji) => $this->kanjiMapper->mapToDomain($kanji))
            ->all();
    }

    private function applyFilters(Builder $query, WordQueryCriteria $criteria): void
    {
        if ($criteria->keyword !== null && $criteria->keyword !== '') {
            $keyword = $criteria->keyword;

            $query->where(function (Builder $query) use ($keyword): void {
                $query->where('word', 'LIKE', "%{$keyword}%")
                    ->orWhere('furigana', 'LIKE', "%{$keyword}%")
                    ->orWhere('sense', 'LIKE', "%{$keyword}%");
            });
        }

        if ($criteria->word !== null && $criteria->word !== '') {
            $query->where('word', 'LIKE', "%{$criteria->word}%");
        }

        if ($criteria->furigana !== null && $criteria->furigana !== '') {
            $query->where('furigana', 'LIKE', "%{$criteria->furigana}%");
        }

        if ($criteria->jlpt !== null && $criteria->jlpt !== '') {
            $query->where('jlpt', $criteria->jlpt);
        }

        if ($criteria->kanjiId !== null) {
            $query->whereHas('kanjis', function (Builder $kanjiQuery) use ($criteria): void {
                $kanjiQuery->whereKey($criteria->kanjiId);
            });
        }
    }
}
