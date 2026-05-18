<?php

namespace Tests\Feature\Legacy;

use App\Application\Pdf\PdfRendererInterface;
use App\Domain\Pdf\DTOs\PdfDocument;
use App\Domain\Pdf\DTOs\PdfRenderResult;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Http\Models\Article;
use App\Http\Models\CustomList;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LegacyPdfExportTest extends TestCase
{
    use RefreshDatabase;

    private LegacyCapturingPdfRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'common', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);

        DB::table('objecttemplates')->insert([
            [
                'id' => ObjectTemplateType::ARTICLE->getLegacyId(),
                'title' => ObjectTemplateType::ARTICLE->getTitle(),
                'entity_type_uuid' => ObjectTemplateType::ARTICLE->value,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => ObjectTemplateType::LIST->getLegacyId(),
                'title' => ObjectTemplateType::LIST->getTitle(),
                'entity_type_uuid' => ObjectTemplateType::LIST->value,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->renderer = new LegacyCapturingPdfRenderer;
        $this->app->instance(PdfRendererInterface::class, $this->renderer);
    }

    public function test_legacy_article_pdf_route_uses_project_pdf_renderer(): void
    {
        $author = $this->createUser();
        $article = new Article([
            'user_id' => $author->id,
            'title_jp' => '記事',
            'title_en' => 'Article',
            'content_jp' => '本文',
            'content_en' => 'Body',
            'source_link' => 'https://example.com/source',
            'publicity' => 1,
            'status' => 3,
        ]);
        $article->uuid = (string) Str::uuid();
        $article->entity_type_uuid = ObjectTemplateType::ARTICLE->value;
        $article->save();

        $this->attachKanjiToArticle($article);

        Passport::actingAs($author, ['*'], 'api');

        $response = $this->get("/api/article/{$article->id}/kanjis-pdf");

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('article-kanjis.pdf', $response->headers->get('content-disposition'));

        $document = $this->renderer->lastDocument();

        $this->assertSame('pdf.kanjis.article-kanjis', $document->view);
        $this->assertSame('article-kanjis.pdf', $document->filename);
        $this->assertSame($article->id, $document->data['article_id']);
    }

    public function test_legacy_list_pdf_route_uses_project_pdf_renderer(): void
    {
        $owner = $this->createUser();
        $list = new CustomList([
            'user_id' => $owner->id,
            'title' => '漢字リスト',
            'description' => 'Legacy list',
            'publicity' => 1,
            'type' => 6,
        ]);
        $list->uuid = (string) Str::uuid();
        $list->entity_type_uuid = ObjectTemplateType::LIST->value;
        $list->save();

        $this->attachKanjiToList($list);

        Passport::actingAs($owner, ['*'], 'api');

        $response = $this->get("/api/list/{$list->id}/kanjis-pdf");

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('list-kanjis.pdf', $response->headers->get('content-disposition'));

        $document = $this->renderer->lastDocument();

        $this->assertSame('pdf.kanjis.list-kanjis', $document->view);
        $this->assertSame('list-kanjis.pdf', $document->filename);
        $this->assertSame($list->id, $document->data['list_id']);
    }

    private function createUser(): User
    {
        return User::create([
            'name' => 'Legacy PDF User',
            'email' => Str::uuid().'@example.com',
            'password' => Hash::make('password'),
            'uuid' => (string) Str::uuid(),
        ]);
    }

    private function attachKanjiToArticle(Article $article): void
    {
        $kanjiId = $this->createKanji();

        DB::table('article_kanji')->insert([
            'article_id' => $article->id,
            'kanji_id' => $kanjiId,
        ]);
    }

    private function attachKanjiToList(CustomList $list): void
    {
        $kanjiId = $this->createKanji();

        DB::table('customlist_object')->insert([
            'list_id' => $list->id,
            'real_object_id' => $kanjiId,
            'listtype_id' => 6,
        ]);
    }

    private function createKanji(): int
    {
        return DB::table('japanese_kanji_bank_long')->insertGetId([
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
    }
}

class LegacyCapturingPdfRenderer implements PdfRendererInterface
{
    /** @var list<PdfDocument> */
    public array $documents = [];

    public function render(PdfDocument $document): PdfRenderResult
    {
        $this->documents[] = $document;

        return new PdfRenderResult(
            contents: '%PDF-1.7 legacy feature test',
            filename: $document->filename,
            disposition: $document->disposition,
        );
    }

    public function lastDocument(): PdfDocument
    {
        return $this->documents[array_key_last($this->documents)];
    }
}
