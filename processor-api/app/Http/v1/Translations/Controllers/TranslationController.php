<?php

declare(strict_types=1);

namespace App\Http\v1\Translations\Controllers;

use App\Application\Translations\Services\TranslationServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\v1\Translations\Resources\TranslationBundleResource;
use App\Http\v1\Translations\Resources\TranslationLocaleListResource;
use App\Shared\Http\TypedResults;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class TranslationController extends Controller
{
    public function __construct(
        private readonly TranslationServiceInterface $translationService,
    ) {
    }

    /**
     * The languages the interface can be shown in.
     *
     * Public. Only active locales are listed, and `is_fallback` marks English, the language every
     * other bundle falls back to.
     */
    #[Response(type: 'TranslationLocaleListResource')]
    public function locales(): JsonResponse|JsonResource
    {
        $result = $this->translationService->activeLocales();

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return new TranslationLocaleListResource($result->getData());
    }

    /**
     * One complete language bundle.
     *
     * Public. `messages` holds every interface label as a flat map of dotted key to text, so a
     * client never has to resolve a missing label itself. An unknown or inactive locale answers 404.
     */
    #[Response(type: 'TranslationBundleResource')]
    public function show(string $locale): JsonResponse|JsonResource
    {
        $result = $this->translationService->bundle($locale);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return new TranslationBundleResource($result->getData());
    }
}
