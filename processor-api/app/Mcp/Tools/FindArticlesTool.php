<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Application\Articles\Services\ArticleListServiceInterface;
use App\Domain\Articles\DTOs\ArticleListIncludes;
use App\Domain\Articles\DTOs\ArticleListResultDTO;
use App\Domain\Articles\Enums\ArticleJlptLevel;
use App\Domain\Articles\Queries\ArticleQueryCriteria;
use App\Domain\Articles\ValueObjects\ArticleSortCriteria;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\SearchTerm;
use App\Mcp\Presenters\ArticleSummaryPresenter;
use App\Mcp\Support\StudyLevel;
use App\Mcp\Support\ToolFailure;
use Closure;
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

#[Name('find_articles')]
#[Title('Find articles to read')]
#[Description(<<<'TEXT'
Find public Japanese reading articles, newest first, by a keyword in the title and by JLPT level.
The query matches the Japanese or the English title, not the article text. Both inputs are optional: with neither, the newest articles are listed.
Each result has uuid, title_jp, title_en, preview_jp (the start of the text), main_level, level_mix, tags and created_at.
The levels filter keeps an article when it has at least one kanji of any chosen level, so an N4 search also returns articles that are mostly harder. Do not read the filter as the article's difficulty: choose by main_level, the easiest level (N5 to N1) whose kanji, with every easier level's, make up at least 90% of the article's kanji.
main_level is null when the article has no kanji, or when too many of its kanji are uncommon to reach 90% even with every level. level_mix gives the count of kanji per level for a closer look.
TEXT)]
#[IsReadOnly]
class FindArticlesTool extends Tool
{
    private const DEFAULT_LIMIT = 5;

    private const MAX_LIMIT = 20;

    /** The website refuses an offset past 10,000 rows; with the largest page this is the last page that is still allowed. */
    private const MAX_PAGE = 500;

    public function __construct(private readonly ArticleSummaryPresenter $presenter)
    {
    }

    public function handle(Request $request, ArticleListServiceInterface $articles): Response|ResponseFactory
    {
        try {
            $input = $request->validate($this->rules(), $this->messages());
        } catch (ValidationException $exception) {
            return ToolFailure::fromValidation($exception);
        }

        $result = $articles->list($this->criteria($input), $this->includes());

        if ($result->isFailure()) {
            return ToolFailure::fromResult($result);
        }

        /** @var ArticleListResultDTO $list */
        $list = $result->getData();

        return Response::structured([
            'results' => array_map($this->presenter->present(...), $list->items),
            'total' => $list->pagination->total,
            'has_more' => $list->pagination->hasMore,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('A word to look for in the Japanese or English title. At most '.SearchTerm::MAX_LENGTH.' bytes, about '.intdiv(SearchTerm::MAX_LENGTH, 3).' Japanese characters. One Japanese character is enough; a single letter or digit is too short.')
                ->max(SearchTerm::MAX_LENGTH),
            'levels' => $schema->array()
                ->description('Keep articles that have at least one kanji of any of these JLPT levels. This does not say how hard the article is: choose by main_level in the results.')
                ->items($schema->string()->enum(StudyLevel::names())),
            'limit' => $schema->integer()
                ->description('How many articles per page, 1 to '.self::MAX_LIMIT.'.')
                ->min(1)
                ->max(self::MAX_LIMIT)
                ->default(self::DEFAULT_LIMIT),
            'page' => $schema->integer()
                ->description('Which page of results, starting at 1. Ask for the next page while has_more is true.')
                ->min(1)
                ->max(self::MAX_PAGE)
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
                ->description('The articles on this page, newest first. Empty when nothing matches.')
                ->items($schema->object([
                    'uuid' => $schema->string()->required(),
                    'title_jp' => $schema->string()->required(),
                    'title_en' => $schema->string()->nullable()->required(),
                    'preview_jp' => $schema->string()->description('The start of the Japanese text.')->required(),
                    'main_level' => $schema->string()
                        ->enum(StudyLevel::names())
                        ->nullable()
                        ->description('How hard the article is: the easiest level whose kanji, with every easier level, make up at least 90% of its kanji. Null when it has no kanji, or when too many are uncommon to reach 90%.')
                        ->required(),
                    'level_mix' => $schema->object([
                        'N5' => $schema->integer()->required(),
                        'N4' => $schema->integer()->required(),
                        'N3' => $schema->integer()->required(),
                        'N2' => $schema->integer()->required(),
                        'N1' => $schema->integer()->required(),
                        'uncommon' => $schema->integer()->description('Kanji with no JLPT level.')->required(),
                    ])->description('How many kanji the article has at each level.')->required(),
                    'tags' => $schema->array()->items($schema->string())->required(),
                    'created_at' => $schema->string()->description('ISO 8601 date and time.')->required(),
                ]))
                ->required(),
            'total' => $schema->integer()->description('How many articles match in all.')->required(),
            'has_more' => $schema->boolean()->description('Whether a later page has more articles.')->required(),
        ];
    }

    /**
     * @param array<string, mixed> $input
     */
    private function criteria(array $input): ArticleQueryCriteria
    {
        $levels = array_map(
            static fn (string $level): ArticleJlptLevel => ArticleJlptLevel::from(StudyLevel::from($level)->toArticle()),
            array_values(array_unique($input['levels'] ?? [])),
        );

        // Validation skips a blank query, and the value object refuses an empty one: blank means no search.
        $query = trim((string) ($input['query'] ?? ''));

        return new ArticleQueryCriteria(
            sort: ArticleSortCriteria::default(),
            pagination: new Pagination((int) ($input['page'] ?? 1), (int) ($input['limit'] ?? self::DEFAULT_LIMIT)),
            search: $query === '' ? null : new SearchTerm($query),
            jlptLevels: $levels,
        );
    }

    /**
     * Only the tags: the presenter reads no engagement counts or processing state, and each include
     * costs a query.
     */
    private function includes(): ArticleListIncludes
    {
        return new ArticleListIncludes(
            includeStats: false,
            includeHashtags: true,
            includeKanjis: false,
            includeWords: false,
            includeFacets: false,
            includeProcessingState: false,
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'query' => ['bail', 'nullable', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                $bytes = strlen(trim((string) $value));

                if ($bytes < SearchTerm::MIN_LENGTH || $bytes > SearchTerm::MAX_LENGTH) {
                    $fail('must be '.SearchTerm::MIN_LENGTH.' to '.SearchTerm::MAX_LENGTH.' bytes. One Japanese character is enough, a single letter or digit is too short.');
                }
            }],
            'levels' => ['nullable', 'array', 'max:'.count(StudyLevel::cases())],
            'levels.*' => ['string', Rule::in(StudyLevel::names())],
            'limit' => ['nullable', 'integer', 'between:1,'.self::MAX_LIMIT],
            'page' => ['nullable', 'integer', 'between:1,'.self::MAX_PAGE],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        $levels = implode(', ', StudyLevel::names());
        $limit = 'must be a whole number from 1 to '.self::MAX_LIMIT.'.';
        $page = 'must be a whole number from 1 to '.self::MAX_PAGE.'.';

        return [
            'query.string' => 'must be text.',
            'levels.array' => "must be a list of levels, each one of {$levels}.",
            'levels.max' => 'must have at most '.count(StudyLevel::cases()).' levels.',
            'levels.*.string' => "must be one of {$levels}.",
            'levels.*.in' => "must be one of {$levels}.",
            'limit.integer' => $limit,
            'limit.between' => $limit,
            'page.integer' => $page,
            'page.between' => $page,
        ];
    }
}
