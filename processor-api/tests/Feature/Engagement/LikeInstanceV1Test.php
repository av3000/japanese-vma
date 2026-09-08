<?php

declare(strict_types=1);

namespace Tests\Feature\Engagement;

use App\Application\Engagement\Interfaces\Repositories\LikeRepositoryInterface;
use App\Domain\Engagement\DTOs\LikeCreateDTO;
use App\Domain\Engagement\Enums\LikeTargetType;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\Catalogue as PersistenceCatalogue;
use App\Infrastructure\Persistence\Models\Comment as PersistenceComment;
use App\Infrastructure\Persistence\Models\Like as PersistenceLike;
use App\Infrastructure\Persistence\Models\Post as PersistencePost;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

class LikeInstanceV1Test extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    private const ENDPOINT = '/api/v1/like-instance';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    // ------------------------------------------------------------------
    // Contract
    // ------------------------------------------------------------------

    public function test_liking_returns_the_typed_state_and_total(): void
    {
        $article = PersistenceArticle::factory()->create();
        $viewer = $this->actingAsUser();

        $response = $this->postJson(self::ENDPOINT, [
            'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'real_object_id' => $article->id,
        ]);

        $response->assertOk()
            ->assertExactJson(['is_liked' => true, 'likes_count' => 1]);

        self::assertIsBool($response->json('is_liked'));
        self::assertIsInt($response->json('likes_count'));

        self::assertSame(1, $this->likeRows($viewer, LikeTargetType::ARTICLE, $article->id));
    }

    public function test_response_is_not_wrapped_in_the_legacy_success_envelope(): void
    {
        $article = PersistenceArticle::factory()->create();
        $this->actingAsUser();

        $this->postJson(self::ENDPOINT, [
            'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'real_object_id' => $article->id,
        ])
            ->assertOk()
            ->assertJsonMissingPath('success')
            ->assertJsonMissingPath('data')
            ->assertJsonMissingPath('like')
            ->assertJsonMissingPath('likeValues');
    }

    // ------------------------------------------------------------------
    // Both directions, all four targets
    // ------------------------------------------------------------------

    public function test_both_toggle_directions_work_for_every_supported_target(): void
    {
        $viewer = $this->actingAsUser();

        foreach ($this->everySupportedTarget() as [$target, $entityId]) {
            $label = $target->name;
            $payload = ['template_id' => $target->legacyId(), 'real_object_id' => $entityId];

            $this->postJson(self::ENDPOINT, $payload)
                ->assertOk()
                ->assertExactJson(['is_liked' => true, 'likes_count' => 1]);

            self::assertSame(1, $this->likeRows($viewer, $target, $entityId), "{$label} was not liked");

            $this->postJson(self::ENDPOINT, $payload)
                ->assertOk()
                ->assertExactJson(['is_liked' => false, 'likes_count' => 0]);

            self::assertSame(0, $this->likeRows($viewer, $target, $entityId), "{$label} like was not removed");
        }
    }

    public function test_a_like_persisted_before_this_request_toggles_off(): void
    {
        $article = PersistenceArticle::factory()->create();
        $viewer = $this->actingAsUser();
        $this->seedLike($viewer, LikeTargetType::ARTICLE, $article->id);

        $this->postJson(self::ENDPOINT, [
            'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'real_object_id' => $article->id,
        ])
            ->assertOk()
            ->assertExactJson(['is_liked' => false, 'likes_count' => 0]);
    }

    public function test_likes_count_is_the_target_total_not_the_viewers_own(): void
    {
        $article = PersistenceArticle::factory()->create();
        $this->seedLike($this->createUser(), LikeTargetType::ARTICLE, $article->id);
        $this->seedLike($this->createUser(), LikeTargetType::ARTICLE, $article->id);

        $this->actingAsUser();

        $this->postJson(self::ENDPOINT, [
            'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'real_object_id' => $article->id,
        ])
            ->assertOk()
            ->assertExactJson(['is_liked' => true, 'likes_count' => 3]);
    }

    // ------------------------------------------------------------------
    // Uniqueness
    // ------------------------------------------------------------------

    public function test_a_duplicate_like_row_cannot_be_inserted(): void
    {
        $article = PersistenceArticle::factory()->create();
        $viewer = $this->createUser();

        /** @var LikeRepositoryInterface $repository */
        $repository = app(LikeRepositoryInterface::class);

        $payload = new LikeCreateDTO(
            userId: $viewer->id,
            templateId: LikeTargetType::ARTICLE->legacyId(),
            realObjectId: $article->id,
        );

        self::assertTrue($repository->createIfAbsent($payload), 'the first insert should win');
        self::assertFalse(
            $repository->createIfAbsent($payload),
            'the unique index should reject the second insert instead of doubling the count'
        );

        self::assertSame(1, $this->likeRows($viewer, LikeTargetType::ARTICLE, $article->id));
    }

    public function test_repeated_toggles_never_leave_more_than_one_row(): void
    {
        $article = PersistenceArticle::factory()->create();
        $viewer = $this->actingAsUser();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(self::ENDPOINT, [
                'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
                'real_object_id' => $article->id,
            ])->assertOk();
        }

        self::assertSame(1, $this->likeRows($viewer, LikeTargetType::ARTICLE, $article->id));
    }

    // ------------------------------------------------------------------
    // Authentication and validation
    // ------------------------------------------------------------------

    public function test_an_anonymous_request_is_rejected(): void
    {
        $article = PersistenceArticle::factory()->create();

        $this->postJson(self::ENDPOINT, [
            'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'real_object_id' => $article->id,
        ])->assertUnauthorized();

        self::assertSame(0, PersistenceLike::query()->count());
    }

    public function test_a_template_that_has_no_likeable_target_is_rejected(): void
    {
        $this->actingAsUser();

        $this->postJson(self::ENDPOINT, [
            // Kanji is a valid ObjectTemplateType but not a like target.
            'template_id' => ObjectTemplateType::KANJI->getLegacyId(),
            'real_object_id' => 1,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('template_id');
    }

    public function test_a_template_outside_the_enum_is_rejected(): void
    {
        $this->actingAsUser();

        $this->postJson(self::ENDPOINT, ['template_id' => 999, 'real_object_id' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('template_id');
    }

    public function test_a_non_positive_object_id_is_rejected(): void
    {
        $this->actingAsUser();

        $this->postJson(self::ENDPOINT, [
            'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'real_object_id' => 0,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('real_object_id');
    }

    public function test_a_missing_payload_is_rejected(): void
    {
        $this->actingAsUser();

        $this->postJson(self::ENDPOINT, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['template_id', 'real_object_id']);
    }

    // ------------------------------------------------------------------
    // Target existence and visibility
    // ------------------------------------------------------------------

    public function test_a_target_that_does_not_exist_returns_not_found(): void
    {
        $this->actingAsUser();

        $this->postJson(self::ENDPOINT, [
            'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'real_object_id' => 999999,
        ])->assertNotFound();

        self::assertSame(0, PersistenceLike::query()->count());
    }

    public function test_another_users_private_article_is_indistinguishable_from_a_missing_one(): void
    {
        $private = PersistenceArticle::factory()->asPrivate()->byUser($this->createUser())->create();
        $this->actingAsUser();

        $hidden = $this->postJson(self::ENDPOINT, [
            'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'real_object_id' => $private->id,
        ])->assertNotFound();

        $missing = $this->postJson(self::ENDPOINT, [
            'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'real_object_id' => 999999,
        ])->assertNotFound();

        self::assertSame(
            $this->comparableProblem($missing->json()),
            $this->comparableProblem($hidden->json()),
            'a private target must not be distinguishable from a missing one'
        );

        self::assertSame(0, PersistenceLike::query()->count());
    }

    public function test_a_user_can_like_their_own_private_article(): void
    {
        $viewer = $this->createUser();
        $private = PersistenceArticle::factory()->asPrivate()->byUser($viewer)->create();
        $this->actingAsUser($viewer);

        $this->postJson(self::ENDPOINT, [
            'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'real_object_id' => $private->id,
        ])
            ->assertOk()
            ->assertExactJson(['is_liked' => true, 'likes_count' => 1]);
    }

    public function test_another_users_private_catalogue_cannot_be_liked(): void
    {
        $private = PersistenceCatalogue::factory()->asPrivate()->byUser($this->createUser())->create();
        $this->actingAsUser();

        $this->postJson(self::ENDPOINT, [
            'template_id' => ObjectTemplateType::LIST->getLegacyId(),
            'real_object_id' => $private->id,
        ])->assertNotFound();

        self::assertSame(0, PersistenceLike::query()->count());
    }

    public function test_a_user_can_like_their_own_private_catalogue(): void
    {
        $viewer = $this->createUser();
        $private = PersistenceCatalogue::factory()->asPrivate()->byUser($viewer)->create();
        $this->actingAsUser($viewer);

        $this->postJson(self::ENDPOINT, [
            'template_id' => ObjectTemplateType::LIST->getLegacyId(),
            'real_object_id' => $private->id,
        ])->assertOk();
    }

    public function test_a_comment_inherits_the_visibility_of_its_parent(): void
    {
        $author = $this->createUser();
        $private = PersistenceArticle::factory()->asPrivate()->byUser($author)->create();
        $hiddenComment = $this->createComment($author, ObjectTemplateType::ARTICLE, $private->id, $private->uuid);

        $public = PersistenceArticle::factory()->create();
        $visibleComment = $this->createComment($author, ObjectTemplateType::ARTICLE, $public->id, $public->uuid);

        $this->actingAsUser();

        $this->postJson(self::ENDPOINT, [
            'template_id' => ObjectTemplateType::COMMENT->getLegacyId(),
            'real_object_id' => $hiddenComment->id,
        ])->assertNotFound();

        $this->postJson(self::ENDPOINT, [
            'template_id' => ObjectTemplateType::COMMENT->getLegacyId(),
            'real_object_id' => $visibleComment->id,
        ])->assertOk();
    }

    public function test_a_comment_that_does_not_exist_returns_not_found(): void
    {
        $this->actingAsUser();

        $this->postJson(self::ENDPOINT, [
            'template_id' => ObjectTemplateType::COMMENT->getLegacyId(),
            'real_object_id' => 999999,
        ])->assertNotFound();
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * @return array<int, array{0: LikeTargetType, 1: int}>
     */
    private function everySupportedTarget(): array
    {
        $author = $this->createUser();
        $article = PersistenceArticle::factory()->create();

        return [
            [LikeTargetType::ARTICLE, $article->id],
            [LikeTargetType::CATALOGUE, PersistenceCatalogue::factory()->create()->id],
            [LikeTargetType::POST, PersistencePost::factory()->create()->id],
            [
                LikeTargetType::COMMENT,
                $this->createComment($author, ObjectTemplateType::ARTICLE, $article->id, $article->uuid)->id,
            ],
        ];
    }

    private function createUser(): User
    {
        return User::factory()->create();
    }

    private function actingAsUser(?User $user = null): User
    {
        $user ??= $this->createUser();

        Passport::actingAs($user, ['*'], 'api');

        return $user;
    }

    private function createComment(
        User $author,
        ObjectTemplateType $parentType,
        int $parentId,
        string $parentUuid
    ): PersistenceComment {
        return PersistenceComment::create([
            'template_id' => $parentType->getLegacyId(),
            'real_object_id' => $parentId,
            'real_object_uuid' => $parentUuid,
            'entity_type_uuid' => $parentType->value,
            'user_id' => $author->id,
            'parent_comment_id' => null,
            'content' => 'Comment used as a like target.',
            'uuid' => (string) Str::uuid(),
        ]);
    }

    private function seedLike(User $user, LikeTargetType $target, int $entityId): void
    {
        PersistenceLike::create([
            'user_id' => $user->id,
            'template_id' => $target->legacyId(),
            'real_object_id' => $entityId,
            'value' => true,
        ]);
    }

    private function likeRows(User $user, LikeTargetType $target, int $entityId): int
    {
        return DB::table('likes')
            ->where('user_id', $user->id)
            ->where('template_id', $target->legacyId())
            ->where('real_object_id', $entityId)
            ->count();
    }

    /**
     * Problem responses carry a wall-clock `timestamp`, which is never a disclosure.
     *
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     */
    private function comparableProblem(array $body): array
    {
        unset($body['timestamp']);

        return $body;
    }
}
