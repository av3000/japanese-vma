<?php

declare(strict_types=1);

namespace App\Http\v1\Admin\Resources;

use App\Domain\Users\Models\Role as DomainRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property DomainRole $resource
 */
class RoleResource extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DomainRole $role */
        $role = $this->resource;

        return [
            'name' => $role->getName(),
            'guard_name' => $role->getGuardName(),
        ];
    }
}
