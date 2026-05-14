<?php

namespace App\Application\Articles\Services;

use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Application\Articles\Policies\ArticlePolicy;
use App\Application\Pdf\DTOs\PdfExportRequest;
use App\Application\Pdf\PdfExportProviderInterface;
use App\Domain\Articles\DTOs\ArticlePdfExportData;
use App\Domain\Articles\Errors\ArticleErrors;
use App\Domain\Pdf\DTOs\PdfDocument;
use App\Domain\Pdf\DTOs\PdfDownloadTarget;
use App\Domain\Pdf\DTOs\PdfExportPreparation;
use App\Domain\Pdf\Enums\PdfExportKind;
use App\Domain\Pdf\Enums\PdfExportSource;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Shared\Results\Result;

class ArticlePdfExportProvider implements PdfExportProviderInterface
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository,
        private readonly ArticlePolicy $articlePolicy,
    ) {
    }

    public function supports(PdfExportRequest $request): bool
    {
        return $request->source === PdfExportSource::ARTICLE
            && in_array($request->kind, [PdfExportKind::KANJIS, PdfExportKind::WORDS], true);
    }

    public function prepare(PdfExportRequest $request): Result
    {
        $includeKanjis = $request->kind === PdfExportKind::KANJIS;
        $includeWords = $request->kind === PdfExportKind::WORDS;

        $exportData = $this->articleRepository->findPdfExportData(
            articleUuid: $request->entityUuid,
            includeKanjis: $includeKanjis,
            includeWords: $includeWords,
        );

        if ($exportData === null) {
            return Result::failure(ArticleErrors::notFound($request->entityUuid->value()));
        }

        if (! $this->articlePolicy->canView($request->viewer, $exportData->article)) {
            return Result::failure(ArticleErrors::accessDenied($request->entityUuid->value()));
        }

        return Result::success(new PdfExportPreparation(
            document: new PdfDocument(
                view: $includeKanjis ? 'pdf.articles.kanjis' : 'pdf.articles.words',
                data: $this->buildViewData($exportData),
                filename: $includeKanjis ? 'article-kanjis.pdf' : 'article-words.pdf',
            ),
            downloadTarget: new PdfDownloadTarget(
                objectType: ObjectTemplateType::ARTICLE,
                entityId: $exportData->article->getIdValue(),
            ),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildViewData(ArticlePdfExportData $exportData): array
    {
        $article = $exportData->article;

        return [
            'frontend_url' => config('app.frontend_url'),
            'article' => [
                'id' => $article->getIdValue(),
                'uuid' => $article->getUid()->value(),
                'title_jp' => $article->getTitleJp()->value,
                'title_en' => $article->getTitleEn()?->value,
                'content_jp' => $article->getContentJp()->value,
                'content_en' => $article->getContentEn()?->value,
                'author' => $article->getAuthorName()->value(),
                'user_id' => $article->getAuthorId()->value(),
                'date' => $article->getCreatedAt(),
                'source_link' => $article->getSourceUrl()->value,
            ],
            'kanjis' => $exportData->kanjis,
            'words' => $exportData->words,
        ];
    }
}
