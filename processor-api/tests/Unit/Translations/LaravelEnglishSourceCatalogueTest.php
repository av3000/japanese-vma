<?php

declare(strict_types=1);

namespace Tests\Unit\Translations;

use App\Infrastructure\Translations\LaravelEnglishSourceCatalogue;
use Illuminate\Contracts\Translation\Loader;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LaravelEnglishSourceCatalogueTest extends TestCase
{
    /**
     * @param array<string, mixed> $ui
     */
    private function catalogueFor(array $ui): LaravelEnglishSourceCatalogue
    {
        $loader = $this->createMock(Loader::class);
        $loader->method('load')
            ->with('en', 'ui')
            ->willReturn($ui);

        return new LaravelEnglishSourceCatalogue($loader);
    }

    public function test_nested_arrays_are_flattened_to_dotted_keys(): void
    {
        $catalogue = $this->catalogueFor([
            'common' => ['save' => 'Save', 'cancel' => 'Cancel'],
            'auth' => ['login' => ['email' => 'Email', 'password' => 'Password']],
            'standalone' => 'Alone',
        ]);

        $this->assertSame([
            'common.save' => 'Save',
            'common.cancel' => 'Cancel',
            'auth.login.email' => 'Email',
            'auth.login.password' => 'Password',
            'standalone' => 'Alone',
        ], $catalogue->messages());
    }

    public function test_find_returns_the_english_text_of_a_known_key(): void
    {
        $catalogue = $this->catalogueFor(['auth' => ['login' => ['email' => 'Email']]]);

        $this->assertSame('Email', $catalogue->find('auth.login.email'));
    }

    public function test_find_returns_null_for_an_unknown_key(): void
    {
        $catalogue = $this->catalogueFor(['common' => ['save' => 'Save']]);

        $this->assertNull($catalogue->find('common.missing'));
        $this->assertNull($catalogue->find('common'), 'A group is not a key.');
    }

    public function test_an_empty_value_fails_loudly(): void
    {
        $catalogue = $this->catalogueFor(['common' => ['save' => '']]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('common.save');

        $catalogue->messages();
    }

    public function test_a_whitespace_only_value_fails_loudly(): void
    {
        $catalogue = $this->catalogueFor(['common' => ['save' => "  \n"]]);

        $this->expectException(InvalidArgumentException::class);

        $catalogue->messages();
    }

    public function test_a_non_string_value_fails_loudly(): void
    {
        $catalogue = $this->catalogueFor(['common' => ['count' => 3]]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('common.count');

        $catalogue->messages();
    }

    public function test_the_real_english_catalogue_has_keys_and_no_empty_value(): void
    {
        $loader = new \Illuminate\Translation\FileLoader(
            new \Illuminate\Filesystem\Filesystem,
            dirname(__DIR__, 3).'/resources/lang',
        );

        $messages = (new LaravelEnglishSourceCatalogue($loader))->messages();

        $this->assertNotEmpty($messages);

        foreach ($messages as $key => $value) {
            $this->assertMatchesRegularExpression('/^[a-z][a-z0-9_]*(\.[a-z0-9_]+)+$/', $key, "Key {$key} is not a dotted semantic key.");
            $this->assertNotSame('', trim($value), "Key {$key} is empty.");
        }
    }
}
