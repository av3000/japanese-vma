<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\CustomLists\Interfaces\Repositories\CustomListRepositoryInterface;
use App\Infrastructure\Persistence\Models\CustomList;
use App\Domain\Shared\Enums\CustomListType;
use App\Domain\Shared\ValueObjects\UserId;
use Illuminate\Support\Str;

final class CustomListRepository implements CustomListRepositoryInterface
{
    public function createDefaultListsForUser(UserId $userId): void
    {
        foreach (CustomListType::cases() as $listType) {
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
        CustomListType $type,
        string $title,
        string $description,
        bool $publicity = false
    ): void {
        CustomList::create([
            'user_id' => $userId->value(),
            'uuid' => Str::uuid()->toString(),
            'type' => $type->value,
            'title' => $title,
            'description' => $description,
            'publicity' => $publicity,
        ]);
    }
}
