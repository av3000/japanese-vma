<?php

declare(strict_types=1);

namespace App\Application\Study\Actions;

use App\Application\Study\Interfaces\Readers\DistractorPoolReaderInterface;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Study\Models\Flashcard;
use App\Domain\Study\Support\AnswerNormalizer;
use App\Domain\Study\ValueObjects\FlashcardConfig;
use Random\Randomizer;

/**
 * Gives every card in options mode exactly OPTION_COUNT choices: its own display answer
 * plus distractors.
 *
 * Distractors come from the deck's other eligible cards first (all of them, not only the
 * cut), and only cards whose accepted answers share nothing with the correct card's after
 * normalization: 学 and 習 both mean "learn", so neither may be the other's wrong answer.
 * When the deck cannot supply three, the dictionary pool fills in, preferring the card's
 * JLPT level or stroke count. Pools are fetched once per level and reused across cards, so
 * the number of queries is bounded by the number of distinct levels in the deck, not by
 * its size.
 */
final class BuildDistractorsAction
{
    public const OPTION_COUNT = 4;

    private const POOL_SIZE = 24;

    public function __construct(
        private readonly DistractorPoolReaderInterface $distractorPool,
    ) {
    }

    /**
     * @param list<Flashcard> $deck The cards that will be served.
     * @param list<Flashcard> $eligible Every eligible card of the catalogue; the deck is a subset.
     * @param int[] $catalogueItemIds Every item of the catalogue, excluded from the pool.
     *
     * @return list<Flashcard> The deck with `options` filled, same order.
     */
    public function execute(
        array $deck,
        array $eligible,
        FlashcardConfig $config,
        SavedListType $baseType,
        array $catalogueItemIds,
        Randomizer $randomizer,
    ): array {
        $answerSets = [];

        foreach ($eligible as $card) {
            $answerSets[$card->itemId] = AnswerNormalizer::normalizeAll($card->acceptedAnswers, $config->answer);
        }

        /** @var array<string, list<Flashcard>> $pools */
        $pools = [];

        return array_map(function (Flashcard $card) use ($eligible, $answerSets, $config, $baseType, $catalogueItemIds, $randomizer, &$pools): Flashcard {
            $correct = $answerSets[$card->itemId] ?? AnswerNormalizer::normalizeAll($card->acceptedAnswers, $config->answer);
            $used = [AnswerNormalizer::normalize($card->displayAnswer, $config->answer) => true];
            $distractors = [];

            $candidates = array_values(array_filter(
                $eligible,
                static fn (Flashcard $other): bool => $other->itemId !== $card->itemId
                    && array_intersect($answerSets[$other->itemId] ?? [], $correct) === [],
            ));

            $this->take($randomizer->shuffleArray($candidates), $config, $used, $distractors);

            if (count($distractors) < self::OPTION_COUNT - 1) {
                $preferred = $this->pool($pools, $baseType, $config, $catalogueItemIds, $card->jlpt, $card->strokes);
                $this->take($this->disjoint($preferred, $correct, $config), $config, $used, $distractors);
            }

            if (count($distractors) < self::OPTION_COUNT - 1) {
                $any = $this->pool($pools, $baseType, $config, $catalogueItemIds, null, null);
                $this->take($this->disjoint($any, $correct, $config), $config, $used, $distractors);
            }

            return $card->withOptions($randomizer->shuffleArray([$card->displayAnswer, ...$distractors]));
        }, $deck);
    }

    /**
     * Appends candidates' display answers to `$distractors` until three are collected,
     * skipping any whose normalized text is already an option.
     *
     * @param list<Flashcard> $candidates
     * @param array<string, true> $used
     * @param list<string> $distractors
     */
    private function take(array $candidates, FlashcardConfig $config, array &$used, array &$distractors): void
    {
        foreach ($candidates as $candidate) {
            if (count($distractors) >= self::OPTION_COUNT - 1) {
                return;
            }

            $key = AnswerNormalizer::normalize($candidate->displayAnswer, $config->answer);

            if ($key === '' || isset($used[$key])) {
                continue;
            }

            $used[$key] = true;
            $distractors[] = $candidate->displayAnswer;
        }
    }

    /**
     * @param list<Flashcard> $cards
     * @param list<string> $correct
     *
     * @return list<Flashcard>
     */
    private function disjoint(array $cards, array $correct, FlashcardConfig $config): array
    {
        return array_values(array_filter(
            $cards,
            static fn (Flashcard $card): bool => array_intersect(
                AnswerNormalizer::normalizeAll($card->acceptedAnswers, $config->answer),
                $correct,
            ) === [],
        ));
    }

    /**
     * @param array<string, list<Flashcard>> $pools
     * @param int[] $catalogueItemIds
     *
     * @return list<Flashcard>
     */
    private function pool(
        array &$pools,
        SavedListType $baseType,
        FlashcardConfig $config,
        array $catalogueItemIds,
        ?string $jlpt,
        ?int $strokes,
    ): array {
        $preferJlpt = $baseType === SavedListType::RADICALS ? null : $jlpt;
        $preferStrokes = $baseType === SavedListType::RADICALS ? $strokes : null;
        $key = ($preferJlpt ?? '').'|'.($preferStrokes ?? '');

        if (! array_key_exists($key, $pools)) {
            $pools[$key] = $this->distractorPool->sample(
                $baseType,
                $config,
                $catalogueItemIds,
                $preferJlpt,
                $preferStrokes,
                self::POOL_SIZE,
            );
        }

        return $pools[$key];
    }
}
