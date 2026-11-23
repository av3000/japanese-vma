<?php

declare(strict_types=1);

namespace App\Http\v1\Auth\Resources;

use App\Domain\Users\Models\User as DomainUser;
use App\Http\v1\Admin\Resources\RoleResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property DomainUser $resource
 */
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

        // The token pair is only present on the login/register responses; `me` returns the same
        // resource without it. Expressing that through `when()` rather than a plain `if` is what
        // makes Scramble emit both fields as optional, so the generated client stops promising an
        // `access_token` that a `me` payload never carries.
        return [
            'id' => $user->getId()->value(),
            'uuid' => $user->getUuid()->value(),
            'name' => $user->getName()->value(),
            'email' => $user->getEmail()->value(),
            'roles' => RoleResource::collection($user->getRoles()),
            'is_admin' => $user->isAdmin(),
            'created_at' => $user->getCreatedAt()->format('c'),
            'access_token' => $this->when($this->accessToken !== null, fn (): string => (string) $this->accessToken),
            'token_type' => $this->when($this->accessToken !== null, 'Bearer'),
        ];
    }
}
