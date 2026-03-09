<?php

declare(strict_types=1);

namespace App\Application\Catalogues\Interfaces\Repositories;

use App\Domain\Catalogues\DTOs\CatalogueCriteriaDTO;
use App\Domain\Catalogues\Models\Catalogues;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;

interface CatalogueRepositoryInterface
{
    /**
     * Create default learning lists for a user
     *
     * @param UserId $userId
     * @return void
     */
    public function createDefaultCataloguesForUser(UserId $userId): void;

    public function create(Catalogue $catalogue): Catalogue;

    public function update(Catalogue $catalogue): void;

    public function findByCriteria(CatalogueCriteriaDTO $criteria): Catalogues;

    public function findByPublicUid(EntityId $uuid): ?Catalogue;
}
