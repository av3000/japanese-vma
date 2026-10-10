<?php

declare(strict_types=1);

namespace App\Domain\ContentImport\DTOs;

use App\Domain\ContentImport\Enums\ImportItemOutcome;

final readonly class ImportItemResult
{
    /**
     * @param list<string> $tags
     */
    public function __construct(
        public string $externalId,
        public string $title,
        public ImportItemOutcome $outcome,
        public array $tags = [],
        public ?string $detail = null,
    ) {
    }
}
