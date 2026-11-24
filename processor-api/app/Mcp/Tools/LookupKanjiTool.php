<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Application\JapaneseMaterial\Kanjis\Services\KanjiDetailServiceInterface;
use App\Application\JapaneseMaterial\Kanjis\Services\KanjiServiceInterface;
use App\Domain\JapaneseMaterial\Kanjis\DTOs\KanjiDetailIncludes;
use App\Domain\JapaneseMaterial\Kanjis\DTOs\KanjiDetailResultDTO;
use App\Domain\JapaneseMaterial\Kanjis\DTOs\KanjiListResultDTO;
use App\Domain\JapaneseMaterial\Kanjis\Queries\KanjiQueryCriteria;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiCharacter;
use App\Mcp\Presenters\KanjiPresenter;
use App\Mcp\Support\StudyLevel;
use App\Mcp\Support\ToolFailure;
use App\Shared\Enums\HttpStatus;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('lookup_kanji')]
#[Title('Look up a kanji')]
#[Description(<<<'TEXT'
Explain one kanji, or find kanji by English meaning. Send exactly one of character or meaning.
character: a single kanji such as 水. The result is that kanji with its meanings, onyomi and kunyomi readings, stroke count, JLPT level (N5 to N1, or null), school grade (or null), radicals and up to 5 example words with their reading and meaning. The example words are the first five dictionary entries that contain the kanji, not the most common ones.
meaning: an English word or phrase, written in lowercase such as water. The result is the kanji whose meanings contain it, without example words, paged with limit and page.
A kanji that is not in the dictionary gives an empty results list.
TEXT)]
#[IsReadOnly]
class LookupKanjiTool extends Tool
{
    private const DEFAULT_LIMIT = 5;

    private const MAX_LIMIT = 20;

    private const EXACTLY_ONE_MESSAGE = 'Send exactly one of character or meaning: character for one kanji (for example 水), meaning to find kanji by their English meaning (for example water).';

    public function __construct(private readonly KanjiPresenter $presenter)
    {
    }

    public function handle(
        Request $request,
        KanjiServiceInterface $kanjis,
        KanjiDetailServiceInterface $details,
    ): Response|ResponseFactory {
        try {
            $input = $request->validate($this->rules(), $this->messages());
        } catch (ValidationException $exception) {
            return ToolFailure::fromValidation($exception);
        }

        $character = trim((string) ($input['character'] ?? ''));
        $meaning = trim((string) ($input['meaning'] ?? ''));

        if (($character === '') === ($meaning === '')) {
            return Response::error(self::EXACTLY_ONE_MESSAGE);
        }

        if ($character !== '') {
            return $this->byCharacter($details, $character);
        }

        return $this->byMeaning(
            $kanjis,
            $meaning,
            (int) ($input['limit'] ?? self::DEFAULT_LIMIT),
            (int) ($input['page'] ?? 1),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'character' => $schema->string()
                ->description('One kanji to explain, for example 水. Send this or meaning, not both.'),
            'meaning' => $schema->string()
                ->description('An English word or phrase, in lowercase, to find kanji by meaning, for example water. Send this or character, not both.')
                ->max(KanjiQueryCriteria::KEYWORD_MAX_LENGTH),
            'limit' => $schema->integer()
                ->description('With meaning: how many kanji per page, 1 to '.self::MAX_LIMIT.'.')
                ->min(1)
                ->max(self::MAX_LIMIT)
                ->default(self::DEFAULT_LIMIT),
            'page' => $schema->integer()
                ->description('With meaning: which page of results, starting at 1. Ask for the next page while has_more is true.')
                ->min(1)
                ->default(1),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'results' => $schema->array()
                ->description('The kanji found: one for a character lookup, a page for a meaning search. Empty when nothing matches.')
                ->items($schema->object([
                    'uuid' => $schema->string()->required(),
                    'character' => $schema->string()->required(),
                    'meanings' => $schema->array()->items($schema->string())->required(),
                    'onyomi' => $schema->array()->items($schema->string())->description('Readings of Chinese origin, in katakana.')->required(),
                    'kunyomi' => $schema->array()->items($schema->string())->description('Readings of Japanese origin, in hiragana.')->required(),
                    'strokes' => $schema->integer()->description('How many strokes the character takes to write.')->required(),
                    'level' => $schema->string()->enum(StudyLevel::names())->nullable()->required(),
                    'grade' => $schema->integer()->description('The Japanese school grade that teaches it, or null.')->nullable()->required(),
                    'radicals' => $schema->array()->items($schema->string())->required(),
                    'example_words' => $schema->array()
                        ->description('Only for a character lookup: up to 5 words that contain the kanji.')
                        ->items($schema->object([
                            'word' => $schema->string()->required(),
                            'reading' => $schema->string()->required(),
                            'meaning' => $schema->string()->required(),
                        ])),
                ]))
                ->required(),
            'total' => $schema->integer()->description('How many kanji match in all.')->required(),
            'has_more' => $schema->boolean()->description('Whether a later page has more kanji.')->required(),
        ];
    }

    private function byCharacter(KanjiDetailServiceInterface $details, string $character): Response|ResponseFactory
    {
        if (! KanjiCharacter::isValid($character)) {
            return Response::error('character: must be a single kanji, for example 水. Kana, Latin letters, digits and longer text are not accepted.');
        }

        $found = $details->findByIdentifier($character, new KanjiDetailIncludes(words: true));

        if ($found->isFailure()) {
            return $found->getError()->status === HttpStatus::NOT_FOUND
                ? $this->results([], 0, false)
                : ToolFailure::fromResult($found);
        }

        /** @var KanjiDetailResultDTO $detail */
        $detail = $found->getData();

        return $this->results(
            [$this->presenter->presentWithExamples($detail->kanji, $detail->words->items ?? [])],
            1,
            false,
        );
    }

    private function byMeaning(KanjiServiceInterface $kanjis, string $meaning, int $limit, int $page): Response|ResponseFactory
    {
        $found = $kanjis->find(KanjiQueryCriteria::forListing(
            perPage: $limit,
            page: $page,
            meanings: [$meaning],
        ));

        if ($found->isFailure()) {
            return ToolFailure::fromResult($found);
        }

        /** @var KanjiListResultDTO $list */
        $list = $found->getData();

        return $this->results(
            array_map($this->presenter->present(...), $list->items),
            $list->pagination['total'],
            $list->pagination['has_more'],
        );
    }

    /**
     * @param array<int, array<string, mixed>> $results
     */
    private function results(array $results, int $total, bool $hasMore): ResponseFactory
    {
        return Response::structured([
            'results' => $results,
            'total' => $total,
            'has_more' => $hasMore,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'character' => ['nullable', 'string'],
            'meaning' => ['nullable', 'string', 'max:'.KanjiQueryCriteria::KEYWORD_MAX_LENGTH],
            'limit' => ['nullable', 'integer', 'between:1,'.self::MAX_LIMIT],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        $limit = 'must be a whole number from 1 to '.self::MAX_LIMIT.'.';
        $page = 'must be a whole number, 1 or more.';

        return [
            'character.string' => 'must be text: one kanji, for example 水.',
            'meaning.string' => 'must be text: an English word, for example water.',
            'meaning.max' => 'must be at most '.KanjiQueryCriteria::KEYWORD_MAX_LENGTH.' characters.',
            'limit.integer' => $limit,
            'limit.between' => $limit,
            'page.integer' => $page,
            'page.min' => $page,
        ];
    }
}
