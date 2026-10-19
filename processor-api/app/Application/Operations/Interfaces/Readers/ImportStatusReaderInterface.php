<?php

declare(strict_types=1);

namespace App\Application\Operations\Interfaces\Readers;

use App\Domain\Operations\DTOs\DatasetHealthDTO;
use App\Domain\Operations\DTOs\ImportRunDTO;

/**
 * Read-only view of the dictionary import: runs, per-table health and missing dumps. Live row
 * counts are full scans on some tables, so implementations cache them and refresh on request.
 */
interface ImportStatusReaderInterface
{
    public const LIVE_COUNT_TTL_SECONDS = 600;

    /**
     * Latest run per environment, most recent first.
     *
     * @return list<ImportRunDTO>
     */
    public function runs(): array;

    /**
     * Per-table health against the given environment's latest run, in manifest order.
     *
     * @return list<DatasetHealthDTO>
     */
    public function datasetHealth(?string $environment): array;

    public function refreshLiveCounts(): void;
}
