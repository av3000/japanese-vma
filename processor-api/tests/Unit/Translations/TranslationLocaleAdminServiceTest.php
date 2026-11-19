<?php

declare(strict_types=1);

namespace Tests\Unit\Translations;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Auth\Interfaces\Providers\ActorPermissionCheckerInterface;
use App\Application\Translations\Interfaces\Repositories\TranslationLocaleRepositoryInterface;
use App\Application\Translations\Policies\TranslationLocaleManagementPolicy;
use App\Application\Translations\Services\TranslationLocaleAdminService;
use App\Application\Users\Support\PanelPermission;
use App\Domain\Translations\Models\TranslationLocale;
use App\Shared\Enums\HttpStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The administrator's rules for the language list (#662: AC-3, AC-4, AC-5), run against an
 * in-memory port: no database, no panel.
 */
class TranslationLocaleAdminServiceTest extends TestCase
{
    private WritableLocales $locales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locales = new WritableLocales([
            new TranslationLocale(1, 'en', 'English', true),
            new TranslationLocale(2, 'da', 'Dansk', true),
        ]);
    }

    public function test_a_manager_adds_a_language_that_is_listed_when_active(): void
    {
        $result = $this->service()->create('sv', 'Svenska', true, $this->actor());

        self::assertTrue($result->isSuccess());
        self::assertSame('sv', $result->getData()->getCode());
        self::assertSame(['en', 'da', 'sv'], array_map(fn (TranslationLocale $l): string => $l->getCode(), $this->locales->findActive()));
    }

    public function test_an_inactive_language_is_not_listed(): void
    {
        $this->service()->create('sv', 'Svenska', false, $this->actor());

        self::assertSame(['en', 'da'], array_map(fn (TranslationLocale $l): string => $l->getCode(), $this->locales->findActive()));
    }

    /** @return iterable<string, array{string}> */
    public static function badCodes(): iterable
    {
        yield 'uppercase' => ['DA'];
        yield 'one letter' => ['d'];
        yield 'three letters' => ['dan'];
        yield 'region code' => ['pt-BR'];
        yield 'digits' => ['d1'];
        yield 'empty' => [''];
        yield 'trailing newline' => ["fi\n"];
        yield 'non-ASCII letters' => ['æø'];
    }

    #[DataProvider('badCodes')]
    public function test_a_code_that_is_not_two_lowercase_letters_is_refused(string $code): void
    {
        $result = $this->service()->create($code, 'Whatever', true, $this->actor());

        self::assertTrue($result->isFailure());
        self::assertSame('Translations.InvalidLocaleCode', $result->getError()->code);
        self::assertSame(HttpStatus::UNPROCESSABLE_ENTITY, $result->getError()->status);
        self::assertSame(0, $this->locales->writes);
    }

    public function test_a_code_that_exists_is_refused_including_english(): void
    {
        foreach (['da', 'en'] as $code) {
            $result = $this->service()->create($code, 'Duplicate', true, $this->actor());

            self::assertSame('Translations.LocaleCodeTaken', $result->getError()->code, $code);
        }

        self::assertSame(0, $this->locales->writes);
    }

    public function test_a_blank_or_oversized_name_is_refused(): void
    {
        foreach (['', '   ', str_repeat('å', TranslationLocaleAdminService::MAX_NAME_LENGTH + 1)] as $name) {
            $result = $this->service()->create('sv', $name, true, $this->actor());

            self::assertSame('Translations.InvalidLocaleName', $result->getError()->code);
        }

        self::assertSame(0, $this->locales->writes);
    }

    public function test_english_cannot_be_deactivated(): void
    {
        $result = $this->service()->update('en', 'English', false, $this->actor());

        self::assertTrue($result->isFailure());
        self::assertSame('Translations.SourceLocaleLocked', $result->getError()->code);
        self::assertSame(0, $this->locales->writes);
        self::assertTrue($this->locales->findByCode('en')?->isActive());
    }

    public function test_english_keeps_its_code_when_its_name_is_edited(): void
    {
        $result = $this->service()->update('en', 'English (source)', true, $this->actor());

        self::assertTrue($result->isSuccess());
        self::assertSame('en', $result->getData()->getCode());
        self::assertSame('English (source)', $result->getData()->getName());
    }

    public function test_another_language_can_be_deactivated_and_reactivated(): void
    {
        $off = $this->service()->update('da', 'Dansk', false, $this->actor());
        $on = $this->service()->update('da', 'Dansk', true, $this->actor());

        self::assertFalse($off->getData()->isActive());
        self::assertTrue($on->getData()->isActive());
    }

    public function test_an_unknown_code_is_not_found(): void
    {
        $result = $this->service()->update('xx', 'Nope', true, $this->actor());

        self::assertSame('Translations.LocaleNotFound', $result->getError()->code);
    }

    public function test_without_locales_manage_nothing_is_written(): void
    {
        $service = $this->service(allows: false);

        $create = $service->create('sv', 'Svenska', true, $this->actor());
        $update = $service->update('da', 'Dansk', false, $this->actor());

        foreach ([$create, $update] as $result) {
            self::assertSame('Translations.ManageLocalesDenied', $result->getError()->code);
            self::assertSame(HttpStatus::FORBIDDEN, $result->getError()->status);
        }

        self::assertSame(0, $this->locales->writes);
    }

    private function service(bool $allows = true): TranslationLocaleAdminService
    {
        $checker = new class($allows) implements ActorPermissionCheckerInterface
        {
            public function __construct(private readonly bool $allows)
            {
            }

            public function allows(AuthenticatedUser $actor, PanelPermission $permission): bool
            {
                return $this->allows && $permission === PanelPermission::LocalesManage;
            }
        };

        return new TranslationLocaleAdminService($this->locales, new TranslationLocaleManagementPolicy($checker));
    }

    private function actor(): AuthenticatedUser
    {
        return (new \ReflectionClass(AuthenticatedUser::class))->newInstanceWithoutConstructor();
    }
}

/** @internal In-memory language port that counts writes, so "nothing was written" can be asserted. */
final class WritableLocales implements TranslationLocaleRepositoryInterface
{
    public int $writes = 0;

    /** @param list<TranslationLocale> $locales */
    public function __construct(private array $locales)
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

    public function create(string $code, string $name, bool $isActive): TranslationLocale
    {
        $this->writes++;

        return $this->locales[] = new TranslationLocale(count($this->locales) + 1, $code, $name, $isActive);
    }

    public function update(int $id, string $name, bool $isActive): TranslationLocale
    {
        $this->writes++;

        foreach ($this->locales as $index => $locale) {
            if ($locale->getIdValue() === $id) {
                return $this->locales[$index] = new TranslationLocale($id, $locale->getCode(), $name, $isActive);
            }
        }

        throw new \LogicException("No locale {$id}");
    }
}
