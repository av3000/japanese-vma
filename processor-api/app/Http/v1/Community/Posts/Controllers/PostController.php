<?php

declare(strict_types=1);

namespace App\Http\v1\Community\Posts\Controllers;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Auth\Interfaces\Providers\CurrentUserProviderInterface;
use App\Application\Community\Posts\Services\PostReadServiceInterface;
use App\Application\Community\Posts\Services\PostWriteServiceInterface;
use App\Domain\Community\Posts\DTOs\PostCreateDTO;
use App\Domain\Community\Posts\DTOs\PostDetailResultDTO;
use App\Domain\Community\Posts\DTOs\PostUpdateDTO;
use App\Domain\Community\Posts\Enums\PostSort;
use App\Domain\Community\Posts\Enums\PostTopic;
use App\Domain\Community\Posts\Queries\PostQueryCriteria;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\Viewer;
use App\Http\Controllers\Controller;
use App\Http\v1\Community\Posts\Requests\IndexPostRequest;
use App\Http\v1\Community\Posts\Requests\LockPostRequest;
use App\Http\v1\Community\Posts\Requests\StorePostRequest;
use App\Http\v1\Community\Posts\Requests\UpdatePostRequest;
use App\Http\v1\Community\Posts\Resources\PostDetailResource;
use App\Http\v1\Community\Posts\Resources\PostListResource;
use App\Http\v1\Community\Posts\Resources\PostLockResource;
use App\Shared\Http\TypedResults;
use Dedoc\Scramble\Attributes\PathParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostController extends Controller
{
    public function __construct(
        private readonly PostReadServiceInterface $postReadService,
        private readonly PostWriteServiceInterface $postWriteService,
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
        $result = $this->postReadService->findByIdentifier($identifier, $this->viewerFrom($request));

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return $this->detailResource($result->getData());
    }

    /**
     * @response PostDetailResource
     */
    #[Response(201, type: 'PostDetailResource')]
    public function store(StorePostRequest $request): JsonResponse
    {
        $result = $this->postWriteService->create(
            PostCreateDTO::fromValidated($request->validated()),
            $this->requiredAuthenticatedUser(),
            $this->viewerFrom($request),
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return $this->detailResource($result->getData())
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Owner-only. Admins moderate a Post by locking or deleting it, not by
     * editing another author's words.
     *
     * @response PostDetailResource
     */
    #[PathParameter('uuid', type: 'string', format: 'uuid')]
    #[Response(type: 'PostDetailResource')]
    public function update(UpdatePostRequest $request, string $uuid): JsonResponse|JsonResource
    {
        $result = $this->postWriteService->update(
            EntityId::from($uuid),
            PostUpdateDTO::fromValidated($request->validated()),
            $this->requiredAuthenticatedUser(),
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return $this->detailResource($result->getData());
    }

    #[PathParameter('uuid', type: 'string', format: 'uuid')]
    #[Response(204)]
    public function destroy(string $uuid): JsonResponse
    {
        $result = $this->postWriteService->delete(
            EntityId::from($uuid),
            $this->requiredAuthenticatedUser(),
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return TypedResults::noContent();
    }

    /**
     * Admin-only and idempotent: the body carries the desired state, so a
     * retry cannot flip a Post back the way the legacy toggle could.
     *
     * @response PostLockResource
     */
    #[PathParameter('uuid', type: 'string', format: 'uuid')]
    #[Response(type: 'PostLockResource')]
    public function lock(LockPostRequest $request, string $uuid): JsonResponse|JsonResource
    {
        $result = $this->postWriteService->setLocked(
            EntityId::from($uuid),
            (bool) $request->validated('locked'),
            $this->requiredAuthenticatedUser(),
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return new PostLockResource($result->getData());
    }

    private function detailResource(PostDetailResultDTO $detail): PostDetailResource
    {
        return new PostDetailResource(
            $detail->post,
            $detail->stats,
            $detail->hashtags,
        );
    }

    private function viewerFrom(Request $request): Viewer
    {
        return new Viewer(
            $this->currentUserProvider->currentAuthenticatedUser()?->id,
            (string) $request->ip(),
        );
    }

    private function requiredAuthenticatedUser(): AuthenticatedUser
    {
        return $this->currentUserProvider->currentAuthenticatedUser()
            ?? throw new AuthenticationException;
    }
}
