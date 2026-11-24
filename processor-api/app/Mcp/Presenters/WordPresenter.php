<?php

declare(strict_types=1);

namespace App\Mcp\Presenters;

use App\Domain\JapaneseMaterial\Words\Models\Word;
use App\Mcp\Support\StudyLevel;

/**
 * Shapes a dictionary word for an assistant: the fields it needs to explain or quiz on the word,
 * and none of the integer ids or viewer state the website's own resources carry.
 */
final class WordPresenter
{
    /**
     * @return array{uuid: string, word: string, reading: string, meanings: array<int, string>, level: ?string, types: array<int, string>}
     */
    public function present(Word $word): array
    {
        return [
            'uuid' => $word->getUuid()->value(),
            'word' => $word->getSurface(),
            'reading' => $word->getFurigana(),
            'meanings' => $word->getMeanings(),
            'level' => StudyLevel::fromDictionary($word->getJlpt())?->value,
            'types' => $word->getWordTypes(),
        ];
    }
}
