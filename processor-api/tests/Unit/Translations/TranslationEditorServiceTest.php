<?php

declare(strict_types=1);

namespace Tests\Unit\Translations;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Auth\Interfaces\Providers\ActorPermissionCheckerInterface;
use App\Application\Translations\Interfaces\Repositories\TranslationLocaleRepositoryInterface;
use App\Application\Translations\Interfaces\Repositories\TranslationOverrideRepositoryInterface;
use App\Application\Translations\Interfaces\Sources\EnglishSourceCatalogueInterface;
use App\Application\Translations\Policies\TranslationEditingPolicy;
use App\Application\Translations\Services\TranslationEditorService;
use App\Application\Users\Support\PanelPermission;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use App\Domain\Translations\DTOs\TranslationRowDTO;
use App\Domain\Translations\Enums\TranslationState;
use App\Domain\Translations\Models\TranslationLocale;
use App\Domain\Translations\Services\TranslationResolver;
use App\Shared\Enums\HttpStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The edit-side rules of #661 (AC-3, AC-4, AC-5), run against in-memory ports: no database.
 */
class TranslationEditorServiceTest extends TestCase
{
    private InMemoryOverrides $overrides;

    private TranslationEditorService $service;

    /** The same service for an actor who does not hold `translations.update`. */
    private TranslationEditorService $deniedService;

    protected function setUp(): void
    {
        parent::setUp();

        $locales = new InMemoryLocales([
            new TranslationLocale(1, 'en', 'English', true),
            new TranslationLocale(2, 'da', 'Dansk', true),
            new TranslationLocale(3, 'sv', 'Svenska', false),
        ]);
        $english = new InMemoryCatalogue([
            'account.log_in' => 'Log In',
            'common.save' => 'Save',
            'common.cancel' => 'Cancel',
            'common.submit' => 'Submit',
        ]);
        $this->overrides = new InMemoryOverrides();

        $this->service = $this->serviceFor($locales, $english, allows: true);
        $this->deniedService = $this->serviceFor($locales, $english, allows: false);
    }

    private function serviceFor(InMemoryLocales $locales, InMemoryCatalogue $english, bool $allows): TranslationEditorService
    {
        $checker = new class($allows) implements ActorPermissionCheckerInterface
        {
            public function __construct(private readonly bool $allows)
            {
            }

            public function allows(AuthenticatedUser $actor, PanelPermission $permission): bool
            {
                return $this->allows && $permission === PanelPermission::TranslationsUpdate;
            }
        };

        return new TranslationEditorService(
            $locales,
            $this->overrides,
            $english,
            new TranslationResolver(),
            new TranslationEditingPolicy($checker),
        );
    }

    private function actor(int $id): AuthenticatedUser
    {
        return new AuthenticatedUser(UserId::from($id), EntityId::generate(), UserName::from('Translator'), isAdmin: false);
    }

    // ------------------------------------------------------------ AC-3: trim, upsert, delete

    public function test_saving_text_trims_it_and_stores_it_with_the_acting_user(): void
    {
        $result = $this->service->saveOverride('da', 'account.log_in', ' Log ind ', $this->actor(42));

        $this->assertTrue($result->isSuccess());
        $this->assertSame(TranslationState::Translated, $result->getData());
        $this->assertSame(['account.log_in' => 'Log ind'], $this->overrides->overridesFor(2));
        $this->assertSame(42, $this->overrides->lastUserId);
    }

    public function test_saving_again_replaces_the_value(): void
    {
        $this->service->saveOverride('da', 'account.log_in', 'Log ind', $this->actor(1));
        $this->service->saveOverride('da', 'account.log_in', 'Login', $this->actor(2));

        $this->assertSame(['account.log_in' => 'Login'], $this->overrides->overridesFor(2));
        $this->assertSame(2, $this->overrides->lastUserId);
    }

    public function test_without_translations_update_nothing_is_written_or_deleted(): void
    {
        $this->service->saveOverride('da', 'account.log_in', 'Log ind', $this->actor(1));
        $writesBefore = $this->overrides->writes;

        foreach (['Login', ''] as $raw) {
            $result = $this->deniedService->saveOverride('da', 'account.log_in', $raw, $this->actor(2));

            $this->assertTrue($result->isFailure());
            $this->assertSame('Translations.UpdateDenied', $result->getError()->code);
            $this->assertSame(HttpStatus::FORBIDDEN, $result->getError()->status);
        }

        $this->assertSame($writesBefore, $this->overrides->writes);
        $this->assertSame(['account.log_in' => 'Log ind'], $this->overrides->overridesFor(2));
        $this->assertSame(1, $this->overrides->lastUserId);
    }

