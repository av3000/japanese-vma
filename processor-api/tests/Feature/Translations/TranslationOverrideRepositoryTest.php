<?php

declare(strict_types=1);

namespace Tests\Feature\Translations;

use App\Application\Translations\Interfaces\Repositories\TranslationOverrideRepositoryInterface;
use App\Infrastructure\Persistence\Models\Translation;
use App\Infrastructure\Persistence\Models\TranslationLocale;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The stored overrides: one row per (locale, key), written by upsert so a second save never
 * hits the unique index (a PostgreSQL unique violation would abort the surrounding transaction).
 */
class TranslationOverrideRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TranslationOverrideRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // A new user is given the `common` role on creation.
        Role::firstOrCreate(['name' => 'common', 'guard_name' => 'api']);
        $this->repository = app(TranslationOverrideRepositoryInterface::class);
    }

    public function test_overrides_for_a_locale_are_a_key_to_value_map_of_that_locale_only(): void
    {
        $da = TranslationLocale::factory()->create();
        $sv = TranslationLocale::factory()->code('sv', 'Svenska')->create();
        Translation::factory()->for($da, 'locale')->create(['key' => 'common.save', 'value' => 'Gem']);
        Translation::factory()->for($da, 'locale')->create(['key' => 'common.cancel', 'value' => 'Annuller']);
        Translation::factory()->for($sv, 'locale')->create(['key' => 'common.save', 'value' => 'Spara']);

        $this->assertEquals(
            ['common.save' => 'Gem', 'common.cancel' => 'Annuller'],
            $this->repository->overridesFor((int) $da->id),
        );
        $this->assertSame([], $this->repository->overridesFor(999_999));
    }

    public function test_upsert_creates_then_updates_the_same_row_and_records_the_user(): void
    {
        $da = TranslationLocale::factory()->create();
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->repository->upsert((int) $da->id, 'common.save', 'Gem', (int) $first->id);
        $this->repository->upsert((int) $da->id, 'common.save', 'Gem nu', (int) $second->id);

        $this->assertSame(1, Translation::query()->count());
        $row = Translation::query()->firstOrFail();
        $this->assertSame('Gem nu', $row->value);
        $this->assertSame((int) $second->id, (int) $row->updated_by_user_id);
    }

    public function test_upsert_without_a_user_stores_null(): void
    {
        $da = TranslationLocale::factory()->create();

        $this->repository->upsert((int) $da->id, 'common.save', 'Gem', null);

        $this->assertNull(Translation::query()->firstOrFail()->updated_by_user_id);
    }

    public function test_delete_removes_only_that_locale_and_key(): void
    {
        $da = TranslationLocale::factory()->create();
        $sv = TranslationLocale::factory()->code('sv', 'Svenska')->create();
        $this->repository->upsert((int) $da->id, 'common.save', 'Gem', null);
        $this->repository->upsert((int) $da->id, 'common.cancel', 'Annuller', null);
        $this->repository->upsert((int) $sv->id, 'common.save', 'Spara', null);

        $this->repository->delete((int) $da->id, 'common.save');

        $this->assertSame(['common.cancel' => 'Annuller'], $this->repository->overridesFor((int) $da->id));
        $this->assertSame(['common.save' => 'Spara'], $this->repository->overridesFor((int) $sv->id));
    }

    public function test_deleting_a_missing_override_is_a_no_op(): void
    {
        $da = TranslationLocale::factory()->create();

        $this->repository->delete((int) $da->id, 'common.save');

        $this->assertSame(0, Translation::query()->count());
    }

    public function test_deleting_a_user_keeps_the_override_and_clears_the_author(): void
    {
        $da = TranslationLocale::factory()->create();
        $user = User::factory()->create();
        $this->repository->upsert((int) $da->id, 'common.save', 'Gem', (int) $user->id);

        $user->delete();

        $this->assertNull(Translation::query()->firstOrFail()->updated_by_user_id);
        $this->assertSame(1, Translation::query()->count());
    }

    public function test_deleting_a_locale_cascades_to_its_overrides(): void
    {
        $da = TranslationLocale::factory()->create();
        $this->repository->upsert((int) $da->id, 'common.save', 'Gem', null);

        $da->delete();

        $this->assertSame(0, Translation::query()->count());
    }
}
