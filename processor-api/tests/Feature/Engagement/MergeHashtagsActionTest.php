<?php

declare(strict_types=1);

namespace Tests\Feature\Engagement;

use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Engagement\Actions\MergeHashtagsAction;
use App\Application\Engagement\Interfaces\Repositories\HashtagRepositoryInterface;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Infrastructure\Auth\Providers\PassportCurrentUserProvider;
use App\Infrastructure\Persistence\Models\AdminActivity;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\HashtagEntity;
use App\Infrastructure\Persistence\Models\Uniquehashtag;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\Support\FailingActivityRecorder;
use Tests\TestCase;

/**
 * MergeHashtagsAction against the database, without the panel: a merge moves every link and
 * deletes the source in one transaction, or changes nothing.
 */
final class MergeHashtagsActionTest extends TestCase
{
    use InteractsWithAdminPanel;
    use RefreshDatabase;

    private AuthenticatedUser $staff;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAdminPanel();
        $this->staff = PassportCurrentUserProvider::authenticatedUserFor($this->createAdmin());
        $this->author = $this->createPanelUser('Tagger');
    }

    public function test_merge_moves_the_links_and_writes_one_row(): void
    {
        $target = Uniquehashtag::create(['content' => '#jlpt']);
        $source = Uniquehashtag::create(['content' => '#JLPT']);
        $link = $this->link($source);

        $result = app(MergeHashtagsAction::class)->execute($source->id, $target->id, $this->staff);

        self::assertTrue($result->isSuccess());
        self::assertNull($source->fresh());
        self::assertSame($target->id, $link->fresh()->hashtag_id);
        $row = AdminActivity::query()->sole();
        self::assertSame(ActivityAction::HashtagMerged, $row->action);
        self::assertSame((string) $target->id, $row->subject_id);
        self::assertSame([$link->id], $row->context['moved_link_ids']);
    }

    public function test_a_tag_cannot_be_merged_into_itself(): void
    {
        $tag = Uniquehashtag::create(['content' => '#jlpt']);

        $result = app(MergeHashtagsAction::class)->execute($tag->id, $tag->id, $this->staff);

        self::assertSame('Hashtags.MergeIntoItself', $result->getError()->code);
        self::assertSame(0, AdminActivity::query()->count());
    }

    public function test_an_unknown_tag_is_not_found(): void
    {
        $tag = Uniquehashtag::create(['content' => '#jlpt']);

        $result = app(MergeHashtagsAction::class)->execute(999_999, $tag->id, $this->staff);

        self::assertSame('Hashtags.NotFound', $result->getError()->code);
    }

    public function test_merge_rolls_back_when_the_activity_row_fails(): void
    {
        $target = Uniquehashtag::create(['content' => '#target']);
        $source = Uniquehashtag::create(['content' => '#source']);
        $link = $this->link($source);
        $this->app->instance(ActivityRecorderInterface::class, new FailingActivityRecorder);

        $result = app(MergeHashtagsAction::class)->execute($source->id, $target->id, $this->staff);

        self::assertSame('Hashtags.MergeFailed', $result->getError()->code);
        self::assertNotNull($source->fresh());
        self::assertSame($source->id, $link->fresh()->hashtag_id);
        self::assertSame(0, AdminActivity::query()->count());
    }

    public function test_a_failed_merge_leaves_both_tags_intact(): void
    {
        $target = Uniquehashtag::create(['content' => '#target']);
        $source = Uniquehashtag::create(['content' => '#source']);
        $link = $this->link($source);

        $real = app(HashtagRepositoryInterface::class);
        $failing = $this->createMock(HashtagRepositoryInterface::class);
        $failing->method('findTag')->willReturnCallback(static fn (int $id) => $real->findTag($id));
        $failing->method('merge')->willReturnCallback(static function (int $sourceId, int $targetId) use ($real): never {
            $real->merge($sourceId, $targetId);

            throw new RuntimeException('Simulated failure after the links moved');
        });
        $this->app->instance(HashtagRepositoryInterface::class, $failing);

        $result = app(MergeHashtagsAction::class)->execute($source->id, $target->id, $this->staff);

        self::assertTrue($result->isFailure());
        self::assertNotNull($source->fresh());
        self::assertSame($source->id, $link->fresh()->hashtag_id);
        self::assertSame(0, AdminActivity::query()->count());
    }

    private function link(Uniquehashtag $tag): HashtagEntity
    {
        return HashtagEntity::query()->create([
            'hashtag_id' => $tag->id,
            'entity_type_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'entity_id' => Article::factory()->create(['user_id' => $this->author->id])->id,
            'user_id' => $this->author->id,
        ]);
    }
}
