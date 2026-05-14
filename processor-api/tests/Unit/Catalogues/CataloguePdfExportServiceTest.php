<?php

namespace Tests\Unit\Catalogues;

use App\Application\Catalogues\Services\CataloguePdfExportService;
use App\Application\Engagement\Interfaces\Repositories\DownloadRepositoryInterface;
use App\Application\Pdf\PdfRendererInterface;
use App\Domain\Engagement\DTOs\DownloadCreateDTO;
use App\Domain\Engagement\DTOs\DownloadFilterDTO;
use App\Domain\Pdf\DTOs\PdfDocument;
use App\Domain\Pdf\DTOs\PdfRenderResult;
use App\Domain\Pdf\Enums\PdfDisposition;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\Enums\UserRole;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CataloguePdfExportServiceTest extends TestCase
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

        Role::firstOrCreate(['name' => UserRole::COMMON->value, 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => UserRole::ADMIN->value, 'guard_name' => 'api']);
    }

    public function test_kanjis_export_returns_rendered_pdf_and_records_download(): void
    {
        $viewer = $this->createUser();
        $catalogue = $this->createCatalogue($viewer, [
            'title' => '漢字カタログ',
            'type' => SavedListType::KANJIS,
        ]);
        $this->attachKanji($catalogue);

        $result = $this->service()->exportKanjis(EntityId::from($catalogue->uuid), $viewer);

        $this->assertTrue($result->isSuccess());
        $this->assertInstanceOf(PdfRenderResult::class, $result->getData());

        $document = $this->pdfRenderer->lastDocument;

        $this->assertNotNull($document);
        $this->assertSame('pdf.catalogues.kanjis', $document->view);
        $this->assertSame('catalogue-kanjis.pdf', $document->filename);
        $this->assertSame(PdfDisposition::INLINE, $document->disposition);
        $this->assertSame('漢字カタログ', $document->data['catalogue']['title']);
        $this->assertSame($catalogue->uuid, $document->data['catalogue']['uuid']);
        $this->assertSame('水', $document->data['kanjis'][0]['kanji']);
        $this->assertSame(config('app.frontend_url'), $document->data['frontend_url']);
        $this->assertSame('catalogue-kanjis.pdf', $result->getData()->filename);
        $this->assertCount(1, $this->downloadRepository->created);
        $this->assertSame($viewer->id, $this->downloadRepository->created[0]->userId);
        $this->assertSame(ObjectTemplateType::LIST->getLegacyId(), $this->downloadRepository->created[0]->templateId);
        $this->assertSame($catalogue->id, $this->downloadRepository->created[0]->realObjectId);
    }

    public function test_words_export_returns_rendered_pdf_with_processed_word_meanings_and_records_download(): void
    {
        $viewer = $this->createUser();
        $catalogue = $this->createCatalogue($viewer, [
            'title' => '言葉カタログ',
            'type' => SavedListType::WORDS,
        ]);
        $this->attachWord($catalogue);

        $result = $this->service()->exportWords(EntityId::from($catalogue->uuid), $viewer);

        $this->assertTrue($result->isSuccess());

        $document = $this->pdfRenderer->lastDocument;

        $this->assertNotNull($document);
        $this->assertSame('pdf.catalogues.words', $document->view);
        $this->assertSame('catalogue-words.pdf', $document->filename);
        $this->assertSame('言葉カタログ', $document->data['catalogue']['title']);
        $this->assertSame('学校', $document->data['words'][0]['word']);
        $this->assertSame('school', $document->data['words'][0]['meaning']);
        $this->assertCount(1, $this->downloadRepository->created);
        $this->assertSame(ObjectTemplateType::LIST->getLegacyId(), $this->downloadRepository->created[0]->templateId);
        $this->assertSame($catalogue->id, $this->downloadRepository->created[0]->realObjectId);
    }

    public function test_missing_catalogue_returns_catalogue_not_found_error(): void
    {
        $viewer = $this->createUser();

        $result = $this->service()->exportKanjis(EntityId::from((string) Str::uuid()), $viewer);

        $this->assertTrue($result->isFailure());
        $this->assertSame('Catalogues.NotFound', $result->getError()->code);
        $this->assertNull($this->pdfRenderer->lastDocument);
        $this->assertCount(0, $this->downloadRepository->created);
    }

    public function test_inaccessible_catalogue_returns_catalogue_access_denied_error(): void
    {
        $owner = $this->createUser();
        $viewer = $this->createUser();
        $catalogue = $this->createCatalogue($owner, [
            'publicity' => PublicityStatus::PRIVATE,
            'type' => SavedListType::KANJIS,
        ]);

        $result = $this->service()->exportKanjis(EntityId::from($catalogue->uuid), $viewer);

        $this->assertTrue($result->isFailure());
        $this->assertSame('Catalogues.AccessDenied', $result->getError()->code);
        $this->assertNull($this->pdfRenderer->lastDocument);
        $this->assertCount(0, $this->downloadRepository->created);
    }

    public function test_wrong_catalogue_type_returns_catalogue_pdf_unsupported_kind_error(): void
    {
        $viewer = $this->createUser();
        $catalogue = $this->createCatalogue($viewer, [
            'type' => SavedListType::WORDS,
        ]);

        $result = $this->service()->exportKanjis(EntityId::from($catalogue->uuid), $viewer);

        $this->assertTrue($result->isFailure());
        $this->assertSame('Catalogues.UnsupportedPdfExportKind', $result->getError()->code);
        $this->assertNull($this->pdfRenderer->lastDocument);
        $this->assertCount(0, $this->downloadRepository->created);
    }

    public function test_new_catalogue_pdf_views_do_not_use_legacy_localhost_or_remote_assets(): void
    {
        $data = [
            'frontend_url' => 'https://app.example.test',
            'catalogue' => [
                'id' => 10,
                'uuid' => 'catalogue-uuid',
                'title' => '漢字カタログ',
                'type_label' => 'Kanji',
                'author' => 'Catalogue User',
                'user_id' => 20,
                'date' => now(),
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

        $kanjisHtml = view('pdf.catalogues.kanjis', $data)->render();
        $wordsHtml = view('pdf.catalogues.words', $data)->render();
        $html = $kanjisHtml.$wordsHtml;

        $this->assertStringContainsString('https://app.example.test/catalogues/catalogue-uuid', $html);
        $this->assertStringNotContainsString('localhost:3000', $html);
        $this->assertStringNotContainsString('maxcdn.bootstrapcdn.com', $html);
        $this->assertStringNotContainsString('jquery', $html);
        $this->assertStringNotContainsString('popper', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    private function service(): CataloguePdfExportService
    {
        return $this->app->make(CataloguePdfExportService::class);
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
