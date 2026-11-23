<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Sentences\Actions;

use App\Application\JapaneseMaterial\Kanjis\Interfaces\Repositories\KanjiRepositoryInterface;
use App\Application\JapaneseMaterial\Kanjis\Services\KanjiExtractionServiceInterface;
use App\Application\JapaneseMaterial\Words\Services\WordExtractionServiceInterface;
use App\Domain\JapaneseMaterial\Sentences\DTOs\SentenceRelationshipIdsDTO;

final class DeriveSentenceRelationshipsAction
{
    public function __construct(
        private readonly KanjiExtractionServiceInterface $kanjiExtractionService,
        private readonly KanjiRepositoryInterface $kanjiRepository,
        private readonly WordExtractionServiceInterface $wordExtractionService,
    ) {
    }

    public function execute(string $content): SentenceRelationshipIdsDTO
    {
        $characters = $this->kanjiExtractionService->extractUniqueKanjis($content);

        return new SentenceRelationshipIdsDTO(
            kanjiIds: array_values(array_unique($this->kanjiRepository->findIdsByCharacters($characters))),
            wordIds: array_values(array_unique($this->wordExtractionService->extractWordIds($content))),
        );
    }
}
