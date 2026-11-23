<?php

declare(strict_types=1);

namespace App\Application\Articles\Interfaces\Readers;

use App\Domain\Processing\DTOs\ProcessingStateDTO;

/**
 * Read access to an Article's current content-processing state, keyed by Article UUID.
 *
 * A narrow port for the two read paths (detail and list) so they do not depend on the whole
 * processing module. One task type per article since ADR 0001, hence "current" not "latest".
 */
interface ArticleProcessingStateReaderInterface
{
    public function currentState(string $articleUuid): ?ProcessingStateDTO;

    /**
     * @param array<int, string> $articleUuids
     *
     * @return array<string, ProcessingStateDTO> keyed by Article UUID
     */
    public function currentStates(array $articleUuids): array;
}
