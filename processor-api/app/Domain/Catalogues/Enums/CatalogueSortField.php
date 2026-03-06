<?php

declare(strict_types=1);

namespace App\Domain\Catalogues\Enums;

enum CatalogueSortField: string
{
    case CREATED_AT = 'created_at';
    case VIEWS = 'views';
}
