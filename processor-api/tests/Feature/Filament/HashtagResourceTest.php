<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Application\Engagement\Actions\MergeHashtagsAction;
use App\Application\Engagement\Interfaces\Repositories\HashtagRepositoryInterface;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Filament\Resources\Hashtags\Pages\ListHashtags;
use App\Filament\Support\PanelActor;
use App\Infrastructure\Persistence\Models\AdminActivity;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\HashtagEntity;
use App\Infrastructure\Persistence\Models\Post;
use App\Infrastructure\Persistence\Models\Uniquehashtag;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RuntimeException;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\TestCase;

class HashtagResourceTest extends TestCase
{
    use InteractsWithAdminPanel;
    use RefreshDatabase;

    private User $admin;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAdminPanel();
        $this->admin = $this->createAdmin();
        $this->author = $this->createPanelUser('Tagger');
        $this->actingAsPanelUser($this->admin);
    }

    public function test_usage_counts_split_by_type_and_count_removed_links_separately(): void
    {
        $grammar = Uniquehashtag::create(['content' => '#grammar']);
        $articles = Article::factory()->count(2)->create(['user_id' => $this->author->id]);
        $post = Post::factory()->create(['user_id' => $this->author->id]);
        $this->link($grammar, ObjectTemplateType::ARTICLE, $articles[0]);
        $this->link($grammar, ObjectTemplateType::ARTICLE, $articles[1]);
        $this->link($grammar, ObjectTemplateType::POST, $post);
        $this->link($grammar, ObjectTemplateType::ARTICLE, Article::factory()->create(['user_id' => $this->author->id]))->delete();
        $unused = Uniquehashtag::create(['content' => '#orphan']);

        Livewire::test(ListHashtags::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$grammar, $unused])
            ->assertTableColumnStateSet('active_links', 3, $grammar)
            ->assertTableColumnStateSet('removed_links', 1, $grammar)
            ->assertTableColumnStateSet('by_type', 'Articles 2 · Posts 1 · Lists 0', $grammar)
            ->assertTableColumnStateSet('active_links', 0, $unused);

        Livewire::test(ListHashtags::class)
            ->filterTable('unused')
            ->assertCanSeeTableRecords([$unused])
            ->assertCanNotSeeTableRecords([$grammar]);

        Livewire::test(ListHashtags::class)
            ->filterTable('entity_type', 'post_links')
            ->assertCanSeeTableRecords([$grammar])
            ->assertCanNotSeeTableRecords([$unused]);

        Livewire::test(ListHashtags::class)
            ->filterTable('minimum_usage', ['minimum' => 3])
            ->assertCanSeeTableRecords([$grammar])
            ->assertCanNotSeeTableRecords([$unused]);
    }

    public function test_duplicate_filter_finds_case_and_width_variants(): void
    {
        $lower = Uniquehashtag::create(['content' => '#jlpt']);
        $upper = Uniquehashtag::create(['content' => '#JLPT']);
        $wide = Uniquehashtag::create(['content' => '＃ＪＬＰＴ']);
        $other = Uniquehashtag::create(['content' => '#kanji']);

        Livewire::test(ListHashtags::class)
            ->filterTable('has_duplicates')
            ->assertCanSeeTableRecords([$lower, $upper, $wide])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_rename_updates_the_tag_and_is_recorded(): void
    {
        $tag = Uniquehashtag::create(['content' => '#grammer']);

        Livewire::test(ListHashtags::class)
            ->callTableAction('rename', $tag, ['name' => '#grammar'])
            ->assertNotified('Hashtag renamed.');

        self::assertSame('#grammar', $tag->fresh()->content);
        self::assertSame('#grammar', $tag->fresh()->normalized_content);

        $row = AdminActivity::query()->sole();
        self::assertSame(ActivityAction::HashtagRenamed, $row->action);
        self::assertSame(['content' => '#grammer'], $row->changes['before']);
    }

    public function test_rename_that_collides_after_normalisation_is_refused(): void
    {
        Uniquehashtag::create(['content' => '#jlpt']);
        $tag = Uniquehashtag::create(['content' => '#n5']);

        Livewire::test(ListHashtags::class)
            ->callTableAction('rename', $tag, ['name' => '＃ＪＬＰＴ'])
            ->assertNotified("That name matches the existing tag '#jlpt'. Merge into it instead.");

        self::assertSame('#n5', $tag->fresh()->content);
        self::assertSame(0, AdminActivity::query()->count());
    }

    public function test_merge_moves_links_retires_duplicates_and_deletes_the_source(): void
    {
        $target = Uniquehashtag::create(['content' => '#jlpt']);
        $source = Uniquehashtag::create(['content' => '#JLPT']);
        $shared = Article::factory()->create(['user_id' => $this->author->id]);
        $onlySource = Article::factory()->create(['user_id' => $this->author->id]);
        $this->link($target, ObjectTemplateType::ARTICLE, $shared);
        $duplicate = $this->link($source, ObjectTemplateType::ARTICLE, $shared);
        $moved = $this->link($source, ObjectTemplateType::ARTICLE, $onlySource);

        Livewire::test(ListHashtags::class)
            ->callTableAction('merge', $source, ['target' => $target->id])
            ->assertNotified('Merged "#JLPT" into "#jlpt".');

        self::assertNull($source->fresh());
        self::assertSame(2, HashtagEntity::query()->where('hashtag_id', $target->id)->count());
        self::assertSame($target->id, HashtagEntity::withTrashed()->find($duplicate->id)->hashtag_id);
        self::assertNotNull(HashtagEntity::withTrashed()->find($duplicate->id)->deleted_at);
        self::assertNull(HashtagEntity::query()->find($moved->id)->deleted_at);

        $row = AdminActivity::query()->sole();
        self::assertSame(ActivityAction::HashtagMerged, $row->action);
        self::assertSame('#JLPT', $row->context['source_name']);
        self::assertEqualsCanonicalizing([$duplicate->id, $moved->id], $row->context['moved_link_ids']);
        self::assertSame([$duplicate->id], $row->context['retired_link_ids']);
    }

    public function test_bulk_merge_keeps_the_most_used_tag_by_default(): void
    {
        $popular = Uniquehashtag::create(['content' => '#kanji']);
        $rare = Uniquehashtag::create(['content' => '#Kanji']);
        foreach (Article::factory()->count(2)->create(['user_id' => $this->author->id]) as $article) {
            $this->link($popular, ObjectTemplateType::ARTICLE, $article);
        }

        Livewire::test(ListHashtags::class)
            ->callTableBulkAction('mergeSelected', [$rare, $popular])
            ->assertNotified('1 merged, 0 failed');

        self::assertNotNull($popular->fresh());
        self::assertNull($rare->fresh());
    }

    public function test_a_failed_merge_leaves_both_tags_intact(): void
    {
        $target = Uniquehashtag::create(['content' => '#target']);
        $source = Uniquehashtag::create(['content' => '#source']);
        $link = $this->link($source, ObjectTemplateType::ARTICLE, Article::factory()->create(['user_id' => $this->author->id]));

        $real = app(HashtagRepositoryInterface::class);
        $failing = $this->createMock(HashtagRepositoryInterface::class);
        $failing->method('findTag')->willReturnCallback(static fn (int $id) => $real->findTag($id));
        $failing->method('merge')->willReturnCallback(static function (int $sourceId, int $targetId) use ($real): never {
            $real->merge($sourceId, $targetId);

            throw new RuntimeException('Simulated failure after the links moved');
        });
        $this->app->instance(HashtagRepositoryInterface::class, $failing);

        $result = app(MergeHashtagsAction::class)->execute($source->id, $target->id, PanelActor::current());

        self::assertTrue($result->isFailure());
        self::assertNotNull($source->fresh());
        self::assertSame($source->id, $link->fresh()->hashtag_id);
        self::assertSame(0, AdminActivity::query()->count());
    }

    public function test_new_tags_reuse_an_existing_variant(): void
    {
        $existing = Uniquehashtag::create(['content' => '#jlpt']);

        app(HashtagRepositoryInterface::class)->create([
            'entity_id' => Article::factory()->create(['user_id' => $this->author->id])->id,
            'entity_type_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'content' => '＃ＪＬＰＴ',
            'user_id' => $this->author->id,
        ]);

        self::assertSame(1, Uniquehashtag::query()->count());
        self::assertSame(1, HashtagEntity::query()->where('hashtag_id', $existing->id)->count());
    }

    public function test_hashtags_need_hashtags_manage(): void
    {
        $this->actingAs($this->createUserWithPermissions(['posts.view']), 'web')
            ->get('/admin/hashtags')
            ->assertForbidden();
    }

    private function link(Uniquehashtag $tag, ObjectTemplateType $type, Model $entity): HashtagEntity
    {
        return HashtagEntity::query()->create([
            'hashtag_id' => $tag->id,
            'entity_type_id' => $type->getLegacyId(),
            'entity_id' => $entity->getKey(),
            'user_id' => $this->author->id,
        ]);
    }
}
