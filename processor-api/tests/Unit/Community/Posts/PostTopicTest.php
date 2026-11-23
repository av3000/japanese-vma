<?php

declare(strict_types=1);

namespace Tests\Unit\Community\Posts;

use App\Domain\Community\Posts\Enums\PostSort;
use App\Domain\Community\Posts\Enums\PostTopic;
use PHPUnit\Framework\TestCase;

class PostTopicTest extends TestCase
{
    public function test_every_persisted_code_maps_to_its_canonical_label(): void
    {
        self::assertSame('Content-related', PostTopic::from(1)->label());
        self::assertSame('Off-topic', PostTopic::from(2)->label());
        self::assertSame('FAQ', PostTopic::from(3)->label());
        self::assertSame('Technical', PostTopic::from(4)->label());
        self::assertSame('Bug', PostTopic::from(5)->label());
        self::assertSame('Feedback', PostTopic::from(6)->label());
        self::assertSame('Announcement', PostTopic::from(7)->label());
    }

    /**
     * PostController::index(), show(), and getPostImpressionsSearch() label code 6
     * "Announcement" and never label code 7. v1 must not inherit that defect.
     */
    public function test_legacy_topic_label_defect_is_not_reproduced(): void
    {
        self::assertSame(PostTopic::FEEDBACK, PostTopic::from(6));
        self::assertNotSame('Announcement', PostTopic::from(6)->label());
        self::assertSame(PostTopic::ANNOUNCEMENT, PostTopic::from(7));
    }

    public function test_codes_outside_the_canonical_range_are_rejected(): void
    {
        self::assertNull(PostTopic::tryFrom(0));
        self::assertNull(PostTopic::tryFrom(8));
        self::assertNull(PostTopic::tryFrom(20));
    }

    public function test_sort_vocabulary_is_closed_and_defaults_to_newest(): void
    {
        self::assertSame(PostSort::NEWEST, PostSort::DEFAULT);
        self::assertNull(PostSort::tryFrom('oldest'));
        self::assertNull(PostSort::tryFrom('created_at'));
        self::assertSame(
            ['newest', 'popular'],
            array_map(static fn (PostSort $sort): string => $sort->value, PostSort::cases()),
        );
    }
}
