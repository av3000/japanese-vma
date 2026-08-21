<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\JapaneseMaterial\Sentences\Interfaces\Repositories\SentenceRepositoryInterface;
use App\Domain\JapaneseMaterial\Sentences\DTOs\SentenceListResultDTO;
use App\Domain\JapaneseMaterial\Sentences\Models\Sentence as DomainSentence;
use App\Domain\JapaneseMaterial\Sentences\Queries\SentenceQueryCriteria;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\Sentence as PersistenceSentence;
use Illuminate\Database\Eloquent\Builder;

class SentenceRepository implements SentenceRepositoryInterface
{
    public function __construct(
        private readonly SentenceMapper $sentenceMapper,
    ) {
    }

    public function find(SentenceQueryCriteria $criteria): SentenceListResultDTO
    {
        $query = PersistenceSentence::query()->orderBy('id');

        $this->applyFilters($query, $criteria);

        $paginator = $query->paginate(
            $criteria->pagination->per_page,
            ['*'],
            'page',
            $criteria->pagination->page,
        );

        $items = $paginator->getCollection()
            ->map(fn (PersistenceSentence $sentence): DomainSentence => $this->sentenceMapper->mapToDomain($sentence))
            ->all();

        return new SentenceListResultDTO(
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

    public function findByUuid(EntityId $uuid, bool $withKanjis = false): ?DomainSentence
    {
        $query = PersistenceSentence::query()->where('uuid', $uuid->value());

        if ($withKanjis) {
            $query->with('kanjis');
        }

        $sentence = $query->first();

        return $sentence ? $this->sentenceMapper->mapToDomain($sentence) : null;
    }

    public function findByLegacyId(int $id, bool $withKanjis = false): ?DomainSentence
    {
        $query = PersistenceSentence::query()->whereKey($id);

        if ($withKanjis) {
            $query->with('kanjis');
        }

        $sentence = $query->first();

        return $sentence ? $this->sentenceMapper->mapToDomain($sentence) : null;
    }

    private function applyFilters(Builder $query, SentenceQueryCriteria $criteria): void
    {
        if ($criteria->keyword !== null && $criteria->keyword !== '') {
            $keyword = $criteria->keyword;

            $query->where(function (Builder $query) use ($keyword): void {
                $query->where('content', 'LIKE', "%{$keyword}%")
                    ->orWhere('tatoeba_entry', 'LIKE', "%{$keyword}%");
            });
        }

        if ($criteria->content !== null && $criteria->content !== '') {
            $query->where('content', 'LIKE', "%{$criteria->content}%");
        }

        if ($criteria->tatoebaEntry !== null && $criteria->tatoebaEntry !== '') {
            $query->where('tatoeba_entry', 'LIKE', "%{$criteria->tatoebaEntry}%");
        }

        if ($criteria->userId !== null) {
            $query->where('user_id', $criteria->userId);
        }

        if ($criteria->kanjiId !== null) {
            $query->whereHas('kanjis', function (Builder $kanjiQuery) use ($criteria): void {
                $kanjiQuery->whereKey($criteria->kanjiId);
            });
        }
    }
}
