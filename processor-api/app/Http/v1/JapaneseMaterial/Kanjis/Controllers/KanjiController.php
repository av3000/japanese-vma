<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Kanjis\Controllers;

use App\Application\JapaneseMaterial\Kanjis\Services\KanjiServiceInterface;
use App\Http\Controllers\Controller;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiCharacter;
use App\Domain\JapaneseMaterial\Kanjis\Queries\KanjiQueryCriteria;
use App\Http\v1\JapaneseMaterial\Kanjis\Requests\IndexKanjiRequest;
use App\Http\v1\JapaneseMaterial\Kanjis\Resources\KanjiResource;
use App\Http\v1\JapaneseMaterial\Kanjis\Resources\KanjiCollectionResource;
use App\Shared\Enums\HttpStatus;
use App\Shared\Http\TypedResults;
use Illuminate\Http\JsonResponse;

class KanjiController extends Controller
{
    public function __construct(
        private readonly KanjiServiceInterface $kanjiService
    ) {}

    public function index(IndexKanjiRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        $criteria = KanjiQueryCriteria::forListing(
            page: $validatedData['page'] ?? 1,
            perPage: $validatedData['per_page'] ?? 10,
            character: $validatedData['character'] ?? null,
            grade: $validatedData['grade'] ?? null,
            jlpt: $validatedData['jlpt'] ?? null,
            minStrokeCount: $validatedData['min_stroke_count'] ?? null,
            maxStrokeCount: $validatedData['max_stroke_count'] ?? null,
            meanings: $validatedData['meanings'] ?? null,
            onyomi: $validatedData['onyomi'] ?? null,
            kunyomi: $validatedData['kunyomi'] ?? null,
            radical: $validatedData['radical'] ?? null,
            limit: $validatedData['limit'] ?? null,
            offset: $validatedData['offset'] ?? null
        );

        $paginatedKanjisResult = $this->kanjiService->find($criteria);

        if ($paginatedKanjisResult->isFailure()) {
            return TypedResults::fromError($paginatedKanjisResult->getError());
        }

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginatedKanjis */
        $paginatedKanjis = $paginatedKanjisResult->getData();

        return TypedResults::ok(
            new KanjiCollectionResource($paginatedKanjis)
        );
    }

    public function show(string $identifier): JsonResponse
    {
        $kanjiResult = null;

        if (EntityId::isValid($identifier)) {
            $kanjiResult = $this->kanjiService->findByUuid(EntityId::from($identifier));
        } elseif (preg_match('/^\p{Han}$/u', $identifier)) {
            $kanjiResult = $this->kanjiService->findByCharacter(new KanjiCharacter($identifier));
        } else {
            return TypedResults::fromError(new \App\Shared\Results\Error(
                'INVALID_IDENTIFIER',
                HttpStatus::BAD_REQUEST,
                'Identifier must be a valid UUID or a single Kanji character.'
            ));
        }


        if ($kanjiResult->isFailure()) {
            return TypedResults::fromError($kanjiResult->getError());
        }

        /** @var \App\Domain\JapaneseMaterial\Kanjis\Models\Kanji $kanji */
        $kanji = $kanjiResult->getData();

        return TypedResults::ok(
            new KanjiResource($kanji)
        );
    }
}
