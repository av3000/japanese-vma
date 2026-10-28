<?php

declare(strict_types=1);

namespace Tests\Unit\Engagement;

use App\Application\Engagement\Interfaces\Repositories\HashtagRepositoryInterface;
use App\Application\Engagement\Services\HashtagService;
use App\Domain\Engagement\ValueObjects\HashtagName;
use App\Domain\Shared\Enums\ObjectTemplateType;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Tag validation goes through HashtagName, so a tag the value object refuses is an invalid tag
 * before anything is written, not a database failure afterwards.
 */
final class HashtagServiceTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function refusedTags(): array
    {
        return [
            'longer than the column' => ['#'.str_repeat('a', HashtagName::MAX_LENGTH)],
            'blank' => ['   '],
            'only stripped characters' => ['<>'],
        ];
    }

    #[DataProvider('refusedTags')]
    public function test_create_refuses_the_tag_before_writing(string $tag): void
    {
        $repository = $this->createMock(HashtagRepositoryInterface::class);
        $repository->expects(self::never())->method('create');

        $result = (new HashtagService($repository))->createTagsForEntity(1, ObjectTemplateType::POST, [$tag], 1);

        self::assertSame('Hashtags.InvalidTag', $result->getError()->code);
    }

    #[DataProvider('refusedTags')]
    public function test_sync_refuses_the_tag_before_clearing_anything(string $tag): void
    {
        $repository = $this->createMock(HashtagRepositoryInterface::class);
        $repository->expects(self::never())->method('deleteByEntity');
        $repository->expects(self::never())->method('create');

        $result = (new HashtagService($repository))->syncTagsForEntity(1, ObjectTemplateType::POST, [$tag], 1);

        self::assertSame('Hashtags.InvalidTag', $result->getError()->code);
    }

    public function test_a_tag_at_the_length_limit_is_written(): void
    {
        $repository = $this->createMock(HashtagRepositoryInterface::class);
        $repository->expects(self::once())->method('create');

        $result = (new HashtagService($repository))->createTagsForEntity(1, ObjectTemplateType::POST, ['#'.str_repeat('a', HashtagName::MAX_LENGTH - 1)], 1);

        self::assertTrue($result->isSuccess());
    }
}
