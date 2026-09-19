<?php

declare(strict_types=1);

namespace App\Domain\Processing\Enums;

/**
 * The background task a processing_states row tracks. One value per consolidated job. The two
 * legacy values exist only so the backfill can name what it migrated from; nothing dispatches
 * them.
 */
enum ProcessingTaskType: string
{
    case ArticleContentProcessing = 'article_content_processing';

    /** @deprecated backfill source only */
    case LegacyKanjiExtraction = 'kanji_extraction';

    /** @deprecated backfill source only */
    case LegacyWordsExtraction = 'words_extraction';

    /**
     * @return list<self>
     */
    public static function legacy(): array
    {
        return [self::LegacyKanjiExtraction, self::LegacyWordsExtraction];
    }
}
