<?php

namespace App\Domain\Engagement\DTOs;

use Illuminate\Contracts\Support\Arrayable;

readonly class LikeCreateDTO implements Arrayable
{
    public function __construct(
        public int $userId,
        public int $templateId,
        public int $realObjectId,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'template_id' => $this->templateId,
            'real_object_id' => $this->realObjectId,
            'value' => 1,
        ];
    }
}