    /** @return array<string, array{string}> */
    public static function blankValues(): array
    {
        return ['empty' => [''], 'spaces' => ['  '], 'tabs and newlines' => ["\t \n"]];
    }

    #[DataProvider('blankValues')]
    public function test_a_blank_value_deletes_the_existing_override(string $blank): void
    {
        $this->service->saveOverride('da', 'account.log_in', 'Log ind', $this->actor(1));

        $result = $this->service->saveOverride('da', 'account.log_in', $blank, $this->actor(1));

        $this->assertTrue($result->isSuccess());
        $this->assertSame(TranslationState::Missing, $result->getData());
        $this->assertSame([], $this->overrides->overridesFor(2));
    }

    public function test_a_blank_value_with_nothing_stored_succeeds_as_missing(): void
    {
        $result = $this->service->saveOverride('da', 'account.log_in', '', $this->actor(1));

        $this->assertTrue($result->isSuccess());
        $this->assertSame(TranslationState::Missing, $result->getData());
    }

    public function test_a_value_of_exactly_2000_characters_is_accepted(): void
    {
        $value = str_repeat('å', 2000);

        $result = $this->service->saveOverride('da', 'account.log_in', $value, $this->actor(1));

        $this->assertTrue($result->isSuccess());
        $this->assertSame($value, $this->overrides->overridesFor(2)['account.log_in']);
    }

    public function test_the_length_limit_applies_to_the_trimmed_value(): void
    {
        $value = '  '.str_repeat('a', 2000).'  ';

        $this->assertTrue($this->service->saveOverride('da', 'account.log_in', $value, $this->actor(1))->isSuccess());
    }

    // ------------------------------------------------------------ AC-4: typed failures, nothing written

    public function test_text_over_2000_characters_is_rejected_and_the_old_value_stays(): void
    {
        $this->service->saveOverride('da', 'account.log_in', 'Log ind', $this->actor(1));
        $writesBefore = $this->overrides->writes;

        $result = $this->service->saveOverride('da', 'account.log_in', str_repeat('å', 2001), $this->actor(1));

        $this->assertTrue($result->isFailure());
        $this->assertSame('Translations.ValueTooLong', $result->getError()->code);
        $this->assertSame(HttpStatus::UNPROCESSABLE_ENTITY, $result->getError()->status);
        $this->assertSame($writesBefore, $this->overrides->writes);
        $this->assertSame(['account.log_in' => 'Log ind'], $this->overrides->overridesFor(2));
    }

    /** @return array<string, array{string}> */
    public static function unstorableValues(): array
    {
        return [
            'a NUL byte inside the text' => ["Log\0ind"],
            'invalid UTF-8' => ["Log ind \xC3\x28"],
        ];
    }

    /** PostgreSQL rejects both in a text column; the save must fail typed instead of a 500. */
    #[DataProvider('unstorableValues')]
    public function test_text_postgres_cannot_store_is_rejected_and_the_old_value_stays(string $value): void
    {
        $this->service->saveOverride('da', 'account.log_in', 'Log ind', $this->actor(1));
        $writesBefore = $this->overrides->writes;

        $result = $this->service->saveOverride('da', 'account.log_in', $value, $this->actor(1));

        $this->assertTrue($result->isFailure());
        $this->assertSame('Translations.InvalidValue', $result->getError()->code);
        $this->assertSame(HttpStatus::UNPROCESSABLE_ENTITY, $result->getError()->status);
        $this->assertSame($writesBefore, $this->overrides->writes);
        $this->assertSame(['account.log_in' => 'Log ind'], $this->overrides->overridesFor(2));
    }

    public function test_saving_for_english_is_rejected(): void
    {
        $result = $this->service->saveOverride('en', 'account.log_in', 'Sign in', $this->actor(1));

        $this->assertTrue($result->isFailure());
        $this->assertSame('Translations.SourceLocaleNotEditable', $result->getError()->code);
        $this->assertSame(0, $this->overrides->writes);
    }

