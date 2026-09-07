<?php

declare(strict_types=1);

namespace Tests\Unit\Community\Posts;

use App\Domain\Community\Posts\Enums\PostTopic;
use App\Infrastructure\Persistence\Models\Post as PersistencePost;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Repositories\PostMapper;
use Carbon\Carbon;
use RuntimeException;
use Tests\TestCase;

class PostMapperTest extends TestCase
{
    public function test_it_maps_persistence_columns_to_domain_values(): void
    {
        $post = $this->makePost(['type' => '3', 'locked' => 1]);

        $domainPost = (new PostMapper)->mapToDomain($post);

        self::assertSame(42, $domainPost->getIdValue());
        self::assertSame('922f91b4-cbe8-4ca0-8bf8-50de48f5d086', $domainPost->getUuid()->value());
        self::assertSame('Community question', $domainPost->getTitle());
        self::assertSame('Full post content', $domainPost->getContent());
        self::assertSame(PostTopic::FAQ, $domainPost->getTopic());
        self::assertTrue($domainPost->isLocked());
        self::assertSame(7, $domainPost->getAuthorId());
        self::assertSame('fa088bc4-1e1b-4f5c-9876-0bc3e99b058f', $domainPost->getAuthorUuid()->value());
        self::assertSame('Example user', $domainPost->getAuthorName());
        self::assertSame('2026-09-01T12:00:00+00:00', $domainPost->getCreatedAt()->format('c'));
        self::assertSame('2026-09-01T12:30:00+00:00', $domainPost->getUpdatedAt()->format('c'));
    }

    public function test_it_casts_persistence_boolean_like_lock_state(): void
    {
        self::assertFalse((new PostMapper)->mapToDomain($this->makePost(['locked' => 0]))->isLocked());
        self::assertTrue((new PostMapper)->mapToDomain($this->makePost(['locked' => 1]))->isLocked());
    }

    public function test_it_refuses_a_topic_code_outside_the_canonical_range(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("topic code '8'");

        (new PostMapper)->mapToDomain($this->makePost(['type' => '8']));
    }

    public function test_it_refuses_a_post_without_a_resolvable_author(): void
    {
        $post = $this->makePost();
        $post->setRelation('author', null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no resolvable author');

        (new PostMapper)->mapToDomain($post);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function makePost(array $overrides = []): PersistencePost
    {
        $post = new PersistencePost;
        $post->forceFill(array_merge([
            'id' => 42,
            'uuid' => '922f91b4-cbe8-4ca0-8bf8-50de48f5d086',
            'entity_type_uuid' => 'a4b78a83-f180-49b5-9f8a-39500cd8fabf',
            'user_id' => 7,
            'type' => '2',
            'title' => 'Community question',
            'content' => 'Full post content',
            'locked' => 0,
            'created_at' => Carbon::parse('2026-09-01T12:00:00+00:00'),
            'updated_at' => Carbon::parse('2026-09-01T12:30:00+00:00'),
        ], $overrides));

        $author = new User;
        $author->forceFill([
            'id' => 7,
            'uuid' => 'fa088bc4-1e1b-4f5c-9876-0bc3e99b058f',
            'name' => 'Example user',
        ]);

        $post->setRelation('author', $author);

        return $post;
    }
}
