<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Catalogues\Interfaces\Repositories\CatalogueRepositoryInterface;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Domain\Shared\Enums\CatalogueType;
use App\Domain\Shared\ValueObjects\UserId;
use Illuminate\Support\Str;

final class CatalogueRepository implements CatalogueRepositoryInterface
{
    public function createDefaultCatalogueForUser(UserId $userId): void
    {
        foreach (CatalogueType::cases() as $listType) {
            $this->create(
                userId: $userId,
                type: $listType,
                title: $listType->title(),
                description: $listType->description(),
                publicity: false
            );
        }
    }

    public function create(
        UserId $userId,
        CatalogueType $type,
        string $title,
        string $description,
        bool $publicity = false
    ): void {
        Catalogue::create([
            'user_id' => $userId->value(),
            'uuid' => Str::uuid()->toString(),
            'type' => $type->value,
            'title' => $title,
            'description' => $description,
            'publicity' => $publicity,
        ]);
    }
}
