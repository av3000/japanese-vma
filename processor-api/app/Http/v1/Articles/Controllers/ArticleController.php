<?php

namespace App\Http\v1\Articles\Controllers;

use App\Application\Articles\Services\ArticlePdfExportServiceInterface;
use App\Application\Articles\Services\ArticleServiceInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Auth\Interfaces\Providers\CurrentUserProviderInterface;
use App\Domain\Articles\DTOs\ArticleCreateDTO;
use App\Domain\Articles\DTOs\ArticleIncludeOptionsDTO;
use App\Domain\Articles\DTOs\ArticleListDTO;

use App\Domain\Articles\DTOs\ArticleUpdateDTO;
use App\Domain\Articles\DTOs\ArticleUpdateResultDTO;
use App\Domain\Pdf\DTOs\PdfRenderResult;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Viewer;
use App\Http\Controllers\Controller;
use App\Http\v1\Articles\Requests\ArticleDetailRequest;
use App\Http\v1\Articles\Requests\IndexArticleRequest;
use App\Http\v1\Articles\Requests\StoreArticleRequest;
use App\Http\v1\Articles\Requests\UpdateArticleRequest;

use App\Http\v1\Articles\Resources\ArticleDetailResource;
use App\Http\v1\Articles\Resources\ArticleListResource;
use App\Http\v1\Articles\Resources\ArticleResource;
use App\Http\v1\Articles\Resources\ArticleWordCollection;
use App\Http\v1\Shared\Resources\UuidCreatedResource;
use App\Shared\Http\PdfResponseFactory;
use App\Shared\Http\TypedResults;
use App\Shared\Results\Result;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Auth\AuthenticationException;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpResponse;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleServiceInterface $articleService,
        private readonly ArticlePdfExportServiceInterface $articlePdfExportService,
        private readonly PdfResponseFactory $pdfResponseFactory,
        private readonly CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    /**
     * @response ArticleListResource
     */
    #[Response(type: 'ArticleListResource')]
    public function index(IndexArticleRequest $request): JsonResponse|JsonResource
    {
        $listDTO = ArticleListDTO::fromRequest($request->validated());
        $authenticatedUser = $this->currentUserProvider->currentAuthenticatedUser();

        return new ArticleListResource(
            $this->articleService->getArticlesList($listDTO, $authenticatedUser)
        );
    }

    private function getImagePath(): string
    {
        return '/var/www/html/public/images/articles/user/testing-image.jpg';
    }

    /**
     * @response UuidCreatedResource
     */
    #[Response(201, type: 'UuidCreatedResource')]
    public function store(StoreArticleRequest $request): JsonResponse|JsonResource
    {
        $createDTO = ArticleCreateDTO::fromRequest($request->validated());

        $result = $this->articleService->createArticle($createDTO, $this->requiredAuthenticatedUser());

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        $article = $result->getData();

        return new UuidCreatedResource([
            'uuid' => $article->getUid()->value(),
        ]);
    }

    /**
     * @response ArticleDetailResource
     */
    #[Response(type: 'ArticleDetailResource')]
    public function show(string $uid, ArticleDetailRequest $request): JsonResponse|JsonResource
    {
        $articleUid = EntityId::from($uid);
        $options = ArticleIncludeOptionsDTO::fromRequest($request->validated());
        $authenticatedUser = $this->currentUserProvider->currentAuthenticatedUser();
        $viewer = new Viewer($authenticatedUser?->id, (string) $request->ip());
        $result = $this->articleService->getArticle($articleUid, $options, $viewer, $authenticatedUser);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return new ArticleDetailResource($result->getData());
    }

    /**
     * @response ArticleResource
     */
    #[Response(type: 'ArticleResource')]
    public function update(string $uid, UpdateArticleRequest $request): JsonResponse|JsonResource
    {
        $updateDTO = ArticleUpdateDTO::fromRequest($request->validated());

        // TODO: dispatch update kanjis list job
        $result = $this->articleService->updateArticle(
            $uid,
            $updateDTO,
            $this->requiredAuthenticatedUser(),
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        /** @var ArticleUpdateResultDTO $updateResult */
        $updateResult = $result->getData();

        /**
         * TODO: Consider a follow-up refactor where resources accept shaped
         * response objects instead of several side inputs.
         */
        return new ArticleResource(
            article: $updateResult->article,
            hashtags: $updateResult->hashtags,
        );
    }

    // TODO: refactor to clean architecture
    /**
     * @response array{success: true, message: string}
     */
    #[Response(type: 'array{success: true, message: string}')]
    public function destroy(string $uuid): JsonResponse
    {
        $articleUuid = EntityId::from($uuid);
        $result = $this->articleService->deleteArticle(
            $articleUuid,
            $this->requiredAuthenticatedUser(),
        );

        if ($result->isFailure()) {
            return $this->legacyFailure($result);
        }

        return response()->json([
            'success' => true,
            'message' => 'Article deleted successfully',
        ]);
    }

    // TODO: refactor to clean architecture
    /**
     * @response ArticleWordCollection
     */
    #[Response(type: 'ArticleWordCollection')]
    public function words(Request $request, int $id): JsonResponse
    {
        $result = $this->articleService->getArticleWordsResult(
            $id,
            $request->get('page'),
            $request->get('per_page')
        );

        if ($result->isFailure()) {
            return $this->legacyFailure($result);
        }

        return response()->json(new ArticleWordCollection($result->getData()));
    }

    public function exportKanjisPdf(string $uuid): JsonResponse|HttpResponse
    {
        return $this->pdfResult($this->articlePdfExportService->exportKanjis(
            EntityId::from($uuid),
            $this->requiredAuthenticatedUser(),
        ));
    }

    public function exportWordsPdf(string $uuid): JsonResponse|HttpResponse
    {
        return $this->pdfResult($this->articlePdfExportService->exportWords(
            EntityId::from($uuid),
            $this->requiredAuthenticatedUser(),
        ));
    }

    private function pdfResult(Result $result): JsonResponse|HttpResponse
    {
        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        /** @var PdfRenderResult $pdf */
        $pdf = $result->getData();

        return $this->pdfResponseFactory->make($pdf);
    }

    private function legacyFailure(Result $result): JsonResponse
    {
        $error = $result->getError();

        return response()->json([
            'success' => false,
            'message' => $error->errorMessage ?? $error->description,
        ], $error->status->value);
    }

    private function requiredAuthenticatedUser(): AuthenticatedUser
    {
        return $this->currentUserProvider->currentAuthenticatedUser()
            ?? throw new AuthenticationException;
    }
}
