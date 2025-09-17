<?php
namespace App\Application\Articles\Actions\Processing;

use App\Application\Articles\Interfaces\Repositories\KanjiRepositoryInterface;

class ExtractKanjisAction
{
    public function __construct(
        private KanjiRepositoryInterface $kanjiRepository
    ) {}

    public function execute(string $text): array
    {
        preg_match_all('/[\x{4E00}-\x{9FAF}]/u', $text, $matches);
        $uniqueKanjiCharacters = array_unique($matches[0]);

        if (empty($uniqueKanjiCharacters)) {
            return [];
        }

        return $this->kanjiRepository->findIdsByCharacters($uniqueKanjiCharacters);
    }
}
