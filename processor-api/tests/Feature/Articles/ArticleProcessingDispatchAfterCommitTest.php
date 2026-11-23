<?php

declare(strict_types=1);

namespace Tests\Feature\Articles;

use App\Application\Articles\Jobs\ProcessArticleContentJob;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

/**
 * Proves that article content-processing jobs are not visible to a worker until the
 * surrounding database transaction commits (audit finding F-02, issue #244).
 *
 * `Bus::fake()` and `Queue::fake()` record a dispatch immediately and ignore `after_commit`,
 * so they cannot observe the deferral. This test uses the real `database` queue driver,
 * whose push goes through the same transaction-manager callback as the `redis` driver,
 * and counts rows in the `jobs` table. `DatabaseTruncation` is used instead of
 * `RefreshDatabase` because the latter wraps the whole test in a transaction that never
 * commits, which would keep the callback pending forever.
 */
class ArticleProcessingDispatchAfterCommitTest extends TestCase
{
    use DatabaseTruncation;
    use SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();

        config(['queue.default' => 'database']);
    }

    protected function tearDown(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        // DatabaseTruncation only truncates at the start of each test. Truncate again on the
        // way out so classes using RefreshDatabase, which skip migrate:fresh once the shared
        // migrated flag is set, do not inherit this class's committed rows.
        $this->truncateTablesForAllConnections();

        parent::tearDown();
    }

    public function test_create_dispatches_the_job_only_after_the_transaction_commits(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['*'], 'api');

        DB::beginTransaction();

        $response = $this->json('POST', '/api/v1/articles', [
            'title_jp' => '学校の話',
            'title_en' => 'School Story',
            'content_jp' => '学校で勉強します。日本語の本文です。',
            'content_en' => 'I study at school.',
            'source_link' => 'https://example.com/source',
            'publicity' => true,
        ]);

        $response->assertCreated();
        $this->assertSame(0, DB::table('jobs')->count(), 'Jobs must not be queued before the transaction commits.');

        DB::commit();

        $this->assertSame(
            [ProcessArticleContentJob::class],
            $this->queuedJobClasses(),
        );
    }

    public function test_update_dispatches_the_job_only_after_the_transaction_commits(): void
    {
        $user = User::factory()->create();
        $article = $this->createArticle($user);
        Passport::actingAs($user, ['*'], 'api');

        DB::beginTransaction();

        $this->json('PUT', "/api/v1/articles/{$article->uuid}", [
            'content_jp' => '新しい本文です。もう一度処理します。',
        ])->assertOk();

        $this->assertSame(0, DB::table('jobs')->count(), 'Jobs must not be queued before the transaction commits.');

        DB::commit();

        $this->assertSame(
            [ProcessArticleContentJob::class],
            $this->queuedJobClasses(),
        );
    }

    /**
     * The job implements ShouldQueueAfterCommit, so it is deferred even when the connection's
     * `after_commit` flag is off: the guarantee no longer rests on configuration alone.
     */
    public function test_job_is_deferred_even_with_after_commit_disabled_on_the_connection(): void
    {
        config(['queue.connections.database.after_commit' => false]);

        $user = User::factory()->create();
        Passport::actingAs($user, ['*'], 'api');

        DB::beginTransaction();

        $this->json('POST', '/api/v1/articles', [
            'title_jp' => '学校の話',
            'title_en' => 'School Story',
            'content_jp' => '学校で勉強します。日本語の本文です。',
            'content_en' => 'I study at school.',
            'source_link' => 'https://example.com/source',
            'publicity' => true,
        ])->assertCreated();

        $this->assertSame(0, DB::table('jobs')->count(), 'ShouldQueueAfterCommit defers regardless of the connection flag.');

        DB::commit();

        $this->assertSame([ProcessArticleContentJob::class], $this->queuedJobClasses());
    }

    /**
     * @return list<class-string>
     */
    private function queuedJobClasses(): array
    {
        return DB::table('jobs')
            ->orderBy('id')
            ->pluck('payload')
            ->map(fn (string $payload): string => json_decode($payload, true, 512, JSON_THROW_ON_ERROR)['displayName'])
            ->all();
    }

    private function createArticle(User $user): PersistenceArticle
    {
        return PersistenceArticle::create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'entity_type_uuid' => ObjectTemplateType::ARTICLE->value,
            'title_jp' => '日本語の題',
            'title_en' => 'English title',
            'content_jp' => '日本語の本文です。最初の内容です。',
            'content_en' => 'English content text.',
            'source_link' => 'https://example.com/source',
            'publicity' => PublicityStatus::PRIVATE,
            'status' => ArticleStatus::PENDING,
            'n1' => 0,
            'n2' => 0,
            'n3' => 0,
            'n4' => 0,
            'n5' => 0,
            'uncommon' => 0,
        ]);
    }
}
