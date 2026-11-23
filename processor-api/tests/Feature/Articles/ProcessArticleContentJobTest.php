<?php

declare(strict_types=1);

namespace Tests\Feature\Articles;

use App\Application\Articles\Jobs\ProcessArticleContentJob;
use App\Application\JapaneseMaterial\Words\Services\WordExtractionServiceInterface;
use App\Application\Processing\Events\ProcessingStatusUpdated;
use App\Application\Processing\Services\ProcessingStateServiceInterface;
use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingStatus;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\ProcessingState;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

/**
 * Issue #257 (ADR 0001): one job computes kanji, words and JLPT counters for an article and
 * persists them atomically, guarded by the content version it was queued for.
 */
class ProcessArticleContentJobTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    public function test_success_syncs_kanji_words_and_counters_and_completes_with_both_counts(): void
    {
        $article = $this->createArticle(titleJp: '学校', contentJp: '水を飲みます。日本語の本文です。');
        $water = $this->createKanji('水', '5');
        $school = $this->createWord('学校');
        $this->createWord('古い');
        DB::table('article_word')->insert(['article_id' => $article->id, 'word_id' => $this->createWord('要らない')]);
        $this->openRow($article);

        $this->runJob($article);

        $this->assertSame([$water], $this->pivot('article_kanji', 'kanji_id', $article));
        $this->assertSame([$school], $this->pivot('article_word', 'word_id', $article), 'Previous word attachments are replaced.');
        $article->refresh();
        $this->assertSame(1, $article->n5);
        $this->assertSame(0, $article->uncommon);

        $row = $this->row($article);
        $this->assertSame(ProcessingStatus::COMPLETED, $row->status);
        $this->assertSame(1, $row->attempt);
        $this->assertSame(['kanji_count' => 1, 'word_count' => 1], $row->metadata);
        $this->assertNotNull($row->started_at);
        $this->assertNotNull($row->finished_at);
    }

    public function test_empty_extraction_clears_attachments_and_zeroes_counters(): void
    {
        $article = $this->createArticle(titleJp: '学校', contentJp: '水を飲みます。日本語の本文です。');
        $this->createKanji('水', '5');
        $this->createWord('学校');
        $this->openRow($article);
        $this->runJob($article);
        $this->assertNotSame([], $this->pivot('article_kanji', 'kanji_id', $article));

        $article->update(['title_jp' => 'ひらがな', 'content_jp' => 'ひらがなだけのほんぶんです。', 'content_version' => 2]);
        $this->openRow($article, version: 2);
        $this->runJob($article, version: 2);

        $this->assertSame([], $this->pivot('article_kanji', 'kanji_id', $article));
        $this->assertSame([], $this->pivot('article_word', 'word_id', $article));
        $article->refresh();
        $this->assertSame([0, 0, 0, 0, 0, 0], [$article->n1, $article->n2, $article->n3, $article->n4, $article->n5, $article->uncommon]);
        $this->assertSame(['kanji_count' => 0, 'word_count' => 0], $this->row($article)->metadata);
    }

    public function test_stale_content_version_marks_the_row_superseded_and_changes_nothing(): void
    {
        $article = $this->createArticle(titleJp: '学校', contentJp: '水を飲みます。日本語の本文です。');
        $this->createKanji('水', '5');
        $this->createWord('学校');
        $article->update(['content_version' => 3]);
        $this->openRow($article, version: 3);

        Event::fake([ProcessingStatusUpdated::class]);

        $this->runJob($article, version: 2);

        $this->assertSame([], $this->pivot('article_kanji', 'kanji_id', $article));
        $this->assertSame([], $this->pivot('article_word', 'word_id', $article));
        $this->assertSame(0, $article->refresh()->n5);

        $row = $this->row($article);
        // The row belongs to version 3, which is still pending; the stale run must not touch it.
        $this->assertSame(ProcessingStatus::PENDING, $row->status);
        $this->assertSame(3, $row->content_version);
        Event::assertNotDispatched(ProcessingStatusUpdated::class);
    }

    public function test_stale_version_supersedes_its_own_in_flight_row(): void
    {
        $article = $this->createArticle(titleJp: '学校', contentJp: '水を飲みます。日本語の本文です。');
        $this->openRow($article, version: 1);
        $article->update(['content_version' => 2]); // bumped after this run was queued, before the row was reset

        $this->runJob($article, version: 1);

        $row = $this->row($article);
        $this->assertSame(ProcessingStatus::SUPERSEDED, $row->status);
        $this->assertNotNull($row->finished_at);
    }

    public function test_failure_during_kanji_stage_records_stage_and_rethrows(): void
    {
        $article = $this->createArticle(titleJp: '学校', contentJp: '水を飲みます。日本語の本文です。');
        $this->openRow($article);

        $this->app->bind(\App\Application\JapaneseMaterial\Kanjis\Services\KanjiExtractionService::class, function () {
            $mock = Mockery::mock(\App\Application\JapaneseMaterial\Kanjis\Services\KanjiExtractionService::class);
            $mock->shouldReceive('extractUniqueKanjis')->andThrow(new RuntimeException("kanji\n  service down"));

            return $mock;
        });

        try {
            $this->runJob($article);
            $this->fail('Expected the job to rethrow.');
        } catch (RuntimeException $exception) {
            $this->assertSame("kanji\n  service down", $exception->getMessage());
        }

        $row = $this->row($article);
        $this->assertSame(ProcessingStatus::FAILED, $row->status);
        $this->assertSame(ProcessArticleContentJob::STAGE_KANJI, $row->error_code);
        $this->assertSame('kanji service down', $row->error_message);
        $this->assertSame(ProcessArticleContentJob::STAGE_KANJI, $row->metadata['stage']);
        $this->assertSame(RuntimeException::class, $row->metadata['exception']);
    }

    public function test_failure_during_words_stage_records_stage_and_leaves_pivots_untouched(): void
    {
        $article = $this->createArticle(titleJp: '学校', contentJp: '水を飲みます。日本語の本文です。');
        $this->createKanji('水', '5');
        $this->openRow($article);

        $words = Mockery::mock(WordExtractionServiceInterface::class);
        $words->shouldReceive('extractWordIds')->andThrow(new RuntimeException('dictionary unavailable'));
        $this->app->instance(WordExtractionServiceInterface::class, $words);

        $this->expectException(RuntimeException::class);

        try {
            $this->runJob($article);
        } finally {
            $row = $this->row($article);
            $this->assertSame(ProcessingStatus::FAILED, $row->status);
            $this->assertSame(ProcessArticleContentJob::STAGE_WORDS, $row->error_code);
            $this->assertSame([], $this->pivot('article_kanji', 'kanji_id', $article), 'Nothing is persisted unless every stage succeeds.');
            $this->assertSame(0, $article->refresh()->n5);
        }
    }

    public function test_missing_article_fails_the_row_and_throws(): void
    {
        $uuid = (string) Str::uuid();
        ProcessingState::create([
            'entity_type' => 'article',
            'entity_id' => $uuid,
            'task_type' => 'article_content_processing',
            'status' => ProcessingStatus::PENDING,
            'content_version' => 1,
        ]);

        $this->expectException(RuntimeException::class);

        try {
            dispatch_sync(new ProcessArticleContentJob($uuid, 1));
        } finally {
            $row = ProcessingState::query()->where('entity_id', $uuid)->firstOrFail();
            $this->assertSame(ProcessingStatus::FAILED, $row->status);
            $this->assertSame('article_not_found', $row->error_code);
        }
    }

    public function test_failed_hook_only_touches_a_non_terminal_row(): void
    {
        $article = $this->createArticle(titleJp: '学校', contentJp: '水を飲みます。日本語の本文です。');
        $this->openRow($article);
        $job = new ProcessArticleContentJob($article->uuid, 1);

        $job->failed(new RuntimeException('killed by timeout'));
        $row = $this->row($article);
        $this->assertSame(ProcessingStatus::FAILED, $row->status);
        $this->assertSame('job_failed', $row->error_code);
        $this->assertSame('killed by timeout', $row->error_message);

        $this->openRow($article);
        $this->runJob($article);
        $job->failed(new RuntimeException('late signal'));
        $this->assertSame(ProcessingStatus::COMPLETED, $this->row($article)->status, 'A completed row is never rewritten by failed().');
    }

    public function test_job_is_serialised_around_the_article_and_queued_after_commit(): void
    {
        $job = new ProcessArticleContentJob('a1a1a1a1-0000-4000-8000-000000000001', 1);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueueAfterCommit::class, $job);
        $this->assertSame(3, $job->tries);
        $this->assertSame([10, 30, 90], $job->backoff);
        $this->assertSame(120, $job->timeout);

        $middleware = $job->middleware();
        $this->assertCount(1, $middleware);
        $this->assertInstanceOf(WithoutOverlapping::class, $middleware[0]);
        $this->assertSame('a1a1a1a1-0000-4000-8000-000000000001', $middleware[0]->key);
        $this->assertSame(30, $middleware[0]->releaseAfter);
        $this->assertSame(180, $middleware[0]->expiresAfter);
    }

    private function runJob(PersistenceArticle $article, int $version = 1): void
    {
        dispatch_sync(new ProcessArticleContentJob($article->uuid, $version));
    }

    private function openRow(PersistenceArticle $article, int $version = 1): void
    {
        app(ProcessingStateServiceInterface::class)->startOrReset(
            ProcessingEntityType::Article,
            EntityId::from($article->uuid),
            ProcessingTaskType::ArticleContentProcessing,
            $version,
        );
    }

    private function row(PersistenceArticle $article): ProcessingState
    {
        return ProcessingState::query()->where('entity_id', $article->uuid)->firstOrFail();
    }

    /**
     * @return list<int>
     */
    private function pivot(string $table, string $column, PersistenceArticle $article): array
    {
        return DB::table($table)->where('article_id', $article->id)->orderBy($column)->pluck($column)->map(fn ($v) => (int) $v)->all();
    }

    private function createArticle(string $titleJp, string $contentJp): PersistenceArticle
    {
        return PersistenceArticle::factory()->byUser(User::factory()->create())->create([
            'title_jp' => $titleJp,
            'content_jp' => $contentJp,
        ]);
    }

    private function createKanji(string $kanji, string $jlpt): int
    {
        return DB::table('japanese_kanji_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'kanji' => $kanji,
            'onyomi' => '-',
            'kunyomi' => '-',
            'meaning' => 'test',
            'nanori' => '-',
            'grade' => '1',
            'stroke_count' => '4',
            'jlpt' => $jlpt,
            'frequency' => '1',
            'radicals' => '-',
            'radical_parts' => $kanji,
        ]);
    }

    private function createWord(string $word): int
    {
        return DB::table('japanese_word_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'entry_sequence' => (string) random_int(100000, 999999),
            'word' => $word,
            'furigana' => $word,
            'jlpt' => 'N5',
            'word_type' => 'noun',
            'word_k_ele' => $word,
            'furigana_r_ele' => $word,
            'sense' => 'test',
        ]);
    }
}
