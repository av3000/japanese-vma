<?php

declare(strict_types=1);

namespace Tests\Feature\Articles;

use App\Application\Articles\Jobs\ProcessArticleKanjisJob;
use App\Application\JapaneseMaterial\Kanjis\Services\KanjiAttachmentService;
use App\Application\JapaneseMaterial\Kanjis\Services\KanjiExtractionService;
use App\Application\LastOperations\Services\LastOperationService;
use App\Domain\Shared\Enums\LastOperationStatus;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

/**
 * Issue #250 (audit findings F-07, F-17, F-18): the v1 kanji job must write the JLPT counters
 * the cards render, and a re-run must replace, not merge, the previous result.
 */
class ProcessArticleKanjisJobTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    public function test_job_attaches_known_kanji_and_persists_jlpt_counters(): void
    {
        $article = $this->createArticle('水と山と鬱。日本語の本文です。');
        $water = $this->createKanji('水', jlpt: '5');
        $mountain = $this->createKanji('山', jlpt: '5');
        $gloom = $this->createKanji('鬱', jlpt: '-');
        $this->createKanji('本', jlpt: '4'); // present in content but the extractor must not double count
        $this->createKanji('学', jlpt: '3'); // not in content at all

        $this->runJob($article);

        $attached = DB::table('article_kanji')->where('article_id', $article->id)->pluck('kanji_id')->sort()->values()->all();
        $this->assertContains($water, $attached);
        $this->assertContains($mountain, $attached);
        $this->assertContains($gloom, $attached);

        $article->refresh();
        $this->assertSame(0, $article->n1);
        $this->assertSame(0, $article->n2);
        $this->assertSame(0, $article->n3);
        $this->assertSame(1, $article->n4, '本 (N4) appears in 本文 and 日本語');
        $this->assertSame(2, $article->n5, '水 and 山');
        $this->assertSame(1, $article->uncommon, '鬱 has no JLPT level');

        $this->assertDatabaseHas('last_operations', [
            'processable_id' => $article->uuid,
            'task_type' => ProcessArticleKanjisJob::TASK_TYPE,
            'status' => LastOperationStatus::COMPLETED->value,
        ]);
    }

    public function test_rerun_with_no_kanji_clears_the_pivot_and_zeroes_the_counters(): void
    {
        $article = $this->createArticle('水を飲みます。日本語の本文です。');
        $this->createKanji('水', jlpt: '5');
        $this->runJob($article);
        $this->assertGreaterThan(0, DB::table('article_kanji')->where('article_id', $article->id)->count());
        $this->assertSame(1, $article->refresh()->n5);

        // Content edited to kana only: the previous attachments and counters must not survive.
        $article->update(['content_jp' => 'ひらがなだけのほんぶんです。']);
        $this->runJob($article->refresh());

        $this->assertSame(0, DB::table('article_kanji')->where('article_id', $article->id)->count());
        $article->refresh();
        $this->assertSame([0, 0, 0, 0, 0, 0], [$article->n1, $article->n2, $article->n3, $article->n4, $article->n5, $article->uncommon]);
    }

    public function test_rerun_replaces_the_previous_set_rather_than_merging(): void
    {
        $article = $this->createArticle('水を飲みます。日本語の本文です。');
        $water = $this->createKanji('水', jlpt: '5');
        $mountain = $this->createKanji('山', jlpt: '5');
        $this->runJob($article);

        $article->update(['content_jp' => '山に登ります。日本語の本文です。']);
        $this->runJob($article->refresh());

        $attached = DB::table('article_kanji')->where('article_id', $article->id)->pluck('kanji_id')->all();
        $this->assertContains($mountain, $attached);
        $this->assertNotContains($water, $attached);
        $this->assertSame(1, $article->refresh()->n5);
    }

    private function runJob(PersistenceArticle $article): void
    {
        (new ProcessArticleKanjisJob($article->uuid, $article->content_jp))->handle(
            app(KanjiExtractionService::class),
            app(KanjiAttachmentService::class),
            app(LastOperationService::class),
        );
    }

    private function createArticle(string $contentJp): PersistenceArticle
    {
        return PersistenceArticle::factory()->byUser(User::factory()->create())->create([
            'title_jp' => 'テスト',
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
}
