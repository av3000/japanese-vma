<?php

namespace App\Domain\Engagement\DTOs;

use Illuminate\Contracts\Support\Arrayable;

readonly class ViewCreateDTO implements Arrayable
{
    public function __construct(
        public ?int $userId,
        public ?string $userIp,
        public int $templateId,
        public int $realObjectId,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'user_ip' => $this->userIp,
            'template_id' => $this->templateId,
            'real_object_id' => $this->realObjectId,
        ];
    }
}
