<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Radicals\Controllers;

use App\Application\Auth\Interfaces\Providers\CurrentUserProviderInterface;
use App\Application\Catalogues\Services\CatalogueServiceInterface;
use App\Application\JapaneseMaterial\Radicals\Services\RadicalServiceInterface;
use App\Domain\Catalogues\Errors\CatalogueErrors;
use App\Domain\JapaneseMaterial\Radicals\Queries\RadicalQueryCriteria;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Http\Controllers\Controller;
use App\Http\v1\JapaneseMaterial\Radicals\Requests\IndexRadicalRequest;
use App\Http\v1\JapaneseMaterial\Radicals\Resources\RadicalListResource;
use App\Http\v1\JapaneseMaterial\Radicals\Resources\RadicalResource;
use App\Shared\Http\TypedResults;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class RadicalController extends Controller
{
    public function __construct(
        private readonly RadicalServiceInterface $radicalService,
        private readonly CatalogueServiceInterface $catalogueService,
        private readonly CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    #[Response(type: 'RadicalListResource')]
    public function index(IndexRadicalRequest $request): JsonResponse|JsonResource
    {
        $validated = $request->validated();
        $catalogueId = null;

        if (isset($validated['catalogue_uuid'])) {
            $source = $this->catalogueService->resolveItemSource(
                EntityId::from($validated['catalogue_uuid']),
                SavedListType::RADICALS,
                $this->currentUserProvider->currentAuthenticatedUser(),
            );

            if ($source->isFailure()) {
                $error = $source->getError();

                // A filter on the wrong kind of catalogue is a rule about one field, so it comes back as a 422 on it.
                if ($error->code === CatalogueErrors::ITEM_FAMILY_MISMATCH) {
                    return TypedResults::validationProblem(['catalogue_uuid' => [(string) $error->errorMessage]]);
                }

                return TypedResults::fromError($error);
            }

            $catalogueId = $source->getData();
        }

        $criteria = RadicalQueryCriteria::forListing(
            page: $validated['page'] ?? Pagination::MIN_PAGE,
            perPage: $validated['per_page'] ?? RadicalQueryCriteria::DEFAULT_PER_PAGE,
            keyword: $validated['keyword'] ?? null,
            radical: $validated['radical'] ?? null,
            meaning: $validated['meaning'] ?? null,
            hiragana: $validated['hiragana'] ?? null,
            strokes: $validated['strokes'] ?? null,
            catalogueId: $catalogueId,
        );

        $result = $this->radicalService->find($criteria);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return new RadicalListResource($result->getData());
    }

    #[Response(type: 'RadicalResource')]
    public function show(string $identifier): JsonResponse|JsonResource
    {
        $result = $this->radicalService->findByIdentifier($identifier, withKanjis: true);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return new RadicalResource($result->getData(), includeKanjis: true);
    }
}
