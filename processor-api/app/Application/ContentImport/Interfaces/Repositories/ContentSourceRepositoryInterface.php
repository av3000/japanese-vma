<?php

declare(strict_types=1);

namespace App\Application\ContentImport\Interfaces\Repositories;

use App\Domain\ContentImport\DTOs\ContentSourceDTO;

interface ContentSourceRepositoryInterface
{
    public function findByKey(string $key): ?ContentSourceDTO;

    /**
     * @return list<string>
     */
    public function enabledKeys(): array;
}
