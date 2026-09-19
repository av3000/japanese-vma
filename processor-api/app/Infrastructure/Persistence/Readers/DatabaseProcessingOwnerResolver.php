<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Readers;

use App\Application\Processing\Interfaces\Readers\ProcessingOwnerResolverInterface;
use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Shared\ValueObjects\EntityId;
use Illuminate\Support\Facades\DB;

final class DatabaseProcessingOwnerResolver implements ProcessingOwnerResolverInterface
{
    public function ownerUuid(ProcessingEntityType $entityType, EntityId $entityId): ?string
    {
        return match ($entityType) {
            ProcessingEntityType::Article => $this->articleOwnerUuid($entityId),
        };
    }

    private function articleOwnerUuid(EntityId $articleUuid): ?string
    {
        $uuid = DB::table('articles')
            ->join('users', 'users.id', '=', 'articles.user_id')
            ->where('articles.uuid', $articleUuid->value())
            ->value('users.uuid');

        return $uuid === null ? null : (string) $uuid;
    }
}
