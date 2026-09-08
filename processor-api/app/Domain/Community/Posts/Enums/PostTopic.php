<?php

declare(strict_types=1);

namespace App\Domain\Community\Posts\Enums;

/**
 * Canonical Post topic vocabulary.
 *
 * The persisted `posts.type` column stores these codes as strings. The legacy
 * read paths (PostController::index/show/getPostImpressionsSearch) label code 6
 * as "Announcement" and never label code 7; the canonical mapping below comes
 * from the write/filter vocabulary in PostController::getPostTypes() and is the
 * single source of truth for v1.
 */
enum PostTopic: int
{
    case CONTENT_RELATED = 1;
    case OFF_TOPIC = 2;
    case FAQ = 3;
    case TECHNICAL = 4;
    case BUG = 5;
    case FEEDBACK = 6;
    case ANNOUNCEMENT = 7;

    public function label(): string
    {
        return match ($this) {
            self::CONTENT_RELATED => 'Content-related',
            self::OFF_TOPIC => 'Off-topic',
            self::FAQ => 'FAQ',
            self::TECHNICAL => 'Technical',
            self::BUG => 'Bug',
            self::FEEDBACK => 'Feedback',
            self::ANNOUNCEMENT => 'Announcement',
        };
    }
}
