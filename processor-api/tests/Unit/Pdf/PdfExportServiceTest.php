<?php

namespace Tests\Unit\Pdf;

use App\Application\Engagement\Interfaces\Repositories\DownloadRepositoryInterface;
use App\Application\Pdf\DTOs\PdfExportRequest;
use App\Application\Pdf\PdfExportProviderInterface;
use App\Application\Pdf\PdfExportService;
use App\Application\Pdf\PdfRendererInterface;
use App\Domain\Engagement\DTOs\DownloadCreateDTO;
use App\Domain\Engagement\DTOs\DownloadFilterDTO;
use App\Domain\Pdf\DTOs\PdfDocument;
use App\Domain\Pdf\DTOs\PdfDownloadTarget;
use App\Domain\Pdf\DTOs\PdfExportPreparation;
use App\Domain\Pdf\DTOs\PdfRenderResult;
use App\Domain\Pdf\Enums\PdfExportKind;
use App\Domain\Pdf\Enums\PdfExportSource;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\User;
use App\Shared\Results\Result;
use RuntimeException;
use Tests\TestCase;

class PdfExportServiceTest extends TestCase
{
    public function test_export_delegates_to_matching_provider_renders_document_and_records_download(): void
    {
        $provider = new FakePdfExportProvider;
        $renderer = new FakePdfRenderer;
        $downloads = new FakeDownloadRepository;
        $service = new PdfExportService($renderer, $downloads, [$provider]);
        $request = $this->request();

        $result = $service->export($request);

        $this->assertTrue($result->isSuccess());
        $this->assertInstanceOf(PdfRenderResult::class, $result->getData());
        $this->assertSame($request, $provider->lastRequest);
        $this->assertSame('pdf.test', $renderer->lastDocument?->view);
        $this->assertSame('test.pdf', $result->getData()->filename);
        $this->assertCount(1, $downloads->created);
        $this->assertSame($request->viewer->id, $downloads->created[0]->userId);
        $this->assertSame(ObjectTemplateType::ARTICLE->getLegacyId(), $downloads->created[0]->templateId);
        $this->assertSame(321, $downloads->created[0]->realObjectId);
    }

    public function test_export_does_not_record_download_when_rendering_fails(): void
    {
        $provider = new FakePdfExportProvider;
        $renderer = new FakePdfRenderer;
        $renderer->shouldFail = true;
        $downloads = new FakeDownloadRepository;
        $service = new PdfExportService($renderer, $downloads, [$provider]);

        $result = $service->export($this->request());

        $this->assertTrue($result->isFailure());
        $this->assertSame('PdfExport.RenderFailed', $result->getError()->code);
        $this->assertCount(0, $downloads->created);
    }

    public function test_export_returns_pdf_when_download_tracking_fails(): void
    {
        $provider = new FakePdfExportProvider;
        $renderer = new FakePdfRenderer;
        $downloads = new FakeDownloadRepository;
        $downloads->shouldFail = true;
        $service = new PdfExportService($renderer, $downloads, [$provider]);

        $result = $service->export($this->request());

        $this->assertTrue($result->isSuccess());
        $this->assertSame('test.pdf', $result->getData()->filename);
        $this->assertSame(1, $downloads->attempts);
    }

    public function test_export_returns_error_when_no_provider_supports_request(): void
    {
        $provider = new FakePdfExportProvider;
        $provider->supportsRequest = false;
        $service = new PdfExportService(new FakePdfRenderer, new FakeDownloadRepository, [$provider]);

        $result = $service->export($this->request());

        $this->assertTrue($result->isFailure());
        $this->assertSame('PdfExport.Unsupported', $result->getError()->code);
    }

    private function request(): PdfExportRequest
    {
        $viewer = new User;
        $viewer->id = 123;
        $viewer->uuid = 'viewer-uuid';
        $viewer->name = 'Viewer';

        return new PdfExportRequest(
            source: PdfExportSource::ARTICLE,
            entityUuid: EntityId::from('4a0ef464-9ac1-4823-88c3-761d1eac665e'),
            kind: PdfExportKind::KANJIS,
            viewer: $viewer,
        );
    }
}

class FakePdfExportProvider implements PdfExportProviderInterface
{
    public ?PdfExportRequest $lastRequest = null;

    public bool $supportsRequest = true;

    public function supports(PdfExportRequest $request): bool
    {
        return $this->supportsRequest;
    }

    public function prepare(PdfExportRequest $request): Result
    {
        $this->lastRequest = $request;

        return Result::success(new PdfExportPreparation(
            document: new PdfDocument(
                view: 'pdf.test',
                data: ['title' => 'Test'],
                filename: 'test.pdf',
            ),
            downloadTarget: new PdfDownloadTarget(
                objectType: ObjectTemplateType::ARTICLE,
                entityId: 321,
            ),
        ));
    }
}

class FakePdfRenderer implements PdfRendererInterface
{
    public ?PdfDocument $lastDocument = null;

    public bool $shouldFail = false;

    public function render(PdfDocument $document): PdfRenderResult
    {
        $this->lastDocument = $document;

        if ($this->shouldFail) {
            throw new RuntimeException('Renderer failed');
        }

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

    public int $attempts = 0;

    public bool $shouldFail = false;

    public function create(DownloadCreateDTO $data): void
    {
        $this->attempts++;

        if ($this->shouldFail) {
            throw new RuntimeException('Download tracking failed');
        }

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
