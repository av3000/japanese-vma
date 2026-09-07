<?php

declare(strict_types=1);

namespace Tests\Feature\Comments;

use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Entity ids are only unique per template, so comment cleanup must be scoped by
 * template_id. Deleting one entity must never remove the comments of a
 * different entity type that happens to share the same numeric id.
 */
class DeleteCommentsByEntityTest extends TestCase
{
    use RefreshDatabase;

    private const SHARED_OBJECT_ID = 5;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => UserRole::COMMON->value, 'guard_name' => 'api']);
    }

    public function test_delete_by_entity_only_removes_comments_of_the_given_template(): void
    {
        $user = $this->createUser();
        $articleCommentId = $this->createComment(ObjectTemplateType::ARTICLE, $user->id, 'Article comment');
        $sentenceCommentId = $this->createComment(ObjectTemplateType::SENTENCE, $user->id, 'Sentence comment');
        $listCommentId = $this->createComment(ObjectTemplateType::LIST, $user->id, 'List comment');

        $this->repository()->deleteByEntity(
            self::SHARED_OBJECT_ID,
            ObjectTemplateType::ARTICLE->getLegacyId(),
        );

        $this->assertDatabaseMissing('comments', ['id' => $articleCommentId]);
        $this->assertDatabaseHas('comments', ['id' => $sentenceCommentId]);
        $this->assertDatabaseHas('comments', ['id' => $listCommentId]);
    }

    public function test_delete_by_entity_only_removes_likes_belonging_to_deleted_comments(): void
    {
        $user = $this->createUser();
        $articleCommentId = $this->createComment(ObjectTemplateType::ARTICLE, $user->id, 'Article comment');

        $this->createLike(ObjectTemplateType::COMMENT, $articleCommentId, $user->id);
        // Same numeric id, different template: belongs to an unrelated object.
        $this->createLike(ObjectTemplateType::ARTICLE, $articleCommentId, $user->id);

        $this->repository()->deleteByEntity(
            self::SHARED_OBJECT_ID,
            ObjectTemplateType::ARTICLE->getLegacyId(),
        );

        $this->assertDatabaseMissing('likes', [
            'real_object_id' => $articleCommentId,
            'template_id' => ObjectTemplateType::COMMENT->getLegacyId(),
        ]);
        $this->assertDatabaseHas('likes', [
            'real_object_id' => $articleCommentId,
            'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
        ]);
    }

    private function repository(): CommentRepositoryInterface
    {
        return app(CommentRepositoryInterface::class);
    }

    private function createUser(): User
    {
        return User::create([
            'name' => 'Comment Test User',
            'email' => Str::uuid().'@example.com',
            'password' => Hash::make('password'),
            'uuid' => (string) Str::uuid(),
        ]);
    }

    private function createComment(ObjectTemplateType $template, int $userId, string $content): int
    {
        return (int) DB::table('comments')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'template_id' => $template->getLegacyId(),
            'real_object_id' => self::SHARED_OBJECT_ID,
            'user_id' => $userId,
            'content' => $content,
        ]);
    }

    private function createLike(ObjectTemplateType $template, int $objectId, int $userId): void
    {
        DB::table('likes')->insert([
            'template_id' => $template->getLegacyId(),
            'real_object_id' => $objectId,
            'user_id' => $userId,
            'value' => true,
        ]);
    }
}
