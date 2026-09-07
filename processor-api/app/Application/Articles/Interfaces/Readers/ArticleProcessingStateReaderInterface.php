<?php

declare(strict_types=1);

namespace App\Application\Articles\Interfaces\Readers;

use App\Application\Articles\DTOs\ArticleProcessingStateDTO;

/**
 * Batch access to Article background-processing state, keyed by Article UUID.
 *
 * LastOperationServiceInterface returns the Eloquent LastOperationState model, so
 * consuming it directly would pull a persistence type into the application layer.
 * This narrow port keeps that leak on the infrastructure side of the boundary.
 * Widening it to the whole LastOperations module is out of scope here.
 */
interface ArticleProcessingStateReaderInterface
{
    /**
     * @param array<int, string> $articleUuids
     *
     * @return array<string, ArticleProcessingStateDTO> keyed by Article UUID
     */
    public function latestKanjiExtractionStates(array $articleUuids): array;
}
