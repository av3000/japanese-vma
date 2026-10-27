<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Posts;

use App\Application\Community\Posts\Actions\DeletePostAction;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Infrastructure\Persistence\Models\HashtagEntity;
use App\Infrastructure\Persistence\Models\Post;
use App\Infrastructure\Persistence\Models\Uniquehashtag;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

/**
 * The one place a post and its dependents are removed, for both the author's delete and a
 * moderator's removal.
 */
final class DeletePostActionTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    public function test_the_post_and_every_dependent_row_are_removed(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);
        $other = Post::factory()->create(['user_id' => $author->id]);
        $this->attachDependents($post);
        $this->attachDependents($other);

        app(DeletePostAction::class)->execute($post->id);

        self::assertNull($post->fresh());
        $template = ObjectTemplateType::POST->getLegacyId();

        foreach (['views', 'likes', 'downloads', 'comments'] as $table) {
            self::assertSame(0, DB::table($table)->where('template_id', $template)->where('real_object_id', $post->id)->count(), $table);
            self::assertSame(1, DB::table($table)->where('template_id', $template)->where('real_object_id', $other->id)->count(), $table);
        }

        self::assertSame(0, HashtagEntity::query()->where('entity_type_id', $template)->where('entity_id', $post->id)->count());
        self::assertSame(1, HashtagEntity::query()->where('entity_type_id', $template)->where('entity_id', $other->id)->count());
        self::assertNotNull($other->fresh());
    }

    private function attachDependents(Post $post): void
    {
        $row = [
            'user_id' => $post->user_id,
            'template_id' => ObjectTemplateType::POST->getLegacyId(),
            'real_object_id' => $post->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('views')->insert([...$row, 'user_ip' => '127.0.0.1']);
        DB::table('likes')->insert([...$row, 'value' => 1]);
        DB::table('downloads')->insert($row);
        DB::table('comments')->insert([...$row, 'uuid' => (string) Str::uuid(), 'content' => 'A comment']);
        HashtagEntity::query()->create([
            'hashtag_id' => Uniquehashtag::query()->firstOrCreate(['content' => '#jlpt'])->id,
            'entity_type_id' => ObjectTemplateType::POST->getLegacyId(),
            'entity_id' => $post->id,
            'user_id' => $post->user_id,
        ]);
    }
}
