<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Words\Services;

interface WordExtractionServiceInterface
{
    /**
     * @return array<int, int>
     */
    public function extractWordIds(string $text): array;
}
