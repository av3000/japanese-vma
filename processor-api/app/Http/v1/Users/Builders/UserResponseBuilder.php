<?php

declare(strict_types=1);

namespace App\Http\v1\Users\Builders;

use App\Http\v1\Users\Resources\UserProfileResource;
use Illuminate\Pagination\LengthAwarePaginator;

class UserResponseBuilder
{
    /**
     * Builds the complete JSON response structure for a user listing.
     *
     * @param LengthAwarePaginator $paginator A paginator containing UserWithProfileContext DTOs.
     * @return array The structured response array (users + pagination).
     */
    public function buildCollectionResponse(LengthAwarePaginator $paginator): array
    {
        return [
            'users' => UserProfileResource::collection($paginator),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }
}
