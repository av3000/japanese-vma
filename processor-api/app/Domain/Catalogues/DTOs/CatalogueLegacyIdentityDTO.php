<?php

declare(strict_types=1);

namespace App\Domain\Catalogues\DTOs;

use App\Domain\Shared\ValueObjects\EntityId;

readonly class CatalogueLegacyIdentityDTO
{
    public function __construct(
        public int $id,
        public EntityId $uuid,
    ) {
    }
}
