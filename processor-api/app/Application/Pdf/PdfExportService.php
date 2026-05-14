<?php

namespace App\Application\Pdf;

use App\Application\Engagement\Interfaces\Repositories\DownloadRepositoryInterface;
use App\Application\Pdf\DTOs\PdfExportRequest;
use App\Domain\Engagement\DTOs\DownloadCreateDTO;
use App\Domain\Pdf\DTOs\PdfExportPreparation;
use App\Domain\Pdf\Errors\PdfExportErrors;
use App\Shared\Results\Result;
use Illuminate\Support\Facades\Log;
use Throwable;

class PdfExportService implements PdfExportServiceInterface
{
    /**
     * @param iterable<int, PdfExportProviderInterface> $providers
     */
    public function __construct(
        private readonly PdfRendererInterface $pdfRenderer,
        private readonly DownloadRepositoryInterface $downloadRepository,
        private readonly iterable $providers,
    ) {
    }

    public function export(PdfExportRequest $request): Result
    {
        $provider = $this->providerFor($request);

        if ($provider === null) {
            return Result::failure(PdfExportErrors::unsupported($request->source->value, $request->kind->value));
        }

        $preparationResult = $provider->prepare($request);

        if ($preparationResult->isFailure()) {
            return $preparationResult;
        }

        /** @var PdfExportPreparation $preparation */
        $preparation = $preparationResult->getData();

        try {
            $renderResult = $this->pdfRenderer->render($preparation->document);
        } catch (Throwable $exception) {
            return Result::failure(PdfExportErrors::renderFailed($exception->getMessage()));
        }

        try {
            $this->downloadRepository->create(new DownloadCreateDTO(
                userId: $request->viewer->id,
                templateId: $preparation->downloadTarget->objectType->getLegacyId(),
                realObjectId: $preparation->downloadTarget->entityId,
            ));
        } catch (Throwable $exception) {
            Log::warning('PDF export download tracking failed', [
                'source' => $request->source->value,
                'kind' => $request->kind->value,
                'entity_uuid' => $request->entityUuid->value(),
                'user_id' => $request->viewer->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return Result::success($renderResult);
    }

    private function providerFor(PdfExportRequest $request): ?PdfExportProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($request)) {
                return $provider;
            }
        }

        return null;
    }
}
