<?php

namespace App\Http\v1\Catalogues\Controllers;

use App\Domain\Catalogues\DTOs\CatalogueListDTO;
use Illuminate\Auth\Request;

use App\Http\Controllers\Controller;
use App\Http\v1\Catalogues\Requests\IndexCatalogueRequest;
use App\Shared\Http\TypedResults;
use Illuminate\Http\JsonResponse;

class CatalogueController extends Controller
{
    public function __construct(
        private readonly CatalogueServiceInterface $catalogueService
    ) {}

    public function index(IndexCatalogueRequest $request): JsonResponse
    {
        $catalogueDTO = CatalogueListDTO::fromRequest($request->validated());

        $paginatedCatalogues = $this->catalogueService->getCatalogueList($catalogueDTO, auth('api')->user());

        return TypedResults::ok(['data' => true]);
    }
}
