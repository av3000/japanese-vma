<?php

declare(strict_types=1);

namespace App\Application\Study\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Catalogues\Services\CatalogueServiceInterface;
use App\Application\JapaneseMaterial\Kanjis\Interfaces\Repositories\KanjiRepositoryInterface;
use App\Application\JapaneseMaterial\Radicals\Interfaces\Repositories\RadicalRepositoryInterface;
use App\Application\JapaneseMaterial\Words\Interfaces\Repositories\WordRepositoryInterface;
use App\Application\Study\Actions\BuildDistractorsAction;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji;
use App\Domain\JapaneseMaterial\Radicals\Models\Radical;
use App\Domain\JapaneseMaterial\Words\Models\Word;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Study\DTOs\EligibleCardsDTO;
use App\Domain\Study\DTOs\FlashcardDeckDTO;
use App\Domain\Study\Enums\AnswerMode;
use App\Domain\Study\Errors\StudyErrors;
use App\Domain\Study\Factories\FlashcardFactory;
use App\Domain\Study\Models\Flashcard;
use App\Domain\Study\ValueObjects\FlashcardConfig;
use App\Domain\Study\ValueObjects\FlashcardQuestion;
use App\Shared\Results\Result;
use Random\Engine\Mt19937;
use Random\Randomizer;

final class FlashcardDeckService implements FlashcardDeckServiceInterface
{
    public function __construct(
        private readonly CatalogueServiceInterface $catalogueService,
        private readonly KanjiRepositoryInterface $kanjiRepository,
        private readonly WordRepositoryInterface $wordRepository,
        private readonly RadicalRepositoryInterface $radicalRepository,
        private readonly FlashcardFactory $flashcardFactory,
        private readonly BuildDistractorsAction $buildDistractors,
    ) {
    }

    public function buildDeck(EntityId $catalogueUuid, FlashcardConfig $config, ?AuthenticatedUser $viewer): Result
    {
        $catalogueResult = $this->catalogueService->getViewableCatalogue($catalogueUuid, $viewer);

        if ($catalogueResult->isFailure()) {
            return $catalogueResult;
        }

        /** @var Catalogue $catalogue */
        $catalogue = $catalogueResult->getData();
        $type = $catalogue->getType();
        $question = $config->question;
        $baseType = FlashcardQuestion::baseType($type);

        if ($baseType === null) {
            return Result::failure(StudyErrors::catalogueTypeNotSupported($catalogueUuid->value(), $type->label()));
        }

        if (! $question->isValidFor($type)) {
            return Result::failure(StudyErrors::invalidFieldCombination(
                $catalogueUuid->value(),
                $question->prompt->value,
                $question->answer->value,
                $question->mode->value,
            ));
        }

        $itemIdsResult = $this->catalogueService->getCatalogueItemIds($catalogue->getIdValue());

        if ($itemIdsResult->isFailure()) {
            return $itemIdsResult;
        }

        /** @var int[] $itemIds */
        $itemIds = $itemIdsResult->getData();
        $source = new EligibleCardsDTO($baseType, $this->mapCards($baseType, $itemIds, $question), $itemIds);

        if ($source->cards === []) {
            return Result::failure(StudyErrors::noEligibleCards($catalogueUuid->value(), $question->answer->value));
        }

        $randomizer = new Randomizer(new Mt19937($config->seed));
        $deck = array_values(array_slice($randomizer->shuffleArray($source->cards), 0, $config->count));

        if ($question->mode === AnswerMode::OPTIONS) {
            $deck = $this->buildDistractors->execute($deck, $source, $question, $config->seed);
        }

        return Result::success(new FlashcardDeckDTO(
            catalogue: $catalogue,
            config: $config,
            cards: $deck,
            totalItems: count($itemIds),
            eligibleItems: count($source->cards),
            excludedEmptyAnswerField: count($itemIds) - count($source->cards),
        ));
    }

    /**
     * @param int[] $itemIds
     *
     * @return list<Flashcard>
     */
    private function mapCards(SavedListType $baseType, array $itemIds, FlashcardQuestion $question): array
    {
        if ($itemIds === []) {
            return [];
        }

        $cards = match ($baseType) {
            SavedListType::KANJIS => array_map(
                fn (Kanji $kanji): ?Flashcard => $this->flashcardFactory->fromKanji($kanji, $question),
                $this->kanjiRepository->findByIds($itemIds),
            ),
            SavedListType::WORDS => array_map(
                fn (Word $word): ?Flashcard => $this->flashcardFactory->fromWord($word, $question),
                $this->wordRepository->findByIds($itemIds),
            ),
            SavedListType::RADICALS => array_map(
                fn (Radical $radical): ?Flashcard => $this->flashcardFactory->fromRadical($radical, $question),
                $this->radicalRepository->findByIds($itemIds),
            ),
            default => [],
        };

        return array_values(array_filter($cards));
    }
}
