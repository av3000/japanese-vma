<?php

declare(strict_types=1);

namespace Tests\Unit\Translations;

use App\Domain\Translations\Enums\TranslationState;
use App\Domain\Translations\Services\TranslationResolver;
use PHPUnit\Framework\TestCase;

/**
 * The override rule, with no database: a non-empty override for a key that exists in English
 * wins, anything else is English (#661, AC-5 and AC-7).
 */
class TranslationResolverTest extends TestCase
{
    private const SOURCE = [
        'account.log_in' => 'Log In',
        'account.sign_up' => 'Sign Up',
        'common.save' => 'Save',
        'common.cancel' => 'Cancel',
    ];

    private TranslationResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new TranslationResolver();
    }

    // ------------------------------------------------------------ merge

    public function test_merge_with_no_overrides_is_the_english_source(): void
    {
        $this->assertSame(self::SOURCE, $this->resolver->merge(self::SOURCE, []));
    }

    public function test_an_override_replaces_the_english_text_and_the_rest_stays_english(): void
    {
        $merged = $this->resolver->merge(self::SOURCE, [
            'account.log_in' => 'Log ind',
            'common.save' => 'Gem',
        ]);

        $this->assertSame([
            'account.log_in' => 'Log ind',
            'account.sign_up' => 'Sign Up',
            'common.save' => 'Gem',
            'common.cancel' => 'Cancel',
        ], $merged);
    }

    public function test_merge_keeps_the_english_key_order(): void
    {
        $merged = $this->resolver->merge(self::SOURCE, ['common.cancel' => 'Annuller', 'account.log_in' => 'Log ind']);

        $this->assertSame(array_keys(self::SOURCE), array_keys($merged));
    }

    public function test_an_empty_or_blank_override_falls_back_to_english(): void
    {
        $merged = $this->resolver->merge(self::SOURCE, [
            'account.log_in' => '',
            'account.sign_up' => "  \t\n ",
        ]);

        $this->assertSame(self::SOURCE, $merged);
    }

    public function test_an_override_for_a_key_that_is_gone_from_english_is_dropped(): void
    {
        $merged = $this->resolver->merge(self::SOURCE, [
            'account.log_in' => 'Log ind',
            'removed.old_label' => 'Gammel tekst',
        ]);

        $this->assertArrayNotHasKey('removed.old_label', $merged);
        $this->assertSame('Log ind', $merged['account.log_in']);
        $this->assertCount(count(self::SOURCE), $merged);
    }

    // ------------------------------------------------------------ state

    public function test_state_is_translated_for_text_and_missing_for_null_or_blank(): void
    {
        $this->assertSame(TranslationState::Translated, $this->resolver->state('Hjem'));
        $this->assertSame(TranslationState::Missing, $this->resolver->state(null));
        $this->assertSame(TranslationState::Missing, $this->resolver->state(''));
        $this->assertSame(TranslationState::Missing, $this->resolver->state('   '));
    }

    // ------------------------------------------------------------ completion

    public function test_completion_counts_valid_translated_keys_over_english_keys(): void
    {
        $completion = $this->resolver->completion(self::SOURCE, [
            'account.log_in' => 'Log ind',
            'common.save' => 'Gem',
        ]);

        $this->assertSame(2, $completion->translated);
        $this->assertSame(4, $completion->total);
        $this->assertSame(50, $completion->percent);
    }

    public function test_completion_ignores_orphans_and_blank_overrides(): void
    {
        $completion = $this->resolver->completion(self::SOURCE, [
            'account.log_in' => 'Log ind',
            'removed.old_label' => 'Gammel tekst',
            'account.sign_up' => '  ',
        ]);

        $this->assertSame(1, $completion->translated);
        $this->assertSame(4, $completion->total);
        $this->assertSame(25, $completion->percent);
    }

    public function test_completion_percent_rounds_down_and_never_reaches_100_early(): void
    {
        $source = array_combine(
            array_map(fn (int $i): string => "k.{$i}", range(1, 3)),
            ['a', 'b', 'c'],
        );

        $twoOfThree = $this->resolver->completion($source, ['k.1' => 'x', 'k.2' => 'y']);
        $this->assertSame(66, $twoOfThree->percent);

        $all = $this->resolver->completion($source, ['k.1' => 'x', 'k.2' => 'y', 'k.3' => 'z']);
        $this->assertSame(100, $all->percent);
    }

    public function test_completion_of_an_empty_catalogue_is_zero_and_does_not_divide_by_zero(): void
    {
        $completion = $this->resolver->completion([], ['a.b' => 'x']);

        $this->assertSame(0, $completion->translated);
        $this->assertSame(0, $completion->total);
        $this->assertSame(0, $completion->percent);
    }
}
