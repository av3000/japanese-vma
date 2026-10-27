<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Application\Engagement\Interfaces\Repositories\HashtagRepositoryInterface;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Filament\Resources\Hashtags\Pages\ListHashtags;
use App\Infrastructure\Persistence\Models\AdminActivity;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\HashtagEntity;
use App\Infrastructure\Persistence\Models\Post;
use App\Infrastructure\Persistence\Models\Uniquehashtag;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\Support\CreatesHashtags;
use Tests\TestCase;

class HashtagResourceTest extends TestCase
{
    use CreatesHashtags;
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
        $grammar = $this->hashtag('#grammar');
        $articles = Article::factory()->count(2)->create(['user_id' => $this->author->id]);
        $post = Post::factory()->create(['user_id' => $this->author->id]);
        $this->link($grammar, ObjectTemplateType::ARTICLE, $articles[0]);
        $this->link($grammar, ObjectTemplateType::ARTICLE, $articles[1]);
        $this->link($grammar, ObjectTemplateType::POST, $post);
        $this->link($grammar, ObjectTemplateType::ARTICLE, Article::factory()->create(['user_id' => $this->author->id]))->delete();
        $unused = $this->hashtag('#orphan');

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
        $lower = $this->hashtag('#jlpt');
        $upper = $this->hashtag('#JLPT');
        $wide = $this->hashtag('＃ＪＬＰＴ');
        $other = $this->hashtag('#kanji');

        Livewire::test(ListHashtags::class)
            ->filterTable('has_duplicates')
            ->assertCanSeeTableRecords([$lower, $upper, $wide])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_rename_updates_the_tag_and_is_recorded(): void
    {
        $tag = $this->hashtag('#grammer');

        Livewire::test(ListHashtags::class)
            ->callTableAction('rename', $tag, ['name' => '#grammar'])
            ->assertNotified('Hashtag renamed.');

        self::assertSame('#grammar', $tag->fresh()->content);
        self::assertSame('#grammar', $tag->fresh()->normalized_content);

        $row = AdminActivity::query()->sole();
        self::assertSame(ActivityAction::HashtagRenamed, $row->action);
        self::assertSame(['content' => '#grammer'], $row->changes['before']);
    }

    public function test_a_non_admin_holding_hashtags_manage_can_rename(): void
    {
        $this->actingAsPanelUser($this->createUserWithPermissions(['hashtags.manage']));
        $tag = $this->hashtag('#grammer');

        Livewire::test(ListHashtags::class)
            ->callTableAction('rename', $tag, ['name' => '#grammar'])
            ->assertNotified('Hashtag renamed.');

        self::assertSame('#grammar', $tag->fresh()->content);
    }

    public function test_rename_that_collides_after_normalisation_is_refused(): void
    {
        $this->hashtag('#jlpt');
        $tag = $this->hashtag('#n5');

        Livewire::test(ListHashtags::class)
            ->callTableAction('rename', $tag, ['name' => '＃ＪＬＰＴ'])
            ->assertNotified("That name matches the existing tag '#jlpt'. Merge into it instead.");

        self::assertSame('#n5', $tag->fresh()->content);
        self::assertSame(0, AdminActivity::query()->count());
    }

    public function test_merge_moves_links_retires_duplicates_and_deletes_the_source(): void
    {
        $target = $this->hashtag('#jlpt');
        $source = $this->hashtag('#JLPT');
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
        $popular = $this->hashtag('#kanji');
        $rare = $this->hashtag('#Kanji');
        foreach (Article::factory()->count(2)->create(['user_id' => $this->author->id]) as $article) {
            $this->link($popular, ObjectTemplateType::ARTICLE, $article);
        }

        Livewire::test(ListHashtags::class)
            ->callTableBulkAction('mergeSelected', [$rare, $popular])
            ->assertNotified('1 merged, 0 failed');

        self::assertNotNull($popular->fresh());
        self::assertNull($rare->fresh());
    }

    public function test_new_tags_reuse_an_existing_variant(): void
    {
        $existing = $this->hashtag('#jlpt');

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
