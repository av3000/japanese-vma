<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Application\Catalogues\Services\CatalogueServiceInterface;
use App\Domain\Catalogues\DTOs\CatalogueListDTO;
use App\Domain\Catalogues\DTOs\CatalogueListResultDTO;
use App\Domain\Shared\ValueObjects\SearchTerm;
use App\Mcp\Presenters\DeckPresenter;
use App\Mcp\Support\DeckKind;
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

#[Name('find_decks')]
#[Title('Find study lists to drill')]
#[Description(<<<'TEXT'
Find public study lists (decks) of kanji, words or radicals to drill from, newest first.
kind is required and one kind per call: kanji, words or radicals. Sentence, article, lyric and artist lists are not offered.
query is optional and matches a word in the list's title or the description. Without it the newest lists of that kind are listed.
Each result has uuid, title, description (null when the list has none), kind and items_count, the number of entries in the list. The list's entries are not returned here.
TEXT)]
#[IsReadOnly]
class FindDecksTool extends Tool
{
    private const DEFAULT_LIMIT = 5;

    private const MAX_LIMIT = 20;

    /** The same cap as find_articles, so both tools page alike. */
    private const MAX_PAGE = 500;

    public function __construct(private readonly DeckPresenter $presenter)
    {
    }

    public function handle(Request $request, CatalogueServiceInterface $catalogues): Response|ResponseFactory
    {
        try {
            $input = $request->validate($this->rules(), $this->messages());
        } catch (ValidationException $exception) {
            return ToolFailure::fromValidation($exception);
        }

        $list = $catalogues->getCatalogueList($this->listRequest($input));

        return $this->respond($list);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'kind' => $schema->string()
                ->description('Which kind of list: '.implode(', ', DeckKind::names()).'. One kind per call.')
                ->enum(DeckKind::names())
                ->required(),
            'query' => $schema->string()
                ->description('A word to look for in the list title or description. At most '.SearchTerm::MAX_LENGTH.' bytes, about '.intdiv(SearchTerm::MAX_LENGTH, 3).' Japanese characters. One Japanese character is enough; a single letter or digit is too short.')
                ->max(SearchTerm::MAX_LENGTH),
            'limit' => $schema->integer()
                ->description('How many lists per page, 1 to '.self::MAX_LIMIT.'.')
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
                ->description('The lists on this page, newest first. Empty when nothing matches.')
                ->items($schema->object([
                    'uuid' => $schema->string()->description('Identifies the list in later calls.')->required(),
                    'title' => $schema->string()->required(),
                    'description' => $schema->string()->nullable()->required(),
                    'kind' => $schema->string()->enum(DeckKind::names())->required(),
                    'items_count' => $schema->integer()->description('How many entries the list holds.')->required(),
                ]))
                ->required(),
            'total' => $schema->integer()->description('How many lists of this kind match in all.')->required(),
            'has_more' => $schema->boolean()->description('Whether a later page has more lists.')->required(),
        ];
    }

    private function respond(CatalogueListResultDTO $list): Response|ResponseFactory
    {
        return Response::structured([
            'results' => array_map($this->presenter->present(...), $list->items),
            'total' => $list->pagination['total'],
            'has_more' => $list->pagination['has_more'],
        ]);
    }

    /**
     * A guest asking for lists of one type: no owner, so the service lists public custom lists only,
     * and none of the stats, tags or level data the website's list page loads.
     *
     * @param array<string, mixed> $input
     */
    private function listRequest(array $input): CatalogueListDTO
    {
        // Validation skips a blank query, and the value object refuses an empty one: blank means no search.
        $query = trim((string) ($input['query'] ?? ''));

        return new CatalogueListDTO(
            search: $query === '' ? null : $query,
            owner_uid: null,
            type: DeckKind::from($input['kind'])->toListType()->value,
            sort_by: null,
            sort_dir: null,
            per_page: (int) ($input['limit'] ?? self::DEFAULT_LIMIT),
            page: (int) ($input['page'] ?? 1),
            public_only: true,
            custom_only: true,
            include_stats_counts: false,
            include_hashtags: false,
            include_jlpt_levels: false,
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'kind' => ['bail', 'required', 'string', Rule::in(DeckKind::names())],
            'query' => ['bail', 'nullable', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                $bytes = strlen(trim((string) $value));

                if ($bytes < SearchTerm::MIN_LENGTH || $bytes > SearchTerm::MAX_LENGTH) {
                    $fail('must be '.SearchTerm::MIN_LENGTH.' to '.SearchTerm::MAX_LENGTH.' bytes. One Japanese character is enough, a single letter or digit is too short.');
                }
            }],
            'limit' => ['nullable', 'integer', 'between:1,'.self::MAX_LIMIT],
            'page' => ['nullable', 'integer', 'between:1,'.self::MAX_PAGE],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        $kinds = implode(', ', DeckKind::names());
        $limit = 'must be a whole number from 1 to '.self::MAX_LIMIT.'.';
        $page = 'must be a whole number from 1 to '.self::MAX_PAGE.'.';

        return [
            'kind.required' => "is required: one of {$kinds}.",
            'kind.string' => "must be one of {$kinds}.",
            'kind.in' => "must be one of {$kinds}.",
            'query.string' => 'must be text.',
            'limit.integer' => $limit,
            'limit.between' => $limit,
            'page.integer' => $page,
            'page.between' => $page,
        ];
    }
}
