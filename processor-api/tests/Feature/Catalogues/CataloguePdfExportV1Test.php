<?php

namespace Tests\Feature\Catalogues;

use App\Application\Pdf\PdfRendererInterface;
use App\Domain\Pdf\DTOs\PdfDocument;
use App\Domain\Pdf\DTOs\PdfRenderResult;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CataloguePdfExportV1Test extends TestCase
{
    use RefreshDatabase;

    private CatalogueFeatureCapturingPdfRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => UserRole::COMMON->value, 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => UserRole::ADMIN->value, 'guard_name' => 'api']);

        DB::table('objecttemplates')->insert([
            'id' => ObjectTemplateType::LIST->getLegacyId(),
            'title' => ObjectTemplateType::LIST->getTitle(),
            'entity_type_uuid' => ObjectTemplateType::LIST->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->renderer = new CatalogueFeatureCapturingPdfRenderer;
        $this->app->instance(PdfRendererInterface::class, $this->renderer);
    }

    public function test_authenticated_user_can_export_catalogue_kanjis_pdf_from_v1_route(): void
    {
        $owner = $this->createUser();
        $catalogue = $this->createCatalogue($owner, [
            'title' => '日本語の漢字',
            'type' => SavedListType::KANJIS,
        ]);
        $this->attachKanji($catalogue);

        Passport::actingAs($owner, ['*'], 'api');

        $response = $this->get("/api/v1/catalogues/{$catalogue->uuid}/kanjis-pdf");

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringStartsWith('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('catalogue-kanjis.pdf', $response->headers->get('content-disposition'));

        $document = $this->renderer->lastDocument();

        $this->assertSame('pdf.catalogues.kanjis', $document->view);
        $this->assertSame('catalogue-kanjis.pdf', $document->filename);
        $this->assertSame('日本語の漢字', $document->data['catalogue']['title']);
        $this->assertSame('水', $document->data['kanjis'][0]['kanji']);
        $this->assertSame(config('app.frontend_url'), $document->data['frontend_url']);
        $this->assertDatabaseHas('downloads', [
            'template_id' => ObjectTemplateType::LIST->getLegacyId(),
            'real_object_id' => $catalogue->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_authenticated_user_can_export_catalogue_words_pdf_from_v1_route(): void
    {
        $owner = $this->createUser();
        $catalogue = $this->createCatalogue($owner, [
            'title' => '日本語の言葉',
            'type' => SavedListType::WORDS,
        ]);
        $this->attachWord($catalogue);

        Passport::actingAs($owner, ['*'], 'api');

        $response = $this->get("/api/v1/catalogues/{$catalogue->uuid}/words-pdf");

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringStartsWith('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('catalogue-words.pdf', $response->headers->get('content-disposition'));

        $document = $this->renderer->lastDocument();

        $this->assertSame('pdf.catalogues.words', $document->view);
        $this->assertSame('catalogue-words.pdf', $document->filename);
        $this->assertSame('日本語の言葉', $document->data['catalogue']['title']);
        $this->assertSame('学校', $document->data['words'][0]['word']);
        $this->assertSame('school', $document->data['words'][0]['meaning']);
    }

    public function test_catalogue_pdf_export_requires_authentication(): void
    {
        $owner = $this->createUser();
        $catalogue = $this->createCatalogue($owner);

        $this->getJson("/api/v1/catalogues/{$catalogue->uuid}/kanjis-pdf")
            ->assertUnauthorized();
    }

    public function test_catalogue_pdf_export_returns_v1_error_for_missing_catalogue(): void
    {
        Passport::actingAs($this->createUser(), ['*'], 'api');

        $this->getJson('/api/v1/catalogues/'.Str::uuid().'/kanjis-pdf')
            ->assertNotFound()
            ->assertJsonPath('title', 'Catalogue not found')
            ->assertJsonPath('status', 404);
    }

    public function test_catalogue_pdf_export_returns_v1_error_for_inaccessible_catalogue(): void
    {
        $owner = $this->createUser();
        $viewer = $this->createUser();
        $catalogue = $this->createCatalogue($owner, [
            'publicity' => PublicityStatus::PRIVATE,
            'type' => SavedListType::KANJIS,
        ]);

        Passport::actingAs($viewer, ['*'], 'api');

        $this->getJson("/api/v1/catalogues/{$catalogue->uuid}/kanjis-pdf")
            ->assertForbidden()
            ->assertJsonPath('title', 'Access denied')
            ->assertJsonPath('status', 403);
    }

    private function createUser(): User
    {
        return User::create([
            'name' => 'Catalogue User',
            'email' => Str::uuid().'@example.com',
            'password' => Hash::make('password'),
            'uuid' => (string) Str::uuid(),
        ]);
    }

    private function createCatalogue(User $user, array $overrides = []): Catalogue
    {
        return Catalogue::create(array_merge([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'entity_type_uuid' => ObjectTemplateType::LIST->value,
            'title' => 'Catalogue',
            'description' => 'Catalogue description',
            'publicity' => PublicityStatus::PUBLIC,
            'type' => SavedListType::KANJIS,
        ], $overrides));
    }

    private function attachKanji(Catalogue $catalogue): void
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

        DB::table('customlist_object')->insert([
            'list_id' => $catalogue->id,
            'real_object_id' => $kanjiId,
            'listtype_id' => SavedListType::KANJIS->value,
        ]);
    }

    private function attachWord(Catalogue $catalogue): void
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

        DB::table('customlist_object')->insert([
            'list_id' => $catalogue->id,
            'real_object_id' => $wordId,
            'listtype_id' => SavedListType::WORDS->value,
        ]);
    }
}

class CatalogueFeatureCapturingPdfRenderer implements PdfRendererInterface
{
    /** @var list<PdfDocument> */
    public array $documents = [];

    public function render(PdfDocument $document): PdfRenderResult
    {
        $this->documents[] = $document;

        return new PdfRenderResult(
            contents: '%PDF-1.7 catalogue feature test',
            filename: $document->filename,
            disposition: $document->disposition,
        );
    }

    public function lastDocument(): PdfDocument
    {
        return $this->documents[array_key_last($this->documents)];
    }
}
