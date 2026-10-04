<?php

declare(strict_types=1);

namespace Tests\Feature\Catalogues;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

/**
 * `catalogue_uuid` on the dictionary indexes (#347): the items of one catalogue, paginated and
 * in the same shape as the index, behind the catalogue's own visibility rule.
 */
class CatalogueItemIndexFilterTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaselineData();
        $this->owner = User::factory()->create();
    }

    public function test_the_kanji_index_lists_only_the_kanji_saved_in_a_public_catalogue(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);
        $water = $this->createKanji('水');
        $this->attach($catalogue, $water);
        // Saved twice, listed once.
        $this->attach($catalogue, $water);
        $this->attach($catalogue, $this->createKanji('火'));
        $this->createKanji('木');

        $this->getJson("/api/v1/kanjis?catalogue_uuid={$catalogue->uuid}")
            ->assertOk()
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonPath('items.0.character', '水')
            ->assertJsonPath('items.1.character', '火');
    }

    public function test_the_kanji_index_pages_through_a_catalogue(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);

        foreach (['一', '二', '三'] as $character) {
            $this->attach($catalogue, $this->createKanji($character));
        }

        $this->getJson("/api/v1/kanjis?catalogue_uuid={$catalogue->uuid}&per_page=2&page=2")
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.character', '三')
            ->assertJsonPath('pagination.total', 3);
    }

    public function test_a_known_kanji_catalogue_lists_through_the_kanji_index(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KNOWNKANJIS, ['publicity' => 0]);
        $this->attach($catalogue, $this->createKanji('水'));
        Passport::actingAs($this->owner);

        $this->getJson("/api/v1/kanjis?catalogue_uuid={$catalogue->uuid}")
            ->assertOk()
            ->assertJsonPath('items.0.character', '水')
            ->assertJsonPath('pagination.total', 1);
    }

    public function test_the_filter_combines_with_the_keyword_and_the_viewer_catalogue_state(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS);
        $this->attach($catalogue, $this->createKanji('水', 'water'));
        $this->attach($catalogue, $this->createKanji('火', 'fire'));
        Passport::actingAs($this->owner);

        $this->getJson("/api/v1/kanjis?catalogue_uuid={$catalogue->uuid}&keyword=water&include=viewer_catalogue_state")
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.character', '水')
            ->assertJsonPath('items.0.viewer_catalogue_state.is_saved', true);
    }

    public function test_the_word_index_lists_only_the_words_saved_in_a_catalogue(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::WORDS);
        $this->attach($catalogue, $this->createWord('勉強'));
        $this->createWord('学校');

        $this->getJson("/api/v1/words?catalogue_uuid={$catalogue->uuid}")
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.word', '勉強')
            ->assertJsonPath('pagination.total', 1);
    }

    public function test_a_private_catalogue_lists_for_its_owner_and_an_admin(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::WORDS, ['publicity' => 0]);
        $this->attach($catalogue, $this->createWord('勉強'));

        Passport::actingAs($this->owner);
        $this->getJson("/api/v1/words?catalogue_uuid={$catalogue->uuid}")
            ->assertOk()
            ->assertJsonPath('pagination.total', 1);

        $admin = User::factory()->create();
        $admin->assignRole(UserRole::ADMIN->value);
        Passport::actingAs($admin);
        $this->getJson("/api/v1/words?catalogue_uuid={$catalogue->uuid}")
            ->assertOk()
            ->assertJsonPath('pagination.total', 1);
    }

    public function test_a_private_catalogue_is_refused_to_a_guest(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::KANJIS, ['publicity' => 0]);
        $this->attach($catalogue, $this->createKanji('水'));

        $this->getJson("/api/v1/kanjis?catalogue_uuid={$catalogue->uuid}")
            ->assertForbidden()
            ->assertJsonMissingPath('items');
    }

    public function test_a_private_catalogue_is_refused_to_another_user(): void
    {
        $catalogue = $this->createCatalogue(SavedListType::WORDS, ['publicity' => 0]);
        $this->attach($catalogue, $this->createWord('勉強'));
        Passport::actingAs(User::factory()->create());

        $this->getJson("/api/v1/words?catalogue_uuid={$catalogue->uuid}")
            ->assertForbidden()
            ->assertJsonMissingPath('items');
    }

    public function test_an_unknown_catalogue_is_not_found(): void
    {
        $uuid = (string) Str::uuid();

        $this->getJson("/api/v1/kanjis?catalogue_uuid={$uuid}")->assertNotFound();
        $this->getJson("/api/v1/words?catalogue_uuid={$uuid}")->assertNotFound();
    }

    public function test_a_catalogue_of_another_kind_is_a_validation_error_on_the_filter(): void
    {
        $words = $this->createCatalogue(SavedListType::WORDS);
        $kanji = $this->createCatalogue(SavedListType::KANJIS);

        $this->getJson("/api/v1/kanjis?catalogue_uuid={$words->uuid}")
            ->assertUnprocessable()
            ->assertJsonPath('errors.catalogue_uuid.0', "This catalogue doesn't hold kanji.");

        $this->getJson("/api/v1/words?catalogue_uuid={$kanji->uuid}")
            ->assertUnprocessable()
            ->assertJsonPath('errors.catalogue_uuid.0', "This catalogue doesn't hold words.");
    }

    public function test_the_filter_must_be_a_uuid(): void
    {
        $this->getJson('/api/v1/kanjis?catalogue_uuid=12')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('catalogue_uuid');
    }

    private function createCatalogue(SavedListType $type, array $overrides = []): Catalogue
    {
        return Catalogue::create(array_merge([
            'user_id' => $this->owner->id,
            'uuid' => (string) Str::uuid(),
            'entity_type_uuid' => ObjectTemplateType::LIST->value,
            'title' => $type->label().' catalogue',
            'description' => '',
            'publicity' => 1,
            'type' => $type->value,
        ], $overrides));
    }

    private function attach(Catalogue $catalogue, int $itemId): void
    {
        DB::table('customlist_object')->insert([
            'list_id' => $catalogue->id,
            'listtype_id' => $catalogue->type->value,
            'real_object_id' => $itemId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createKanji(string $kanji, string $meaning = '-'): int
    {
        return DB::table('japanese_kanji_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'kanji' => $kanji,
            'onyomi' => '-',
            'kunyomi' => '-',
            'meaning' => $meaning,
            'nanori' => '-',
            'grade' => '1',
            'stroke_count' => '4',
            'jlpt' => '5',
            'frequency' => '1',
            'radicals' => '-',
            'radical_parts' => '-',
        ]);
    }

    private function createWord(string $word): int
    {
        return DB::table('japanese_word_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'entry_sequence' => '1001',
            'word' => $word,
            'furigana' => '-',
            'jlpt' => '-',
            'word_type' => 'noun',
            'word_k_ele' => $word,
            'furigana_r_ele' => '-',
            'sense' => '-',
        ]);
    }
}
