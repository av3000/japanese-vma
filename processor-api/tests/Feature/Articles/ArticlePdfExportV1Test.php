<?php

namespace Tests\Feature\Articles;

use App\Application\Pdf\PdfRendererInterface;
use App\Domain\Pdf\DTOs\PdfDocument;
use App\Domain\Pdf\DTOs\PdfRenderResult;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ArticlePdfExportV1Test extends TestCase
{
    use RefreshDatabase;

    private V1FeatureCapturingPdfRenderer $renderer;

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

        $this->renderer = new V1FeatureCapturingPdfRenderer;
        $this->app->instance(PdfRendererInterface::class, $this->renderer);
    }

    public function test_authenticated_user_can_export_article_kanjis_pdf_from_v1_route(): void
    {
        $author = $this->createUser();
        $article = $this->createArticle($author, [
            'title_jp' => '日本語の記事',
            'content_jp' => '水を飲むための記事本文です。',
        ]);
        $this->attachKanji($article);

        Passport::actingAs($author, ['*'], 'api');

        $response = $this->get("/api/v1/articles/{$article->uuid}/kanjis-pdf");

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringStartsWith('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('article-kanjis.pdf', $response->headers->get('content-disposition'));

        $document = $this->renderer->lastDocument();

        $this->assertSame('pdf.articles.kanjis', $document->view);
        $this->assertSame('article-kanjis.pdf', $document->filename);
        $this->assertSame('日本語の記事', $document->data['article']['title_jp']);
        $this->assertSame('水', $document->data['kanjis'][0]['kanji']);
        $this->assertSame(config('app.frontend_url'), $document->data['frontend_url']);
        $this->assertDatabaseHas('downloads', [
            'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'real_object_id' => $article->id,
            'user_id' => $author->id,
        ]);
    }

    public function test_authenticated_user_can_export_article_words_pdf_from_v1_route(): void
    {
        $author = $this->createUser();
        $article = $this->createArticle($author, [
            'title_jp' => '言葉の記事',
            'content_jp' => '学校へ行くための記事本文です。',
        ]);
        $this->attachWord($article);

        Passport::actingAs($author, ['*'], 'api');

        $response = $this->get("/api/v1/articles/{$article->uuid}/words-pdf");

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringStartsWith('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('article-words.pdf', $response->headers->get('content-disposition'));

        $document = $this->renderer->lastDocument();

        $this->assertSame('pdf.articles.words', $document->view);
        $this->assertSame('article-words.pdf', $document->filename);
        $this->assertSame('言葉の記事', $document->data['article']['title_jp']);
        $this->assertSame('学校', $document->data['words'][0]['word']);
        $this->assertSame('がっこう', $document->data['words'][0]['furigana']);
        $this->assertSame('5', $document->data['words'][0]['jlpt']);
        $this->assertSame('school', $document->data['words'][0]['meaning']);
    }

    public function test_article_pdf_export_requires_authentication(): void
    {
        $author = $this->createUser();
        $article = $this->createArticle($author);

        $this->getJson("/api/v1/articles/{$article->uuid}/kanjis-pdf")
            ->assertUnauthorized();
    }

    public function test_article_pdf_export_returns_v1_error_for_inaccessible_article(): void
    {
        $author = $this->createUser();
        $viewer = $this->createUser();
        $article = $this->createArticle($author, [
            'publicity' => PublicityStatus::PRIVATE,
        ]);

        Passport::actingAs($viewer, ['*'], 'api');

        $this->getJson("/api/v1/articles/{$article->uuid}/kanjis-pdf")
            ->assertForbidden()
            ->assertJsonPath('title', 'Access denied')
            ->assertJsonPath('status', 403);
    }

    public function test_article_pdf_export_returns_v1_error_for_missing_article(): void
    {
        Passport::actingAs($this->createUser(), ['*'], 'api');

        $this->getJson('/api/v1/articles/'.Str::uuid().'/kanjis-pdf')
            ->assertNotFound()
            ->assertJsonPath('title', 'Article not found')
            ->assertJsonPath('status', 404);
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

class V1FeatureCapturingPdfRenderer implements PdfRendererInterface
{
    /** @var list<PdfDocument> */
    public array $documents = [];

    public function render(PdfDocument $document): PdfRenderResult
    {
        $this->documents[] = $document;

        return new PdfRenderResult(
            contents: '%PDF-1.7 v1 feature test',
            filename: $document->filename,
            disposition: $document->disposition,
        );
    }

    public function lastDocument(): PdfDocument
    {
        return $this->documents[array_key_last($this->documents)];
    }
}
