<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp;

use App\Mcp\Support\BoundedText;
use PHPUnit\Framework\TestCase;

final class BoundedTextTest extends TestCase
{
    public function test_text_over_the_limit_is_cut_at_the_limit_and_flagged(): void
    {
        [$text, $truncated] = BoundedText::cut(str_repeat('あ', 10_001), 10_000);

        $this->assertSame(10_000, mb_strlen($text));
        $this->assertTrue($truncated);
    }

    public function test_text_exactly_at_the_limit_is_returned_whole_and_not_flagged(): void
    {
        $original = str_repeat('あ', 10_000);

        [$text, $truncated] = BoundedText::cut($original, 10_000);

        $this->assertSame($original, $text);
        $this->assertFalse($truncated);
    }

    public function test_shorter_text_is_returned_whole_and_not_flagged(): void
    {
        [$text, $truncated] = BoundedText::cut('短い文です。', 10_000);

        $this->assertSame('短い文です。', $text);
        $this->assertFalse($truncated);
    }

    public function test_the_limit_counts_characters_not_bytes(): void
    {
        // Three bytes per character: a byte count would cut this at 3 characters, or not at all.
        [$text, $truncated] = BoundedText::cut('日本語のテキスト', 5);

        $this->assertSame('日本語のテ', $text);
        $this->assertTrue($truncated);
    }

    public function test_the_cut_never_splits_a_multibyte_character(): void
    {
        [$text] = BoundedText::cut(str_repeat('日', 20), 7);

        $this->assertTrue(mb_check_encoding($text, 'UTF-8'));
        $this->assertSame(str_repeat('日', 7), $text);
    }
}
