<?php

declare(strict_types=1);

namespace App\Domain\Catalogues\DTOs;

use App\Domain\Catalogues\ValueObjects\CatalogueSortCriteria;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\SearchTerm;

readonly class CatalogueCriteriaDTO
{
    public function __construct(
        public ?SearchTerm $search,
        public CatalogueSortCriteria $sort,
        public Pagination $pagination,
        public bool $publicOnly = true,
        public bool $customOnly = true
    ) {}
}
