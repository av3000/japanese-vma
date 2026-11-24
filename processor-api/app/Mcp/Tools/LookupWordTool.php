<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Application\JapaneseMaterial\Words\Services\WordServiceInterface;
use App\Domain\JapaneseMaterial\Words\DTOs\WordListResultDTO;
use App\Domain\JapaneseMaterial\Words\Models\Word;
use App\Domain\JapaneseMaterial\Words\Queries\WordQueryCriteria;
use App\Mcp\Presenters\WordPresenter;
use App\Mcp\Support\StudyLevel;
use App\Mcp\Support\ToolFailure;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('lookup_word')]
#[Title('Look up a word')]
#[Description(<<<'TEXT'
Look up Japanese dictionary words by headword (kanji or kana), reading or English meaning.
A word whose headword equals the query is listed first; the rest follow in dictionary order.
Each result has uuid, word, reading, meanings, level (N5 to N1, or null) and types.
Word levels may be missing: no dictionary word has a level assigned yet, so level is null for every result and a level filter finds nothing until levels are filled in.
TEXT)]
#[IsReadOnly]
class LookupWordTool extends Tool
{
    private const DEFAULT_LIMIT = 5;

    private const MAX_LIMIT = 20;

    public function __construct(private readonly WordPresenter $presenter)
    {
    }

    public function handle(Request $request, WordServiceInterface $words): Response|ResponseFactory
    {
        try {
            $input = $request->validate($this->rules(), $this->messages());
        } catch (ValidationException $exception) {
            return ToolFailure::fromValidation($exception);
        }

        $query = trim($input['query']);
        $level = isset($input['level']) ? StudyLevel::from($input['level']) : null;
        $limit = (int) ($input['limit'] ?? self::DEFAULT_LIMIT);
        $page = (int) ($input['page'] ?? 1);

        $exact = $this->exactHeadword($words, $query, $level);
        $window = $this->fetchWindow($words, $query, $level, $limit, $page, $exact);

        if ($window instanceof Response) {
            return $window;
        }

        [$items, $total] = $window;

        return Response::structured([
            'results' => array_map($this->presenter->present(...), $items),
            'total' => $total,
            'has_more' => $page * $limit < $total,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('A Japanese word (kanji or kana), a reading, or an English meaning to search for.')
                ->min(1)
                ->max(WordQueryCriteria::KEYWORD_MAX_LENGTH)
                ->required(),
            'level' => $schema->string()
                ->description('Only words of this JLPT level. Word levels may be missing, so this can return nothing.')
                ->enum(StudyLevel::names()),
            'limit' => $schema->integer()
                ->description('How many words per page, 1 to '.self::MAX_LIMIT.'.')
                ->min(1)
                ->max(self::MAX_LIMIT)
                ->default(self::DEFAULT_LIMIT),
            'page' => $schema->integer()
                ->description('Which page of results, starting at 1. Ask for the next page while has_more is true.')
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
                ->description('The words on this page.')
                ->items($schema->object([
                    'uuid' => $schema->string()->required(),
                    'word' => $schema->string()->description('The headword.')->required(),
                    'reading' => $schema->string()->description('The reading in kana.')->required(),
                    'meanings' => $schema->array()->items($schema->string())->required(),
                    'level' => $schema->string()->enum(StudyLevel::names())->nullable()->required(),
                    'types' => $schema->array()->items($schema->string())->description('Parts of speech.')->required(),
                ]))
                ->required(),
            'total' => $schema->integer()->description('How many words match in all.')->required(),
            'has_more' => $schema->boolean()->description('Whether a later page has more words.')->required(),
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'query' => ['required', 'string', 'max:'.WordQueryCriteria::KEYWORD_MAX_LENGTH],
            'level' => ['nullable', 'string', Rule::in(StudyLevel::names())],
            'limit' => ['nullable', 'integer', 'between:1,'.self::MAX_LIMIT],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        $levels = implode(', ', StudyLevel::names());
        $limit = 'must be a whole number from 1 to '.self::MAX_LIMIT.'.';
        $page = 'must be a whole number, 1 or more.';

        return [
            'query.required' => 'is required: a Japanese word, a reading or an English meaning, 1 to '.WordQueryCriteria::KEYWORD_MAX_LENGTH.' characters.',
            'query.string' => 'must be text, 1 to '.WordQueryCriteria::KEYWORD_MAX_LENGTH.' characters.',
            'query.max' => 'must be at most '.WordQueryCriteria::KEYWORD_MAX_LENGTH.' characters.',
            'level.string' => "must be one of {$levels}.",
            'level.in' => "must be one of {$levels}.",
            'limit.integer' => $limit,
            'limit.between' => $limit,
            'page.integer' => $page,
            'page.min' => $page,
        ];
    }

    /**
     * The word whose headword is exactly the query, when it also passes the level filter.
     *
     * The search is a substring match ordered by id, so on its own a short common word such as 水
     * can sit behind dozens of compounds that contain it.
     */
    private function exactHeadword(WordServiceInterface $words, string $query, ?StudyLevel $level): ?Word
    {
        $found = $words->findByIdentifier($query);

        if ($found->isFailure()) {
            return null;
        }

        $word = $found->getData();

        if (! $word instanceof Word || $word->getSurface() !== $query) {
            return null;
        }

        if ($level !== null && StudyLevel::fromDictionary($word->getJlpt()) !== $level) {
            return null;
        }

        return $word;
    }

    /**
     * One page of matches, with the exact headword moved to the front of the whole list.
     *
     * The service pages by id, so moving one word to the front shifts every later word one place.
     * To keep the pages free of gaps and repeats, this reads the service page that holds the
     * wanted positions and the one before it, and works out where each word now stands. A word
     * sorts after the exact one exactly when its id is higher.
     *
     * @return array{array<int, Word>, int}|Response
     */
    private function fetchWindow(
        WordServiceInterface $words,
        string $query,
        ?StudyLevel $level,
        int $limit,
        int $page,
        ?Word $exact,
    ): array|Response {
        $fetched = [];

        foreach (array_unique($exact === null || $page === 1 ? [$page] : [$page - 1, $page]) as $number) {
            $result = $words->find(WordQueryCriteria::forListing(
                page: $number,
                perPage: $limit,
                keyword: $query,
                jlpt: $level?->toDictionary(),
            ));

            if ($result->isFailure()) {
                return ToolFailure::fromResult($result);
            }

            /** @var WordListResultDTO $list */
            $list = $result->getData();
            $fetched[$number] = $list;
        }

        $total = $fetched[$page]->pagination['total'];

        if ($exact === null) {
            return [$fetched[$page]->items, $total];
        }

        $first = ($page - 1) * $limit;
        $positioned = $page === 1 ? [0 => $exact] : [];
        $windowStart = (max($page - 1, 1) - 1) * $limit;

        foreach ($fetched as $list) {
            foreach ($list->items as $offset => $word) {
                if ($word->getIdValue() === $exact->getIdValue()) {
                    continue;
                }

                $positioned[$windowStart + $offset + ($word->getIdValue() > $exact->getIdValue() ? 0 : 1)] = $word;
            }

            $windowStart += $limit;
        }

        ksort($positioned);

        return [
            array_values(array_filter(
                $positioned,
                static fn (int $position): bool => $position >= $first && $position < $first + $limit,
                ARRAY_FILTER_USE_KEY,
            )),
            $total,
        ];
    }
}
