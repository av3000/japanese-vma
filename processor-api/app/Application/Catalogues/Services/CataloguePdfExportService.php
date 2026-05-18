<?php

namespace App\Application\Catalogues\Services;

use App\Application\Catalogues\Interfaces\Repositories\CatalogueRepositoryInterface;
use App\Application\Catalogues\Policies\CataloguePolicy;
use App\Application\Engagement\Actions\RecordDownloadAction;
use App\Application\Pdf\PdfRendererInterface;
use App\Domain\Catalogues\Errors\CatalogueErrors;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Pdf\DTOs\PdfDocument;
use App\Domain\Pdf\Enums\PdfExportKind;
use App\Domain\Pdf\Errors\PdfExportErrors;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\User;
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

    public function exportKanjis(EntityId $catalogueUuid, User $viewer): Result
    {
        return $this->export($catalogueUuid, $viewer, PdfExportKind::KANJIS);
    }

    public function exportWords(EntityId $catalogueUuid, User $viewer): Result
    {
        return $this->export($catalogueUuid, $viewer, PdfExportKind::WORDS);
    }

    // TODO: Recreate radical and sentence PDF exports here as v1 service-backed
    // exports when those kinds are supported; do not route them through
    // CustomListController or any renderer facade.
    private function export(EntityId $catalogueUuid, User $viewer, PdfExportKind $kind): Result
    {
        $catalogue = $this->catalogueRepository->findByPublicUid($catalogueUuid);

        if ($catalogue === null) {
            return Result::failure(CatalogueErrors::notFound($catalogueUuid->value()));
        }

        if (! $this->cataloguePolicy->canView($viewer, $catalogue)) {
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
            view: $kind === PdfExportKind::KANJIS ? 'pdf.catalogues.kanjis' : 'pdf.catalogues.words',
            data: $this->buildViewData($catalogue, $kind, $items),
            filename: $kind === PdfExportKind::KANJIS ? 'catalogue-kanjis.pdf' : 'catalogue-words.pdf',
        );

        try {
            $renderResult = $this->pdfRenderer->render($document);
        } catch (Throwable $exception) {
            return Result::failure(PdfExportErrors::renderFailed($exception->getMessage()));
        }

        $this->recordDownloadAction->record(
            viewer: $viewer,
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
     *
     * @return array<string, mixed>
     */
    private function buildViewData(Catalogue $catalogue, PdfExportKind $kind, array $items): array
    {
        return [
            'frontend_url' => config('app.frontend_url'),
            'catalogue' => [
                'id' => $catalogue->getIdValue(),
                'uuid' => $catalogue->getUid()->value(),
                'title' => $catalogue->getTitle()->value,
                'type_label' => $catalogue->getTypeLabel(),
                'author' => $catalogue->getOwnerName()->value(),
                'user_id' => $catalogue->getOwnerId()->value(),
                'date' => $catalogue->getCreatedAt(),
            ],
            'kanjis' => $kind === PdfExportKind::KANJIS ? $this->normalizeKanjis($items) : [],
            'words' => $kind === PdfExportKind::WORDS ? $this->normalizeWords($items) : [],
        ];
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
