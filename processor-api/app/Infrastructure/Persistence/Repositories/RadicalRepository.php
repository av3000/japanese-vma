<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\JapaneseMaterial\Radicals\Interfaces\Repositories\RadicalRepositoryInterface;
use App\Domain\JapaneseMaterial\Radicals\DTOs\RadicalListResultDTO;
use App\Domain\JapaneseMaterial\Radicals\Models\Radical as DomainRadical;
use App\Domain\JapaneseMaterial\Radicals\Queries\RadicalQueryCriteria;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\Radical as PersistenceRadical;
use Illuminate\Database\Eloquent\Builder;

class RadicalRepository implements RadicalRepositoryInterface
{
    public function __construct(
        private readonly RadicalMapper $radicalMapper,
    ) {}

    public function find(RadicalQueryCriteria $criteria): RadicalListResultDTO
    {
        $query = PersistenceRadical::query()->orderBy('id');

        $this->applyFilters($query, $criteria);

        $paginator = $query->paginate(
            $criteria->pagination->per_page,
            ['*'],
            'page',
            $criteria->pagination->page,
        );

        $items = $paginator->getCollection()
            ->map(fn (PersistenceRadical $radical): DomainRadical => $this->radicalMapper->mapToDomain($radical))
            ->all();

        return new RadicalListResultDTO(
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

    public function findByUuid(EntityId $uuid, bool $withKanjis = false): ?DomainRadical
    {
        $query = PersistenceRadical::query()->where('uuid', $uuid->value());

        if ($withKanjis) {
            $query->with('kanjis');
        }

        $radical = $query->first();

        return $radical ? $this->radicalMapper->mapToDomain($radical) : null;
    }

    public function findByLegacyId(int $id, bool $withKanjis = false): ?DomainRadical
    {
        $query = PersistenceRadical::query()->whereKey($id);

        if ($withKanjis) {
            $query->with('kanjis');
        }

        $radical = $query->first();

        return $radical ? $this->radicalMapper->mapToDomain($radical) : null;
    }

    private function applyFilters(Builder $query, RadicalQueryCriteria $criteria): void
    {
        if ($criteria->keyword !== null && $criteria->keyword !== '') {
            $keyword = $criteria->keyword;

            $query->where(function (Builder $query) use ($keyword): void {
                $query->where('radical', 'LIKE', "%{$keyword}%")
                    ->orWhere('meaning', 'LIKE', "%{$keyword}%")
                    ->orWhere('hiragana', 'LIKE', "%{$keyword}%");
            });
        }

        if ($criteria->radical !== null && $criteria->radical !== '') {
            $query->where('radical', 'LIKE', "%{$criteria->radical}%");
        }

        if ($criteria->meaning !== null && $criteria->meaning !== '') {
            $query->where('meaning', 'LIKE', "%{$criteria->meaning}%");
        }

        if ($criteria->hiragana !== null && $criteria->hiragana !== '') {
            $query->where('hiragana', 'LIKE', "%{$criteria->hiragana}%");
        }

        if ($criteria->strokes !== null) {
            $query->where('strokes', $criteria->strokes);
        }
    }
}
