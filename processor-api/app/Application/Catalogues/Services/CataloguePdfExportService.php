<?php

namespace App\Application\Catalogues\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Catalogues\Interfaces\Repositories\CatalogueRepositoryInterface;
use App\Application\Catalogues\Policies\CataloguePolicy;
use App\Application\Engagement\Actions\RecordDownloadAction;
use App\Application\Pdf\PdfRendererInterface;
use App\Domain\Catalogues\Errors\CatalogueErrors;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Pdf\DTOs\CatalogueKanjisPdfDTO;
use App\Domain\Pdf\DTOs\CataloguePdfHeaderDTO;
use App\Domain\Pdf\DTOs\CataloguePdfViewDataInterface;
use App\Domain\Pdf\DTOs\CatalogueWordsPdfDTO;
use App\Domain\Pdf\DTOs\PdfDocument;
use App\Domain\Pdf\Enums\PdfExportKind;
use App\Domain\Pdf\Errors\PdfExportErrors;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;
use Throwable;

class CataloguePdfExportService implements CataloguePdfExportServiceInterface
{
    public function __construct(
        private readonly CatalogueRepositoryInterface $catalogueRepository,
        private readonly CataloguePolicy $cataloguePolicy,
        private readonly CatalogueItemService $catalogueItemService,
        private readonly PdfRendererInterface $pdfRenderer,
        private readonly RecordDownloadAction $recordDownloadAction,
    ) {
    }

    public function exportKanjis(EntityId $catalogueUuid, AuthenticatedUser $authenticatedUser): Result
    {
        return $this->export($catalogueUuid, $authenticatedUser, PdfExportKind::KANJIS);
    }

    public function exportWords(EntityId $catalogueUuid, AuthenticatedUser $authenticatedUser): Result
    {
        return $this->export($catalogueUuid, $authenticatedUser, PdfExportKind::WORDS);
    }

    private function export(EntityId $catalogueUuid, AuthenticatedUser $authenticatedUser, PdfExportKind $kind): Result
    {
        $catalogue = $this->catalogueRepository->findByPublicUid($catalogueUuid);

        if ($catalogue === null) {
            return Result::failure(CatalogueErrors::notFound($catalogueUuid->value()));
        }

        if (! $this->cataloguePolicy->canView($authenticatedUser, $catalogue)) {
            return Result::failure(CatalogueErrors::accessDenied($catalogueUuid->value()));
        }

        if (! $this->supportsCatalogueType($catalogue, $kind)) {
            return Result::failure(CatalogueErrors::unsupportedPdfExportKind(
                $catalogueUuid->value(),
                $kind->value,
            ));
        }

        $items = $this->catalogueItemService->getItems($catalogue);

        $document = new PdfDocument(
            view: $kind->view(),
            data: $this->buildViewData($catalogue, $kind, $items)->toViewData(),
            filename: $kind->filename(),
        );

        try {
            $renderResult = $this->pdfRenderer->render($document);
        } catch (Throwable $exception) {
            return Result::failure(PdfExportErrors::renderFailed($exception->getMessage()));
        }

        $this->recordDownloadAction->record(
            viewerId: $authenticatedUser->id,
            objectType: ObjectTemplateType::LIST,
            entityId: $catalogue->getIdValue(),
            context: [
                'source' => 'catalogue',
                'kind' => $kind->value,
                'entity_uuid' => $catalogueUuid->value(),
            ],
        );

        return Result::success($renderResult);
    }

    private function supportsCatalogueType(Catalogue $catalogue, PdfExportKind $kind): bool
    {
        return match ($kind) {
            PdfExportKind::KANJIS => in_array($catalogue->getType(), [SavedListType::KANJIS, SavedListType::KNOWNKANJIS], true),
            PdfExportKind::WORDS => in_array($catalogue->getType(), [SavedListType::WORDS, SavedListType::KNOWNWORDS], true),
        };
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function buildViewData(Catalogue $catalogue, PdfExportKind $kind, array $items): CataloguePdfViewDataInterface
    {
        $frontendUrl = (string) config('app.frontend_url');
        $header = CataloguePdfHeaderDTO::fromCatalogue($catalogue);

        return match ($kind) {
            PdfExportKind::KANJIS => new CatalogueKanjisPdfDTO($frontendUrl, $header, $this->normalizeKanjis($items)),
            PdfExportKind::WORDS => new CatalogueWordsPdfDTO($frontendUrl, $header, $this->normalizeWords($items)),
        };
    }

    /**
     * @param array<int, array<string, mixed>> $kanjis
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeKanjis(array $kanjis): array
    {
        return array_map(function (array $kanji): array {
            $kanji['onyomi'] = $this->firstPipeValues((string) ($kanji['onyomi'] ?? ''));
            $kanji['kunyomi'] = $this->firstPipeValues((string) ($kanji['kunyomi'] ?? ''));
            $kanji['meaning'] = $this->firstPipeValues((string) ($kanji['meaning'] ?? ''));

            return $kanji;
        }, $kanjis);
    }

    /**
     * @param array<int, array<string, mixed>> $words
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeWords(array $words): array
    {
        return array_map(function (array $word): array {
            $word['meaning'] = trim((string) ($word['meaning'] ?? ''), " \t\n\r\0\x0B,");

            return $word;
        }, $words);
    }

    private function firstPipeValues(string $value): string
    {
        return implode(', ', array_slice(explode('|', $value), 0, 3));
    }
}
