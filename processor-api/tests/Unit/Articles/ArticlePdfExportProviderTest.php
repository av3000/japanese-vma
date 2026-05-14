<?php

namespace Tests\Unit\Articles;

use App\Application\Articles\Services\ArticlePdfExportProvider;
use App\Application\Pdf\DTOs\PdfExportRequest;
use App\Domain\Pdf\DTOs\PdfExportPreparation;
use App\Domain\Pdf\Enums\PdfDisposition;
use App\Domain\Pdf\Enums\PdfExportKind;
use App\Domain\Pdf\Enums\PdfExportSource;
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

class ArticlePdfExportProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_kanjis_export_prepares_pdf_document_and_download_target(): void
    {
        $viewer = $this->createUser();
        $article = $this->createArticle($viewer, [
            'title_jp' => '漢字の記事',
            'content_jp' => '水を飲むための記事本文です。',
        ]);
        $this->attachKanji($article);

        $result = $this->provider()->prepare(new PdfExportRequest(
            source: PdfExportSource::ARTICLE,
            entityUuid: EntityId::from($article->uuid),
            kind: PdfExportKind::KANJIS,
            viewer: $viewer,
        ));

        $this->assertTrue($result->isSuccess());
        $this->assertInstanceOf(PdfExportPreparation::class, $result->getData());

        /** @var PdfExportPreparation $preparation */
        $preparation = $result->getData();
        $document = $preparation->document;

        $this->assertSame('pdf.articles.kanjis', $document->view);
        $this->assertSame('article-kanjis.pdf', $document->filename);
        $this->assertSame(PdfDisposition::INLINE, $document->disposition);
        $this->assertSame('漢字の記事', $document->data['article']['title_jp']);
        $this->assertSame('水', $document->data['kanjis'][0]['kanji']);
        $this->assertSame(config('app.frontend_url'), $document->data['frontend_url']);
        $this->assertSame(ObjectTemplateType::ARTICLE, $preparation->downloadTarget->objectType);
        $this->assertSame($article->id, $preparation->downloadTarget->entityId);
    }

    public function test_words_export_prepares_pdf_document_with_processed_word_meanings(): void
    {
        $viewer = $this->createUser();
        $article = $this->createArticle($viewer, [
            'title_jp' => '言葉の記事',
            'content_jp' => '学校へ行くための記事本文です。',
        ]);
        $this->attachWord($article);

        $result = $this->provider()->prepare(new PdfExportRequest(
            source: PdfExportSource::ARTICLE,
            entityUuid: EntityId::from($article->uuid),
            kind: PdfExportKind::WORDS,
            viewer: $viewer,
        ));

        $this->assertTrue($result->isSuccess());

        /** @var PdfExportPreparation $preparation */
        $preparation = $result->getData();
        $document = $preparation->document;

        $this->assertSame('pdf.articles.words', $document->view);
        $this->assertSame('article-words.pdf', $document->filename);
        $this->assertSame('言葉の記事', $document->data['article']['title_jp']);
        $this->assertSame('学校', $document->data['words'][0]['word']);
        $this->assertSame('school', $document->data['words'][0]['meaning']);
        $this->assertSame(ObjectTemplateType::ARTICLE, $preparation->downloadTarget->objectType);
        $this->assertSame($article->id, $preparation->downloadTarget->entityId);
    }

    public function test_missing_article_returns_article_not_found_error(): void
    {
        $viewer = $this->createUser();

        $result = $this->provider()->prepare(new PdfExportRequest(
            source: PdfExportSource::ARTICLE,
            entityUuid: EntityId::from((string) Str::uuid()),
            kind: PdfExportKind::KANJIS,
            viewer: $viewer,
        ));

        $this->assertTrue($result->isFailure());
        $this->assertSame('Articles.NotFound', $result->getError()->code);
    }

    public function test_inaccessible_article_returns_article_access_denied_error(): void
    {
        $author = $this->createUser();
        $viewer = $this->createUser();
        $article = $this->createArticle($author, [
            'publicity' => PublicityStatus::PRIVATE,
        ]);

        $result = $this->provider()->prepare(new PdfExportRequest(
            source: PdfExportSource::ARTICLE,
            entityUuid: EntityId::from($article->uuid),
            kind: PdfExportKind::KANJIS,
            viewer: $viewer,
        ));

        $this->assertTrue($result->isFailure());
        $this->assertSame('Articles.AccessDenied', $result->getError()->code);
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
                    'onyomi' => 'スイ',
                    'kunyomi' => 'みず',
                    'meaning' => 'water',
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
        $this->assertStringNotContainsString('localhost:3000', $html);
        $this->assertStringNotContainsString('maxcdn.bootstrapcdn.com', $html);
        $this->assertStringNotContainsString('jquery', $html);
        $this->assertStringNotContainsString('popper', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    private function provider(): ArticlePdfExportProvider
    {
        return $this->app->make(ArticlePdfExportProvider::class);
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
            'onyomi' => 'スイ',
            'kunyomi' => 'みず',
            'meaning' => 'water',
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
