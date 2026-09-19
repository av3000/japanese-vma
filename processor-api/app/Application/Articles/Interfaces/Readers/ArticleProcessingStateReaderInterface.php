<?php

declare(strict_types=1);

namespace App\Application\Articles\Interfaces\Readers;

use App\Domain\Articles\DTOs\ArticleProcessingStateDTO;

/**
 * Read access to an Article's current content-processing state, keyed by Article UUID.
 *
 * A narrow port for the two read paths (detail and list) so they do not depend on the whole
 * processing module. One task type per article since ADR 0001, hence "current" not "latest".
 */
interface ArticleProcessingStateReaderInterface
{
    public function currentState(string $articleUuid): ?ArticleProcessingStateDTO;

    /**
     * @param array<int, string> $articleUuids
     *
     * @return array<string, ArticleProcessingStateDTO> keyed by Article UUID
     */
    public function currentStates(array $articleUuids): array;
}
