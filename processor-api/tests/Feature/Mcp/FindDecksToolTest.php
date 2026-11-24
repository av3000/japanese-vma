<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Application\Catalogues\Services\CatalogueServiceInterface;
use App\Domain\Shared\Enums\SavedListType;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\User;
use App\Mcp\Servers\StudyServer;
use App\Mcp\Tools\FindDecksTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

final class FindDecksToolTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
        $this->owner = User::factory()->create();
    }

    // ------------------------------------------------------------------ AC-1

    public function test_the_tool_is_read_only_and_requires_a_kind_of_the_three(): void
    {
        $tool = app(FindDecksTool::class)->toArray();

        $this->assertSame('find_decks', $tool['name']);
        $this->assertTrue($tool['annotations']['readOnlyHint']);

        $input = $tool['inputSchema'];
        $this->assertSame(['kind', 'query', 'limit', 'page'], array_keys($input['properties']));
        $this->assertSame(['kind'], $input['required']);
        $this->assertSame(['kanji', 'words', 'radicals'], $input['properties']['kind']['enum']);
        $this->assertSame(1, $input['properties']['limit']['minimum']);
        $this->assertSame(20, $input['properties']['limit']['maximum']);
        $this->assertSame(5, $input['properties']['limit']['default']);
        $this->assertSame(1, $input['properties']['page']['minimum']);
        $this->assertSame(1, $input['properties']['page']['default']);

        $output = $tool['outputSchema'];
        $this->assertSame(['results', 'total', 'has_more'], array_keys($output['properties']));
        $this->assertSame(
            ['uuid', 'title', 'description', 'kind', 'items_count'],
            array_keys($output['properties']['results']['items']['properties']),
        );
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidKinds(): array
    {
        return [
            'no kind' => [[]],
            'a null kind' => [['kind' => null]],
            'an empty kind' => [['kind' => '']],
            'a kind that is not offered' => [['kind' => 'sentences']],
            'the singular of words' => [['kind' => 'word']],
            'a kind in capitals' => [['kind' => 'KANJI']],
            'a kind that is not text' => [['kind' => ['kanji']]],
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     */
    #[DataProvider('invalidKinds')]
    public function test_a_missing_or_other_kind_is_an_error_listing_the_three(array $arguments): void
    {
        $this->mock(CatalogueServiceInterface::class)->shouldNotReceive('getCatalogueList');

        StudyServer::tool(FindDecksTool::class, $arguments)->assertHasErrors(['kind', 'kanji, words, radicals']);
    }

    // ------------------------------------------------------------------ AC-2

    public function test_a_list_is_returned_in_the_documented_shape_without_internal_keys(): void
    {
        $deck = $this->createList(SavedListType::KANJIS, ['title' => 'N5 kanji', 'description' => 'The first hundred.']);
        $this->addItems($deck, SavedListType::KANJIS, [101, 102, 103]);

        StudyServer::tool(FindDecksTool::class, ['kind' => 'kanji'])
            ->assertOk()
            ->assertStructuredContent([
                'results' => [[
                    'uuid' => $deck->uuid,
                    'title' => 'N5 kanji',
                    'description' => 'The first hundred.',
                    'kind' => 'kanji',
                    'items_count' => 3,
                ]],
                'total' => 1,
                'has_more' => false,
            ])
            ->assertStructuredContentMissingPaths([
                'results.0.id',
                'results.0.owner',
                'results.0.owner_name',
                'results.0.user_id',
                'results.0.publicity',
                'results.0.type',
                'results.0.stats',
                'results.0.hashtags',
            ]);
    }

    public function test_a_list_without_a_description_or_items_has_null_and_zero(): void
    {
        // The column is not null: a list saved without a description holds an empty string.
        $this->createList(SavedListType::WORDS, ['title' => 'Empty list', 'description' => '']);

        StudyServer::tool(FindDecksTool::class, ['kind' => 'words'])
            ->assertOk()
            ->assertStructuredContentPaths([
                'results.0.description' => null,
                'results.0.items_count' => 0,
                'results.0.kind' => 'words',
            ]);
    }

    /**
     * @return array<string, array{string, SavedListType}>
     */
    public static function kinds(): array
    {
        return [
            'kanji' => ['kanji', SavedListType::KANJIS],
            'words' => ['words', SavedListType::WORDS],
            'radicals' => ['radicals', SavedListType::RADICALS],
        ];
    }

    #[DataProvider('kinds')]
    public function test_only_lists_of_the_requested_kind_are_returned_and_counted(string $kind, SavedListType $type): void
    {
        $wanted = $this->createList($type, ['title' => 'Wanted list']);

        // Every other kind of list a guest could otherwise find in the website's list index.
        foreach (SavedListType::cases() as $other) {
            if ($other !== $type) {
                $this->createList($other, ['title' => "Other {$other->name}"]);
            }
        }

        StudyServer::tool(FindDecksTool::class, ['kind' => $kind])
            ->assertOk()
            ->assertStructuredContentCount(1, 'results')
            ->assertStructuredContentPaths([
                'total' => 1,
                'results.0.uuid' => $wanted->uuid,
                'results.0.kind' => $kind,
            ]);
    }

    public function test_lists_are_newest_first(): void
    {
        $this->createList(SavedListType::KANJIS, ['title' => 'Older', 'created_at' => Carbon::parse('2026-01-01')]);
        $this->createList(SavedListType::KANJIS, ['title' => 'Newer', 'created_at' => Carbon::parse('2026-02-01')]);

        StudyServer::tool(FindDecksTool::class, ['kind' => 'kanji'])
            ->assertOk()
            ->assertStructuredContentPaths(['results.0.title' => 'Newer', 'results.1.title' => 'Older']);
    }

    // ------------------------------------------------------------------ AC-3

    #[DataProvider('kinds')]
    public function test_a_private_list_is_neither_in_the_results_nor_in_the_total(string $kind, SavedListType $type): void
    {
        $public = $this->createList($type, ['title' => 'Public list']);
        $private = $this->createList($type, ['title' => 'Private list'], private: true);
        $this->addItems($private, $type, [201, 202]);

        $response = StudyServer::tool(FindDecksTool::class, ['kind' => $kind, 'limit' => 20]);

        $response->assertOk()
            ->assertStructuredContentCount(1, 'results')
            ->assertStructuredContentPaths(['total' => 1, 'has_more' => false, 'results.0.uuid' => $public->uuid]);

        $this->assertStringNotContainsString($private->uuid, (string) json_encode($response));
    }

    public function test_a_search_cannot_reach_a_private_list(): void
    {
        $this->createList(SavedListType::KANJIS, ['title' => 'Secret drill'], private: true);

        StudyServer::tool(FindDecksTool::class, ['kind' => 'kanji', 'query' => 'Secret'])
            ->assertOk()
            ->assertStructuredContent(['results' => [], 'total' => 0, 'has_more' => false]);
    }

    // ------------------------------------------------------------------ AC-4

    public function test_the_query_matches_the_title_or_the_description(): void
    {
        $this->createList(SavedListType::KANJIS, ['title' => 'Beginner N5 drill', 'description' => 'Nothing here']);
        $this->createList(SavedListType::KANJIS, ['title' => 'Plain title', 'description' => 'Made for the N5 exam']);
        $this->createList(SavedListType::KANJIS, ['title' => 'Advanced N1', 'description' => 'Hard']);
        $this->createList(SavedListType::WORDS, ['title' => 'N5 words']);

        StudyServer::tool(FindDecksTool::class, ['kind' => 'kanji', 'query' => 'N5'])
            ->assertOk()
            ->assertStructuredContentCount(2, 'results')
            ->assertStructuredContentPaths(['total' => 2]);
    }

    public function test_one_japanese_character_is_a_valid_query(): void
    {
        $this->createList(SavedListType::WORDS, ['title' => '食べ物の言葉']);

        StudyServer::tool(FindDecksTool::class, ['kind' => 'words', 'query' => '食'])
            ->assertOk()
            ->assertStructuredContentPaths(['total' => 1]);
    }

    public function test_a_blank_query_is_no_query(): void
    {
        $this->createList(SavedListType::KANJIS, ['title' => 'First list']);
        $this->createList(SavedListType::KANJIS, ['title' => 'Second list']);

        StudyServer::tool(FindDecksTool::class, ['kind' => 'kanji', 'query' => '   '])
            ->assertOk()
            ->assertStructuredContentPaths(['total' => 2]);
    }

    public function test_a_search_with_no_matches_is_an_ok_empty_list(): void
    {
        $this->createList(SavedListType::KANJIS, ['title' => 'Some list']);

        StudyServer::tool(FindDecksTool::class, ['kind' => 'kanji', 'query' => 'zzzz'])
            ->assertOk()
            ->assertStructuredContent(['results' => [], 'total' => 0, 'has_more' => false]);
    }

    public function test_page_two_returns_the_next_lists_and_has_more_is_false_on_the_last_page(): void
    {
        foreach (range(1, 7) as $number) {
            $this->createList(SavedListType::KANJIS, [
                'title' => "List {$number}",
                'created_at' => Carbon::parse('2026-01-01')->addDays($number),
            ]);
        }

        $expected = [
            1 => [['List 7', 'List 6', 'List 5'], true],
            2 => [['List 4', 'List 3', 'List 2'], true],
            3 => [['List 1'], false],
        ];

        foreach ($expected as $page => [$titles, $hasMore]) {
            $paths = ['total' => 7, 'has_more' => $hasMore];

            foreach ($titles as $position => $title) {
                $paths["results.{$position}.title"] = $title;
            }

            StudyServer::tool(FindDecksTool::class, ['kind' => 'kanji', 'limit' => 3, 'page' => $page])
                ->assertOk()
                ->assertStructuredContentCount(count($titles), 'results')
                ->assertStructuredContentPaths($paths);
        }
    }

    public function test_the_default_page_size_is_five(): void
    {
        foreach (range(1, 6) as $number) {
            $this->createList(SavedListType::KANJIS, ['title' => "List {$number}"]);
        }

        StudyServer::tool(FindDecksTool::class, ['kind' => 'kanji'])
            ->assertOk()
            ->assertStructuredContentCount(5, 'results')
            ->assertStructuredContentPaths(['total' => 6, 'has_more' => true]);
    }

    public function test_a_page_past_the_end_is_an_ok_empty_list(): void
    {
        $this->createList(SavedListType::KANJIS, ['title' => 'Only list']);

        StudyServer::tool(FindDecksTool::class, ['kind' => 'kanji', 'page' => 4])
            ->assertOk()
            ->assertStructuredContent(['results' => [], 'total' => 1, 'has_more' => false]);
    }

    // ------------------------------------------------------------------ input

    /**
     * @return array<string, array{array<string, mixed>, array<int, string>}>
     */
    public static function invalidInput(): array
    {
        return [
            'a one letter query' => [['query' => 'a'], ['query', '2 to 255 bytes']],
            'a query over 255 bytes' => [['query' => str_repeat('あ', 86)], ['query', '255']],
            'a query that is not text' => [['query' => ['x']], ['query']],
            'a limit of 0' => [['limit' => 0], ['limit', '1 to 20']],
            'a limit of 21' => [['limit' => 21], ['limit', '1 to 20']],
            'a limit that is not a number' => [['limit' => 'many'], ['limit', '1 to 20']],
            'a page of 0' => [['page' => 0], ['page', '1 to 500']],
            'a page past the cap' => [['page' => 501], ['page', '1 to 500']],
            'a huge page' => [['page' => PHP_INT_MAX], ['page', '1 to 500']],
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array<int, string> $expectedInMessage
     */
    #[DataProvider('invalidInput')]
    public function test_invalid_input_is_an_error_naming_the_field_and_the_service_is_not_called(
        array $arguments,
        array $expectedInMessage,
    ): void {
        $this->mock(CatalogueServiceInterface::class)->shouldNotReceive('getCatalogueList');

        StudyServer::tool(FindDecksTool::class, ['kind' => 'kanji', ...$arguments])->assertHasErrors($expectedInMessage);
    }

    public function test_the_description_says_one_kind_per_call_and_that_the_search_reads_the_description_too(): void
    {
        $description = app(FindDecksTool::class)->toArray()['description'];

        $this->assertStringContainsString('one kind per call', $description);
        $this->assertStringContainsString('title or the description', $description);
        $this->assertStringContainsString('uuid', $description);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createList(SavedListType $type, array $attributes = [], bool $private = false): Catalogue
    {
        $factory = Catalogue::factory()->byUser($this->owner)->ofType($type);

        if ($private) {
            $factory = $factory->asPrivate();
        }

        return $factory->create($attributes);
    }

    /**
     * @param array<int, int> $itemIds
     */
    private function addItems(Catalogue $list, SavedListType $type, array $itemIds): void
    {
        foreach ($itemIds as $itemId) {
            DB::table('customlist_object')->insert([
                'list_id' => $list->id,
                'listtype_id' => $type->value,
                'real_object_id' => $itemId,
            ]);
        }
    }
}
