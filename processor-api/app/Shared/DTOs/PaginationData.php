<?php
namespace App\Shared\DTOs;

class PaginationData
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 15
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            page: $data['page'] ?? 1,
            perPage: min($data['per_page'] ?? 15, 50) // Max 50 per page
        );
    }
}
