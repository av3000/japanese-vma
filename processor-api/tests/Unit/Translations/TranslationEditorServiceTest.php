<?php

declare(strict_types=1);

namespace Tests\Unit\Translations;

use App\Application\Translations\Interfaces\Repositories\TranslationLocaleRepositoryInterface;
use App\Application\Translations\Interfaces\Repositories\TranslationOverrideRepositoryInterface;
use App\Application\Translations\Interfaces\Sources\EnglishSourceCatalogueInterface;
use App\Application\Translations\Services\TranslationEditorService;
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

        $this->service = new TranslationEditorService(
            $locales,
            $this->overrides,
            $english,
            new TranslationResolver(),
        );
    }

    // ------------------------------------------------------------ AC-3: trim, upsert, delete

    public function test_saving_text_trims_it_and_stores_it_with_the_acting_user(): void
    {
        $result = $this->service->saveOverride('da', 'account.log_in', ' Log ind ', 42);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(TranslationState::Translated, $result->getData());
        $this->assertSame(['account.log_in' => 'Log ind'], $this->overrides->overridesFor(2));
        $this->assertSame(42, $this->overrides->lastUserId);
    }

    public function test_saving_again_replaces_the_value(): void
    {
        $this->service->saveOverride('da', 'account.log_in', 'Log ind', 1);
        $this->service->saveOverride('da', 'account.log_in', 'Login', 2);

        $this->assertSame(['account.log_in' => 'Login'], $this->overrides->overridesFor(2));
        $this->assertSame(2, $this->overrides->lastUserId);
    }

    public function test_a_save_without_a_known_user_stores_null(): void
    {
        $this->service->saveOverride('da', 'account.log_in', 'Log ind', null);

        $this->assertNull($this->overrides->lastUserId);
        $this->assertSame(['account.log_in' => 'Log ind'], $this->overrides->overridesFor(2));
    }

    /** @return array<string, array{string}> */
    public static function blankValues(): array
    {
        return ['empty' => [''], 'spaces' => ['  '], 'tabs and newlines' => ["\t \n"]];
    }

    #[DataProvider('blankValues')]
    public function test_a_blank_value_deletes_the_existing_override(string $blank): void
    {
        $this->service->saveOverride('da', 'account.log_in', 'Log ind', 1);

        $result = $this->service->saveOverride('da', 'account.log_in', $blank, 1);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(TranslationState::Missing, $result->getData());
        $this->assertSame([], $this->overrides->overridesFor(2));
    }

    public function test_a_blank_value_with_nothing_stored_succeeds_as_missing(): void
    {
        $result = $this->service->saveOverride('da', 'account.log_in', '', 1);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(TranslationState::Missing, $result->getData());
    }

    public function test_a_value_of_exactly_2000_characters_is_accepted(): void
    {
        $value = str_repeat('å', 2000);

        $result = $this->service->saveOverride('da', 'account.log_in', $value, 1);

        $this->assertTrue($result->isSuccess());
        $this->assertSame($value, $this->overrides->overridesFor(2)['account.log_in']);
    }

    public function test_the_length_limit_applies_to_the_trimmed_value(): void
    {
        $value = '  '.str_repeat('a', 2000).'  ';

        $this->assertTrue($this->service->saveOverride('da', 'account.log_in', $value, 1)->isSuccess());
    }

    // ------------------------------------------------------------ AC-4: typed failures, nothing written

    public function test_text_over_2000_characters_is_rejected_and_the_old_value_stays(): void
    {
        $this->service->saveOverride('da', 'account.log_in', 'Log ind', 1);
        $writesBefore = $this->overrides->writes;

        $result = $this->service->saveOverride('da', 'account.log_in', str_repeat('å', 2001), 1);

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
        $this->service->saveOverride('da', 'account.log_in', 'Log ind', 1);
        $writesBefore = $this->overrides->writes;

        $result = $this->service->saveOverride('da', 'account.log_in', $value, 1);

        $this->assertTrue($result->isFailure());
        $this->assertSame('Translations.InvalidValue', $result->getError()->code);
        $this->assertSame(HttpStatus::UNPROCESSABLE_ENTITY, $result->getError()->status);
        $this->assertSame($writesBefore, $this->overrides->writes);
        $this->assertSame(['account.log_in' => 'Log ind'], $this->overrides->overridesFor(2));
    }

    public function test_saving_for_english_is_rejected(): void
    {
        $result = $this->service->saveOverride('en', 'account.log_in', 'Sign in', 1);

        $this->assertTrue($result->isFailure());
        $this->assertSame('Translations.SourceLocaleNotEditable', $result->getError()->code);
        $this->assertSame(0, $this->overrides->writes);
    }

    public function test_saving_a_key_that_is_not_in_english_is_rejected(): void
    {
        $result = $this->service->saveOverride('da', 'removed.old_label', 'Gammel', 1);

        $this->assertTrue($result->isFailure());
        $this->assertSame('Translations.UnknownKey', $result->getError()->code);
        $this->assertSame(HttpStatus::NOT_FOUND, $result->getError()->status);
        $this->assertSame(0, $this->overrides->writes);
    }

    public function test_a_blank_save_for_an_unknown_key_is_rejected_too_and_deletes_nothing(): void
    {
        $result = $this->service->saveOverride('da', 'removed.old_label', '', 1);

        $this->assertTrue($result->isFailure());
        $this->assertSame('Translations.UnknownKey', $result->getError()->code);
        $this->assertSame(0, $this->overrides->writes);
    }

    public function test_saving_for_an_inactive_locale_is_rejected(): void
    {
        $result = $this->service->saveOverride('sv', 'account.log_in', 'Logga in', 1);

        $this->assertTrue($result->isFailure());
        $this->assertSame('Translations.LocaleInactive', $result->getError()->code);
        $this->assertSame(HttpStatus::UNPROCESSABLE_ENTITY, $result->getError()->status);
        $this->assertSame(0, $this->overrides->writes);
    }

    public function test_saving_for_an_unknown_locale_is_rejected_as_not_found(): void
    {
        $result = $this->service->saveOverride('xx', 'account.log_in', 'x', 1);

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
