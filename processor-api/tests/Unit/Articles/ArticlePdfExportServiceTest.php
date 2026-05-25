<?php

namespace Tests\Unit\Articles;

use App\Application\Articles\Services\ArticlePdfExportService;
use App\Application\Engagement\Interfaces\Repositories\DownloadRepositoryInterface;
use App\Application\Pdf\PdfRendererInterface;
use App\Domain\Engagement\DTOs\DownloadCreateDTO;
use App\Domain\Engagement\DTOs\DownloadFilterDTO;
use App\Domain\Pdf\DTOs\PdfDocument;
use App\Domain\Pdf\DTOs\PdfRenderResult;
use App\Domain\Pdf\Enums\PdfDisposition;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ArticlePdfExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private FakePdfRenderer $pdfRenderer;

    private FakeDownloadRepository $downloadRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdfRenderer = new FakePdfRenderer;
        $this->downloadRepository = new FakeDownloadRepository;

        $this->app->instance(PdfRendererInterface::class, $this->pdfRenderer);
        $this->app->instance(DownloadRepositoryInterface::class, $this->downloadRepository);

        Role::firstOrCreate(['name' => 'common', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);

        DB::table('objecttemplates')->insert([
            'id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'title' => ObjectTemplateType::ARTICLE->getTitle(),
            'entity_type_uuid' => ObjectTemplateType::ARTICLE->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_kanjis_export_returns_rendered_pdf_and_records_download(): void
    {
        $viewer = $this->createUser();
        $article = $this->createArticle($viewer, [
            'title_jp' => '漢字の記事',
            'content_jp' => '水を飲むための記事本文です。',
        ]);
        $this->attachKanji($article);

        $result = $this->service()->exportKanjis(EntityId::from($article->uuid), $viewer);

        $this->assertTrue($result->isSuccess());
        $this->assertInstanceOf(PdfRenderResult::class, $result->getData());

        $document = $this->pdfRenderer->lastDocument;

        $this->assertNotNull($document);
        $this->assertSame('pdf.articles.kanjis', $document->view);
        $this->assertSame('article-kanjis.pdf', $document->filename);
        $this->assertSame(PdfDisposition::INLINE, $document->disposition);
        $this->assertSame('漢字の記事', $document->data['article']['title_jp']);
        $this->assertSame('水', $document->data['kanjis'][0]['kanji']);
        $this->assertSame(['スイ', 'ス'], $document->data['kanjis'][0]['onyomi']);
        $this->assertSame(['みず', 'み'], $document->data['kanjis'][0]['kunyomi']);
        $this->assertSame(['water', 'fluid'], $document->data['kanjis'][0]['meaning']);
        $this->assertSame(config('app.frontend_url'), $document->data['frontend_url']);
        $this->assertSame('article-kanjis.pdf', $result->getData()->filename);
        $this->assertCount(1, $this->downloadRepository->created);
        $this->assertSame($viewer->id, $this->downloadRepository->created[0]->userId);
        $this->assertSame(ObjectTemplateType::ARTICLE->getLegacyId(), $this->downloadRepository->created[0]->templateId);
        $this->assertSame($article->id, $this->downloadRepository->created[0]->realObjectId);
    }

    public function test_words_export_returns_rendered_pdf_with_processed_word_meanings_and_records_download(): void
    {
        $viewer = $this->createUser();
        $article = $this->createArticle($viewer, [
            'title_jp' => '言葉の記事',
            'content_jp' => '学校へ行くための記事本文です。',
        ]);
        $this->attachWord($article);

        $result = $this->service()->exportWords(EntityId::from($article->uuid), $viewer);

        $this->assertTrue($result->isSuccess());

        $document = $this->pdfRenderer->lastDocument;

        $this->assertNotNull($document);
        $this->assertSame('pdf.articles.words', $document->view);
        $this->assertSame('article-words.pdf', $document->filename);
        $this->assertSame('言葉の記事', $document->data['article']['title_jp']);
        $this->assertSame('学校', $document->data['words'][0]['word']);
        $this->assertSame('がっこう', $document->data['words'][0]['furigana']);
        $this->assertSame('5', $document->data['words'][0]['jlpt']);
        $this->assertSame('school', $document->data['words'][0]['meaning']);
        $this->assertCount(1, $this->downloadRepository->created);
        $this->assertSame(ObjectTemplateType::ARTICLE->getLegacyId(), $this->downloadRepository->created[0]->templateId);
        $this->assertSame($article->id, $this->downloadRepository->created[0]->realObjectId);
    }

    public function test_missing_article_returns_article_not_found_error(): void
    {
        $viewer = $this->createUser();

        $result = $this->service()->exportKanjis(EntityId::from((string) Str::uuid()), $viewer);

        $this->assertTrue($result->isFailure());
        $this->assertSame('Articles.NotFound', $result->getError()->code);
        $this->assertNull($this->pdfRenderer->lastDocument);
        $this->assertCount(0, $this->downloadRepository->created);
    }

    public function test_inaccessible_article_returns_article_access_denied_error(): void
    {
        $author = $this->createUser();
        $viewer = $this->createUser();
        $article = $this->createArticle($author, [
            'publicity' => PublicityStatus::PRIVATE,
        ]);

        $result = $this->service()->exportKanjis(EntityId::from($article->uuid), $viewer);

        $this->assertTrue($result->isFailure());
        $this->assertSame('Articles.AccessDenied', $result->getError()->code);
        $this->assertNull($this->pdfRenderer->lastDocument);
        $this->assertCount(0, $this->downloadRepository->created);
    }

    public function test_new_article_pdf_views_do_not_use_legacy_localhost_or_remote_assets(): void
    {
        $data = [
            'frontend_url' => 'https://app.example.test',
            'article' => [
                'id' => 10,
                'uuid' => 'article-uuid',
                'title_jp' => '漢字の記事',
                'title_en' => 'Kanji Article',
                'content_jp' => '水を飲むための記事本文です。',
                'content_en' => 'Article body',
                'author' => 'Article User',
                'user_id' => 20,
                'date' => now(),
                'source_link' => 'https://example.com/source',
            ],
            'kanjis' => [
                [
                    'id' => 1,
                    'kanji' => '水',
                    'onyomi' => ['スイ', 'ス'],
                    'kunyomi' => ['みず', 'み'],
                    'meaning' => ['water', 'fluid'],
                    'jlpt' => '5',
                ],
            ],
            'words' => [
                [
                    'id' => 1,
                    'word' => '学校',
                    'furigana' => 'がっこう',
                    'meaning' => 'school',
                    'jlpt' => '5',
                ],
            ],
        ];

        $kanjisHtml = view('pdf.articles.kanjis', $data)->render();
        $wordsHtml = view('pdf.articles.words', $data)->render();
        $html = $kanjisHtml.$wordsHtml;

        $this->assertStringContainsString('https://app.example.test/articles/article-uuid', $html);
        $this->assertStringContainsString('.kanjis-table', $kanjisHtml);
        $this->assertStringContainsString('width: 100%;', $kanjisHtml);
        $this->assertStringContainsString('text-align: left;', $kanjisHtml);
        $this->assertStringContainsString("td {\n            border-bottom: 1px solid #e5e7eb;\n            vertical-align: top;", $kanjisHtml);
        $this->assertStringContainsString('<table class="kanjis-table">', $kanjisHtml);
        $this->assertStringContainsString('<span class="value-line">スイ</span>', $kanjisHtml);
        $this->assertStringContainsString('<span class="value-line">ス</span>', $kanjisHtml);
        $this->assertStringNotContainsString('スイ|ス', $kanjisHtml);
        $this->assertStringNotContainsString('localhost:3000', $html);
        $this->assertStringNotContainsString('maxcdn.bootstrapcdn.com', $html);
        $this->assertStringNotContainsString('jquery', $html);
        $this->assertStringNotContainsString('popper', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    private function service(): ArticlePdfExportService
    {
        return $this->app->make(ArticlePdfExportService::class);
    }

    private function createUser(): User
    {
        return User::create([
            'name' => 'Article User',
            'email' => Str::uuid().'@example.com',
            'password' => Hash::make('password'),
            'uuid' => (string) Str::uuid(),
        ]);
    }

    private function createArticle(User $user, array $overrides = []): Article
    {
        return Article::create(array_merge([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'entity_type_uuid' => ObjectTemplateType::ARTICLE->value,
            'title_jp' => '記事',
            'title_en' => 'Article',
            'content_jp' => 'これは記事本文のテストです。',
            'content_en' => 'Article body text.',
            'source_link' => 'https://example.com/source',
            'publicity' => PublicityStatus::PUBLIC,
            'status' => ArticleStatus::APPROVED,
            'n1' => 0,
            'n2' => 0,
            'n3' => 0,
            'n4' => 0,
            'n5' => 0,
            'uncommon' => 0,
        ], $overrides));
    }

    private function attachKanji(Article $article): void
    {
        $kanjiId = DB::table('japanese_kanji_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'kanji' => '水',
            'onyomi' => 'スイ|ス',
            'kunyomi' => 'みず|み',
            'meaning' => 'water|fluid',
            'nanori' => '-',
            'grade' => '1',
            'stroke_count' => '4',
            'jlpt' => '5',
            'frequency' => '1',
            'radicals' => 'water',
            'radical_parts' => '水',
        ]);

        DB::table('article_kanji')->insert([
            'article_id' => $article->id,
            'kanji_id' => $kanjiId,
        ]);
    }

    private function attachWord(Article $article): void
    {
        $wordId = DB::table('japanese_word_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'entry_sequence' => '1000000',
            'word' => '学校',
            'furigana' => 'がっこう',
            'jlpt' => '5',
            'word_type' => 'noun',
            'word_k_ele' => '[]',
            'furigana_r_ele' => '[]',
            'sense' => json_encode([
                [
                    ['gloss', ['school']],
                ],
            ]),
        ]);

        DB::table('article_word')->insert([
            'article_id' => $article->id,
            'word_id' => $wordId,
        ]);
    }
}

class FakePdfRenderer implements PdfRendererInterface
{
    public ?PdfDocument $lastDocument = null;

    public function render(PdfDocument $document): PdfRenderResult
    {
        $this->lastDocument = $document;

        return new PdfRenderResult(
            contents: '%PDF-1.7',
            filename: $document->filename,
            disposition: $document->disposition,
        );
    }
}

class FakeDownloadRepository implements DownloadRepositoryInterface
{
    /** @var list<DownloadCreateDTO> */
    public array $created = [];

    public function create(DownloadCreateDTO $data): void
    {
        $this->created[] = $data;
    }

    public function findByFilter(DownloadFilterDTO $filter): ?int
    {
        return null;
    }

    public function deleteByEntity(int $entityId, int $entityTypeId): void
    {
    }

    public function findAllByEntityIds(array $entityIds, ObjectTemplateType $objectType): array
    {
        return [];
    }

    public function findAllByFilter(DownloadFilterDTO $filter): array
    {
        return [];
    }

    public function countByFilter(DownloadFilterDTO $filter): int
    {
        return 0;
    }
}