    public function test_saving_a_key_that_is_not_in_english_is_rejected(): void
    {
        $result = $this->service->saveOverride('da', 'removed.old_label', 'Gammel', $this->actor(1));

        $this->assertTrue($result->isFailure());
        $this->assertSame('Translations.UnknownKey', $result->getError()->code);
        $this->assertSame(HttpStatus::NOT_FOUND, $result->getError()->status);
        $this->assertSame(0, $this->overrides->writes);
    }

    public function test_a_blank_save_for_an_unknown_key_is_rejected_too_and_deletes_nothing(): void
    {
        $result = $this->service->saveOverride('da', 'removed.old_label', '', $this->actor(1));

        $this->assertTrue($result->isFailure());
        $this->assertSame('Translations.UnknownKey', $result->getError()->code);
        $this->assertSame(0, $this->overrides->writes);
    }

    public function test_saving_for_an_inactive_locale_is_rejected(): void
    {
        $result = $this->service->saveOverride('sv', 'account.log_in', 'Logga in', $this->actor(1));

        $this->assertTrue($result->isFailure());
        $this->assertSame('Translations.LocaleInactive', $result->getError()->code);
        $this->assertSame(HttpStatus::UNPROCESSABLE_ENTITY, $result->getError()->status);
        $this->assertSame(0, $this->overrides->writes);
    }

    public function test_saving_for_an_unknown_locale_is_rejected_as_not_found(): void
    {
        $result = $this->service->saveOverride('xx', 'account.log_in', 'x', $this->actor(1));

        $this->assertTrue($result->isFailure());
        $this->assertSame('Translations.LocaleNotFound', $result->getError()->code);
        $this->assertSame(0, $this->overrides->writes);
    }

    // ------------------------------------------------------------ AC-5: completion

    public function test_completion_counts_valid_translated_keys_and_leaves_orphans_out(): void
    {
        $this->overrides->seed(2, [
            'account.log_in' => 'Log ind',
            'common.save' => 'Gem',
            'removed.old_label' => 'Gammel tekst',
        ]);

        $result = $this->service->completion('da');

        $this->assertTrue($result->isSuccess());
        $this->assertSame(2, $result->getData()->translated);
        $this->assertSame(4, $result->getData()->total);
        $this->assertSame(50, $result->getData()->percent);
    }

    public function test_completion_of_a_locale_with_no_overrides_is_zero(): void
    {
        $result = $this->service->completion('da');

        $this->assertSame(0, $result->getData()->translated);
        $this->assertSame(4, $result->getData()->total);
        $this->assertSame(0, $result->getData()->percent);
    }

    public function test_completion_follows_the_same_locale_rules_as_saving(): void
    {
        $this->assertSame('Translations.SourceLocaleNotEditable', $this->service->completion('en')->getError()->code);
        $this->assertSame('Translations.LocaleInactive', $this->service->completion('sv')->getError()->code);
        $this->assertSame('Translations.LocaleNotFound', $this->service->completion('xx')->getError()->code);
    }

    // ------------------------------------------------------------ #663 rows: AC-1, AC-2, AC-3, AC-4

    public function test_rows_lists_every_english_key_once_with_its_value_and_state(): void
    {
        $this->overrides->seed(2, ['account.log_in' => ' Log ind ', 'common.save' => '   ', 'gone.key' => 'Orphan']);

        $result = $this->service->rows('da', null, false);

        $this->assertTrue($result->isSuccess());

        $rows = array_map(
            static fn (TranslationRowDTO $row): array => [$row->key, $row->source, $row->value, $row->state],
            $result->getData(),
        );
        $this->assertSame([
            ['account.log_in', 'Log In', 'Log ind', TranslationState::Translated],
            ['common.save', 'Save', null, TranslationState::Missing],
            ['common.cancel', 'Cancel', null, TranslationState::Missing],
            ['common.submit', 'Submit', null, TranslationState::Missing],
        ], $rows);
    }

    public function test_rows_with_missing_only_leaves_out_translated_keys(): void
    {
        $this->overrides->seed(2, ['account.log_in' => 'Log ind', 'common.save' => 'Gem']);

        $keys = array_map(
            static fn (TranslationRowDTO $row): string => $row->key,
            $this->service->rows('da', null, true)->getData(),
        );

        $this->assertSame(['common.cancel', 'common.submit'], $keys);
    }

