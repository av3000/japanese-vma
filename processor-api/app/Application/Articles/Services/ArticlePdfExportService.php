<?php

namespace App\Application\Articles\Services;

use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Application\Articles\Policies\ArticlePolicy;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Engagement\Actions\RecordDownloadAction;
use App\Application\Pdf\PdfRendererInterface;
use App\Domain\Articles\DTOs\ArticlePdfExportData;
use App\Domain\Articles\Errors\ArticleErrors;
use App\Domain\JapaneseMaterial\Words\Models\Word as DomainWord;
use App\Domain\Pdf\DTOs\PdfDocument;
use App\Domain\Pdf\Enums\PdfExportKind;
use App\Domain\Pdf\Errors\PdfExportErrors;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;
use Throwable;

class ArticlePdfExportService implements ArticlePdfExportServiceInterface
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository,
        private readonly ArticlePolicy $articlePolicy,
        private readonly PdfRendererInterface $pdfRenderer,
        private readonly RecordDownloadAction $recordDownloadAction,
    ) {
    }

    public function exportKanjis(EntityId $articleUuid, AuthenticatedUser $authenticatedUser): Result
    {
        return $this->export($articleUuid, $authenticatedUser, PdfExportKind::KANJIS);
    }

    public function exportWords(EntityId $articleUuid, AuthenticatedUser $authenticatedUser): Result
    {
        return $this->export($articleUuid, $authenticatedUser, PdfExportKind::WORDS);
    }

    private function export(EntityId $articleUuid, AuthenticatedUser $authenticatedUser, PdfExportKind $kind): Result
    {
        $includeKanjis = $kind === PdfExportKind::KANJIS;
        $includeWords = $kind === PdfExportKind::WORDS;

        $exportData = $this->articleRepository->findPdfExportData(
            articleUuid: $articleUuid,
            includeKanjis: $includeKanjis,
            includeWords: $includeWords,
        );

        if ($exportData === null) {
            return Result::failure(ArticleErrors::notFound($articleUuid->value()));
        }

        if (! $this->articlePolicy->canView($authenticatedUser, $exportData->article)) {
            return Result::failure(ArticleErrors::accessDenied($articleUuid->value()));
        }
        $document = new PdfDocument(
            view: $includeKanjis ? 'pdf.articles.kanjis' : 'pdf.articles.words',
            data: $this->buildViewData($exportData),
            filename: $includeKanjis ? 'article-kanjis.pdf' : 'article-words.pdf',
        );

        try {
            $renderResult = $this->pdfRenderer->render($document);
        } catch (Throwable $exception) {
            return Result::failure(PdfExportErrors::renderFailed($exception->getMessage()));
        }

        $this->recordDownloadAction->record(
            viewerId: $authenticatedUser->id,
            objectType: ObjectTemplateType::ARTICLE,
            entityId: $exportData->article->getIdValue(),
            context: [
                'source' => 'article',
                'kind' => $kind->value,
                'entity_uuid' => $articleUuid->value(),
            ],
        );

        return Result::success($renderResult);
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
            'words' => array_map(
                fn (DomainWord $word): array => [
                    'id' => $word->getIdValue(),
                    'word' => $word->getSurface(),
                    'furigana' => $word->getFurigana(),
                    'meaning' => implode(', ', array_slice($word->getMeanings(), 0, 3)),
                    'jlpt' => $word->getJlpt(),
                ],
                $exportData->words
            ),
        ];
    }
}
