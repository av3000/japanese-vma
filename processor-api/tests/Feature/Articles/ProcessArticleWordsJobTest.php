<?php

declare(strict_types=1);

namespace Tests\Feature\Articles;

use App\Application\Articles\Jobs\ProcessArticleWordsJob;
use App\Application\JapaneseMaterial\Words\Services\WordAttachmentService;
use App\Application\JapaneseMaterial\Words\Services\WordExtractionServiceInterface;
use App\Application\LastOperations\Services\LastOperationService;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\LastOperationStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

class ProcessArticleWordsJobTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    public function test_process_article_words_job_attaches_words_replaces_old_words_and_records_completion(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user, [
            'title_jp' => '学校',
            'content_jp' => '勉強します',
        ]);
        $oldWordId = $this->createWord('古い', 'ふるい');
        $schoolWordId = $this->createWord('学校', 'がっこう');
        $studyWordId = $this->createWord('勉強', 'べんきょう');

        DB::table('article_word')->insert([
            'article_id' => $article->id,
            'word_id' => $oldWordId,
        ]);

        (new ProcessArticleWordsJob(
            articleUuid: $article->uuid,
            articleText: $article->title_jp.$article->content_jp,
        ))->handle(
            app(WordExtractionServiceInterface::class),
            app(WordAttachmentService::class),
            app(LastOperationService::class),
        );

        $this->assertDatabaseMissing('article_word', [
            'article_id' => $article->id,
            'word_id' => $oldWordId,
        ]);
        $this->assertDatabaseHas('article_word', [
            'article_id' => $article->id,
            'word_id' => $schoolWordId,
        ]);
        $this->assertDatabaseHas('article_word', [
            'article_id' => $article->id,
            'word_id' => $studyWordId,
        ]);
        $this->assertDatabaseHas('last_operations', [
            'processable_id' => $article->uuid,
            'task_type' => 'words_extraction',
            'status' => LastOperationStatus::COMPLETED->value,
        ]);

        $operation = DB::table('last_operations')
            ->where('processable_id', $article->uuid)
            ->where('task_type', 'words_extraction')
            ->first();

        $metadata = json_decode($operation->metadata, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(2, $metadata['word_count']);
        $this->assertSame('Attached 2 words.', $metadata['message']);
    }

    public function test_process_article_words_job_records_failed_status_when_article_does_not_exist(): void
    {
        $missingArticleUuid = (string) Str::uuid();
        $this->createWord('学校', 'がっこう');

        try {
            (new ProcessArticleWordsJob(
                articleUuid: $missingArticleUuid,
                articleText: '学校',
            ))->handle(
                app(WordExtractionServiceInterface::class),
                app(WordAttachmentService::class),
                app(LastOperationService::class),
            );

            $this->fail('Expected the word processing job to throw when the article cannot be resolved.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Article not found', $exception->getMessage());
        }

        $this->assertDatabaseHas('last_operations', [
            'processable_id' => $missingArticleUuid,
            'task_type' => 'words_extraction',
            'status' => LastOperationStatus::FAILED->value,
        ]);
    }

    private function createUser(array $overrides = []): User
    {
        return User::factory()->create($overrides);
    }

    private function createArticle(User $user, array $overrides = []): PersistenceArticle
    {
        return PersistenceArticle::factory()->byUser($user)->create(array_merge([
            'title_jp' => 'Japanese title',
            'title_en' => 'English title',
            'content_jp' => 'Japanese content text.',
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
        ], $overrides));
    }

    private function createWord(string $word, string $furigana): int
    {
        return DB::table('japanese_word_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'entry_sequence' => (string) random_int(100000, 999999),
            'word' => $word,
            'furigana' => $furigana,
            'jlpt' => 'N5',
            'word_type' => 'noun',
            'word_k_ele' => $word,
            'furigana_r_ele' => $furigana,
            'sense' => 'study',
        ]);
    }
}
