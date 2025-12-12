<?php

declare(strict_types=1);

namespace App\Http\v1\Users\Resources;

use App\Application\Users\DTOs\UserWithProfileContext;
use App\Http\V1\Admin\Resources\RoleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    public function __construct(UserWithProfileContext $userContext)
    {
        parent::__construct($userContext);
    }

    public function toArray(Request $request): array
    {
        /** @var UserWithProfileContext $userContext */
        $userContext = $this->resource;

        $user = $userContext->user;

        return [
            'uuid' => $user->getUuid()->value(),
            'name' => $user->getName()->value(),
            'roles' => RoleResource::collection($user->getRoles()),
            'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            'isOwnProfile' => $userContext->isOwnProfile,
            // TODO: in this admin route it should show all data including email, do checkRole('admin') or some 'isAdmin' validation.
            'email' => $this->when($userContext->isOwnProfile, $user->getEmail()->value()),
        ];
    }
}
