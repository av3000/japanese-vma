<?php

declare(strict_types=1);

namespace App\Http\v1\Users\Resources;

use App\Domain\Users\Models\User as DomainUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    public function __construct(
        private DomainUser $user,
        private bool $isOwnProfile
    ) {
        parent::__construct($user);
    }


    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->user->getUuid()->value(),
            'name' => $this->user->getName()->value(),
            'roles' => $this->user->getRoles(),
            'created_at' => $this->user->getCreatedAt()->format('Y-m-d H:i:s'),
            'email' => $this->when($this->isOwnProfile, $this->user->getEmail()->value()),
        ];
    }
}
