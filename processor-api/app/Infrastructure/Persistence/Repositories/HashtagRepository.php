<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Engagement\Interfaces\Repositories\HashtagRepositoryInterface;
use App\Domain\Engagement\DTOs\HashtagDuplicateGroupDTO;
use App\Domain\Engagement\DTOs\HashtagFilterDTO;
use App\Domain\Engagement\DTOs\HashtagMergeResultDTO;
use App\Domain\Engagement\DTOs\HashtagTagDTO;
use App\Domain\Engagement\ValueObjects\HashtagName;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Infrastructure\Persistence\Models\HashtagEntity;
use App\Infrastructure\Persistence\Models\ObjectTemplate;
use App\Infrastructure\Persistence\Models\Uniquehashtag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class HashtagRepository implements HashtagRepositoryInterface
{
    /**
     * Create a new hashtag association for an entity.
     *
     * First, creates or retrieves the unique hashtag by content.
     * Then, creates the association between the hashtag and the entity.
     *
     * @param array{entity_id: int, entity_type_id: int, content: string, user_id: int} $data
     *
     * @throws \Illuminate\Database\QueryException On database constraint violation or connection failure
     */
    public function create(array $data): void
    {
        // Reuse a tag that differs only in case or character width, so `#JLPT` and `＃jlpt` do
        // not become two tags. HashtagName enforces the empty and length rules on this path too.
        $name = HashtagName::from($data['content']);
        $uniquehashtag = Uniquehashtag::query()
            ->where('normalized_content', $name->key)
            ->orderBy('id')
            ->first()
            ?? Uniquehashtag::create(['content' => $name->display, 'normalized_content' => $name->key]);

        // Create the association
        HashtagEntity::create([
            'hashtag_id' => $uniquehashtag->id,
            'entity_type_id' => $this->resolveTemplateIdFromLegacyId($data['entity_type_id']),
            'entity_id' => $data['entity_id'],
            'user_id' => $data['user_id'],
        ]);
    }

    /**
     * Find all hashtags for a specific entity using a filter DTO.
     *
     * Eager loads the uniquehashtag relationship to avoid N+1 queries.
     * Returns only the unique hashtag data, not the association records.
     *
     * @param HashtagFilterDTO $filter Filter criteria (entity_id, entity_type)
     *
     * @throws \Illuminate\Database\QueryException On database failure
     *
     * @return array<object> Array of hashtag objects with properties: id, content, created_at, updated_at
     */
    public function findAllByFilter(HashtagFilterDTO $filter): array
    {
        return HashtagEntity::with('uniquehashtag')
            ->where('entity_type_id', $this->resolveTemplateId($filter->entityType))
            ->where('entity_id', $filter->entityId)
            ->orderBy('id')
            ->get()
            ->map(fn ($link) => $link->uniquehashtag)
            ->toArray();
    }

    /**
     * Delete all hashtag associations for a specific entity.
     *
     * Note: Does not delete the unique hashtag records themselves,
     * only the associations. Unique hashtags may be reused by other entities.
     *
     * @param int $entityId The entity ID
     * @param int $entityTypeId The entity type ID (legacy integer ID)
     *
     * @throws \Illuminate\Database\QueryException On database failure
     */
    public function deleteByEntity(int $entityId, int $entityTypeId): void
    {
        HashtagEntity::where('entity_id', $entityId)
            ->where('entity_type_id', $this->resolveTemplateIdFromLegacyId($entityTypeId))
            ->delete();
    }

    /**
     * Build base query for hashtag filtering.
     *
     * @param HashtagFilterDTO $filter Filter criteria
     *
     * @return Builder Eloquent query builder
     */
    private function buildBaseQuery(HashtagFilterDTO $filter): Builder
    {
        return HashtagEntity::where('entity_type_id', $this->resolveTemplateId($filter->entityType))
            ->where('entity_id', $filter->entityId);
    }

    /**
     * Find all hashtags for multiple entities (batch loading for N+1 optimization).
     *
     * Useful when loading hashtags for a paginated list of articles/lists/posts.
     * Returns hashtags grouped by entity_id for easy attachment.
     *
     * @param array<int> $entityIds Array of entity IDs to load hashtags for
     * @param ObjectTemplateType $entityType The type of entities
     *
     * @throws \Illuminate\Database\QueryException On database failure
     *
     * @return array<int, array<object>> Associative array keyed by entity_id
     *
     * @example [123 => [{id: 1, content: '#php'}, {id: 2, content: '#laravel'}], 456 => [{id: 3, content: '#vue'}]]
     */
    public function findAllByEntityIds(array $entityIds, ObjectTemplateType $entityType): array
    {
        return HashtagEntity::with('uniquehashtag')
            ->where('entity_type_id', $this->resolveTemplateId($entityType))
            ->whereIn('entity_id', $entityIds)
            ->orderBy('id')
            ->get()
            ->groupBy('entity_id')
            ->map(fn ($links) => $links->map(fn ($link) => $link->uniquehashtag))
            ->toArray();
    }

    public function findTag(int $hashtagId): ?HashtagTagDTO
    {
        $tag = Uniquehashtag::query()->find($hashtagId);

        return $tag === null ? null : $this->toTagDTO($tag);
    }

    public function findTagByKey(string $key, ?int $exceptId = null): ?HashtagTagDTO
    {
        $tag = Uniquehashtag::query()
            ->where('normalized_content', $key)
            ->when($exceptId !== null, static fn (Builder $query) => $query->where('id', '!=', $exceptId))
            ->orderBy('id')
            ->first();

        return $tag === null ? null : $this->toTagDTO($tag);
    }

    public function rename(int $hashtagId, HashtagName $name): HashtagTagDTO
    {
        $tag = Uniquehashtag::query()->findOrFail($hashtagId);
        $tag->content = $name->display;
        $tag->normalized_content = $name->key;
        $tag->save();

        return $this->toTagDTO($tag);
    }

    public function merge(int $sourceId, int $targetId): HashtagMergeResultDTO
    {
        // Lock both tags in id order so two opposite merges cannot deadlock.
        $locked = Uniquehashtag::query()
            ->whereIn('id', [$sourceId, $targetId])
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $source = $locked[$sourceId] ?? throw (new ModelNotFoundException())->setModel(Uniquehashtag::class, [$sourceId]);
        $target = $locked[$targetId] ?? throw (new ModelNotFoundException())->setModel(Uniquehashtag::class, [$targetId]);

        $targetKeys = HashtagEntity::query()
            ->where('hashtag_id', $targetId)
            ->get(['entity_type_id', 'entity_id'])
            ->map(static fn (HashtagEntity $link): string => $link->entity_type_id.':'.$link->entity_id)
            ->flip();

        $sourceLinks = HashtagEntity::withTrashed()
            ->where('hashtag_id', $sourceId)
            ->orderBy('id')
            ->get(['id', 'entity_type_id', 'entity_id', 'deleted_at']);

        $retired = $sourceLinks
            ->filter(static fn (HashtagEntity $link): bool => $link->deleted_at === null
                && $targetKeys->has($link->entity_type_id.':'.$link->entity_id))
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();

        if ($retired !== []) {
            HashtagEntity::query()->whereIn('id', $retired)->delete();
        }

        HashtagEntity::withTrashed()->where('hashtag_id', $sourceId)->update(['hashtag_id' => $targetId]);

        $sourceDTO = $this->toTagDTO($source);
        $source->delete();

        return new HashtagMergeResultDTO(
            source: $sourceDTO,
            target: $this->toTagDTO($target),
            movedLinkIds: $sourceLinks->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all(),
            retiredLinkIds: $retired,
        );
    }

    private function toTagDTO(Uniquehashtag $tag): HashtagTagDTO
    {
        return new HashtagTagDTO(
            id: (int) $tag->id,
            content: (string) $tag->content,
            key: (string) $tag->normalized_content,
        );
    }

    /**
     * @return list<HashtagDuplicateGroupDTO>
     */
    public function findDuplicateGroups(): array
    {
        $tags = Uniquehashtag::query()
            ->whereIn('normalized_content', Uniquehashtag::duplicateKeys())
            ->orderBy('normalized_content')
            ->orderBy('id')
            ->get(['id', 'content', 'normalized_content'])
            ->groupBy('normalized_content');

        return $tags
            ->map(static fn ($group, $key): HashtagDuplicateGroupDTO => new HashtagDuplicateGroupDTO(
                key: (string) $key,
                tags: $group->map(static fn (Uniquehashtag $tag): array => ['id' => (int) $tag->id, 'content' => (string) $tag->content])->values()->all(),
            ))
            ->values()
            ->all();
    }

    private function resolveTemplateId(ObjectTemplateType $entityType): int
    {
        return ObjectTemplate::query()
            ->where('entity_type_uuid', $entityType->value)
            ->value('id')
            ?? $entityType->getLegacyId();
    }

    private function resolveTemplateIdFromLegacyId(int $legacyId): int
    {
        $entityType = ObjectTemplateType::tryFromLegacyValue($legacyId);

        if ($entityType === null) {
            return $legacyId;
        }

        return $this->resolveTemplateId($entityType);
    }
}
