<?php

declare(strict_types=1);

namespace App\Application\Catalogues\Interfaces\Repositories;

use App\Domain\Catalogues\DTOs\CatalogueCriteriaDTO;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Catalogues\Models\Catalogues;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;

interface CatalogueRepositoryInterface
{
    /**
     * Create default learning lists for a user
     */
    public function createDefaultCataloguesForUser(UserId $userId): void;

    public function create(Catalogue $catalogue): Catalogue;

    public function update(Catalogue $catalogue): void;

    public function deleteById(int $id): bool;

    /**
     * Get integer ID from catalogue UUID.
     *
     * Performs a lightweight query returning only the ID column.
     * Useful when you need the integer ID for operations but only have the public UUID.
     *
     * @param  EntityId  $entityUuid  The catalogue's public UUID
     * @return int|null The catalogue's integer ID, or null if UUID not found
     *
     * @throws \Illuminate\Database\QueryException On database failure
     */
    public function getIdByUuid(EntityId $entityUuid): ?int;

    public function findByCriteria(CatalogueCriteriaDTO $criteria): Catalogues;

    public function findByPublicUid(EntityId $uuid): ?Catalogue;
}
