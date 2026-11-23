<?php

declare(strict_types=1);

namespace Tests\Feature\Engagement;

use App\Application\Engagement\Interfaces\Readers\EntityEngagementReaderInterface;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\HashtagEntity;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\CreatesHashtags;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

final class DatabaseEntityEngagementReaderTest extends TestCase
{
    use CreatesHashtags;
    use RefreshDatabase;
    use SeedsBaselineData;

    private User $user;

    private Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
        $this->user = User::factory()->create();
        $this->article = Article::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_counts_each_kind_of_engagement_for_the_entity_only(): void
    {
        $this->engage($this->article->id, ObjectTemplateType::ARTICLE, views: 3, likes: 2, comments: 1, downloads: 4);
        $this->engage($this->article->id, ObjectTemplateType::POST, views: 5, likes: 5, comments: 5, downloads: 5);

        $counts = app(EntityEngagementReaderInterface::class)->counts(ObjectTemplateType::ARTICLE, $this->article->id);

        self::assertSame([3, 2, 1, 4], [$counts->views, $counts->likes, $counts->comments, $counts->downloads]);
    }

    public function test_an_entity_without_engagement_counts_zero(): void
    {
        $counts = app(EntityEngagementReaderInterface::class)->counts(ObjectTemplateType::ARTICLE, $this->article->id);

        self::assertSame([0, 0, 0, 0], [$counts->views, $counts->likes, $counts->comments, $counts->downloads]);
    }

    public function test_hashtag_names_are_live_links_only_in_alphabetical_order(): void
    {
        $this->link('#reading');
        $this->link('#grammar');
        $this->link('#removed')->delete();

        self::assertSame(
            ['#grammar', '#reading'],
            app(EntityEngagementReaderInterface::class)->hashtagNames(ObjectTemplateType::ARTICLE, $this->article->id),
        );
    }

    private function engage(int $entityId, ObjectTemplateType $type, int $views, int $likes, int $comments, int $downloads): void
    {
        $row = ['template_id' => $type->getLegacyId(), 'real_object_id' => $entityId, 'user_id' => $this->user->id];

        foreach (range(1, $views) as $_) {
            DB::table('views')->insert($row);
        }

        // One like per user and target, so each like needs its own user.
        foreach (range(1, $likes) as $_) {
            DB::table('likes')->insert([...$row, 'user_id' => User::factory()->create()->id, 'value' => 1]);
        }

        foreach (range(1, $comments) as $_) {
            DB::table('comments')->insert([...$row, 'uuid' => (string) Str::uuid(), 'content' => 'A comment']);
        }

        foreach (range(1, $downloads) as $_) {
            DB::table('downloads')->insert($row);
        }
    }

    private function link(string $tag): HashtagEntity
    {
        return HashtagEntity::query()->create([
            'hashtag_id' => $this->hashtag($tag)->id,
            'entity_type_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'entity_id' => $this->article->id,
            'user_id' => $this->user->id,
        ]);
    }
}