    public function test_rows_search_matches_the_key_the_english_text_or_the_translation_in_any_case(): void
    {
        $this->overrides->seed(2, ['common.save' => 'Gem ændringer']);

        $keysFor = fn (string $search): array => array_map(
            static fn (TranslationRowDTO $row): string => $row->key,
            $this->service->rows('da', $search, false)->getData(),
        );

        $this->assertSame(['account.log_in'], $keysFor('ACCOUNT.LOG'), 'matches on the key');
        $this->assertSame(['common.cancel'], $keysFor('cANCEL'), 'matches on the English text');
        $this->assertSame(['common.save'], $keysFor('ÆNDRINGER'), 'matches on the translated text, multi-byte letters included');
        $this->assertSame([], $keysFor('no such text'));
    }

    public function test_rows_search_and_missing_only_combine_and_a_blank_search_changes_nothing(): void
    {
        $this->overrides->seed(2, ['common.save' => 'Gem']);

        $this->assertCount(4, $this->service->rows('da', '  ', false)->getData());
        $this->assertSame([], $this->service->rows('da', 'gem', true)->getData(), 'a translated key is not missing');
    }

    public function test_rows_is_refused_for_english_an_inactive_locale_and_an_unknown_one(): void
    {
        $this->assertSame('Translations.SourceLocaleNotEditable', $this->service->rows('en', null, false)->getError()->code);
        $this->assertSame('Translations.LocaleInactive', $this->service->rows('sv', null, false)->getError()->code);
        $this->assertSame('Translations.LocaleNotFound', $this->service->rows('xx', null, false)->getError()->code);
    }

    public function test_completion_with_one_valid_override_and_one_orphan_is_one_of_four_and_25_percent(): void
    {
        $this->overrides->seed(2, ['account.log_in' => 'Log ind', 'gone.key' => 'Orphan']);

        $completion = $this->service->completion('da')->getData();

        $this->assertSame([1, 4, 25], [$completion->translated, $completion->total, $completion->percent]);
    }

    public function test_editable_locales_are_the_active_non_english_ones(): void
    {
        $codes = array_map(
            static fn (TranslationLocale $locale): string => $locale->getCode(),
            $this->service->editableLocales(),
        );

        $this->assertSame(['da'], $codes);
    }
}

/** @internal In-memory locale port for the editor service tests. */
final class InMemoryLocales implements TranslationLocaleRepositoryInterface
{
    /** @param list<TranslationLocale> $locales */
    public function __construct(private readonly array $locales)
    {
    }

    public function findActive(): array
    {
        return array_values(array_filter($this->locales, fn (TranslationLocale $l): bool => $l->isActive()));
    }

    public function findActiveByCode(string $code): ?TranslationLocale
    {
        $locale = $this->findByCode($code);

        return $locale?->isActive() ? $locale : null;
    }

    public function findByCode(string $code): ?TranslationLocale
    {
        foreach ($this->locales as $locale) {
            if ($locale->getCode() === $code) {
                return $locale;
            }
        }

        return null;
    }

    public function create(string $code, string $name, bool $isActive): ?TranslationLocale
    {
        throw new \LogicException('Not used by the editor service.');
    }

    public function update(int $id, string $name, bool $isActive): TranslationLocale
    {
        throw new \LogicException('Not used by the editor service.');
    }
}

/** @internal In-memory override port that counts writes, so "nothing was written" can be asserted. */
final class InMemoryOverrides implements TranslationOverrideRepositoryInterface
{
    /** @var array<int, array<string, string>> */
    private array $rows = [];

    public int $writes = 0;

    public ?int $lastUserId = null;

    /** @param array<string, string> $overrides */
    public function seed(int $localeId, array $overrides): void
    {
        $this->rows[$localeId] = $overrides;
    }

    public function overridesFor(int $localeId): array
    {
        return $this->rows[$localeId] ?? [];
    }

    public function upsert(int $localeId, string $key, string $value, ?int $userId): void
    {
        $this->writes++;
        $this->lastUserId = $userId;
        $this->rows[$localeId][$key] = $value;
    }

    public function delete(int $localeId, string $key): void
    {
        $this->writes++;
        unset($this->rows[$localeId][$key]);
    }
}

/** @internal Fixed English catalogue for the editor service tests. */
final class InMemoryCatalogue implements EnglishSourceCatalogueInterface
{
    /** @param array<string, string> $messages */
    public function __construct(private readonly array $messages)
    {
    }

    public function messages(): array
    {
        return $this->messages;
    }

    public function find(string $key): ?string
    {
        return $this->messages[$key] ?? null;
    }
}
