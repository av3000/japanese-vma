<?php

declare(strict_types=1);

namespace App\Http\v1\Auth\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Domain\Users\Models\User as DomainUser;
use App\Http\V1\Admin\Resources\RoleResource;

class AuthUserResource extends JsonResource
{
    private ?string $accessToken = null;

    public function withToken(string $token): self
    {
        $this->accessToken = $token;
        return $this;
    }

    public function toArray($request): array
    {
        /** @var DomainUser $user */
        $user = $this->resource;

        $data = [
            'id' => $user->getId()->value(),
            'uuid' => $user->getUuid()->value(),
            'name' => $user->getName()->value(),
            'email' => $user->getEmail()->value(),
            'roles' => RoleResource::collection($user->getRoles()),
            'is_admin' => $user->isAdmin(),
            'created_at' => $user->getCreatedAt()->format('c'),
        ];

        if ($this->accessToken) {
            $data['access_token'] = $this->accessToken;
            $data['token_type'] = 'Bearer';
        }

        return $data;
    }
}
