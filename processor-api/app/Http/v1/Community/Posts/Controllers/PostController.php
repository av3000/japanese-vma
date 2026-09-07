<?php

declare(strict_types=1);

namespace App\Http\v1\Community\Posts\Controllers;

use App\Application\Auth\Interfaces\Providers\CurrentUserProviderInterface;
use App\Application\Community\Posts\Services\PostReadServiceInterface;
use App\Domain\Community\Posts\Enums\PostSort;
use App\Domain\Community\Posts\Enums\PostTopic;
use App\Domain\Community\Posts\Queries\PostQueryCriteria;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\Viewer;
use App\Http\Controllers\Controller;
use App\Http\v1\Community\Posts\Requests\IndexPostRequest;
use App\Http\v1\Community\Posts\Resources\PostDetailResource;
use App\Http\v1\Community\Posts\Resources\PostListResource;
use App\Shared\Http\TypedResults;
use Dedoc\Scramble\Attributes\PathParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostController extends Controller
{
    public function __construct(
        private readonly PostReadServiceInterface $postReadService,
        private readonly CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    /**
     * @response PostListResource
     */
    #[Response(type: 'PostListResource')]
    public function index(IndexPostRequest $request): JsonResponse|JsonResource
    {
        $validated = $request->validated();

        $criteria = PostQueryCriteria::forListing(
            page: $validated['page'] ?? Pagination::MIN_PAGE,
            perPage: $validated['per_page'] ?? PostQueryCriteria::DEFAULT_PER_PAGE,
            keyword: $validated['keyword'] ?? null,
            hashtag: $validated['hashtag'] ?? null,
            topic: isset($validated['topic']) ? PostTopic::from((int) $validated['topic']) : null,
            sort: isset($validated['sort']) ? PostSort::from((string) $validated['sort']) : PostSort::DEFAULT,
        );

        $result = $this->postReadService->find($criteria);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return new PostListResource($result->getData());
    }

    /**
     * Canonical identity is the UUID. A positive legacy integer resolves the same
     * Post during migration; the response always carries the UUID.
     *
     * @response PostDetailResource
     */
    #[PathParameter('identifier', type: 'string')]
    #[Response(type: 'PostDetailResource')]
    public function show(string $identifier, Request $request): JsonResponse|JsonResource
    {
        $viewer = new Viewer(
            $this->currentUserProvider->currentAuthenticatedUser()?->id,
            (string) $request->ip(),
        );

        $result = $this->postReadService->findByIdentifier($identifier, $viewer);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        $detail = $result->getData();

        return new PostDetailResource(
            $detail->post,
            $detail->stats,
            $detail->hashtags,
        );
    }
}
