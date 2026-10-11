<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Engagement;

use App\Domain\Engagement\ValueObjects\HashtagName;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class HashtagNameTest extends TestCase
{
    public function test_case_and_width_variants_share_a_key(): void
    {
        $key = HashtagName::from('#jlpt')->key;

        self::assertSame($key, HashtagName::from('#JLPT')->key);
        self::assertSame($key, HashtagName::from('＃ＪＬＰＴ')->key);
        self::assertSame($key, HashtagName::from('  #Jlpt ')->key);
    }

    public function test_display_form_keeps_what_was_typed_without_surrounding_space(): void
    {
        self::assertSame('＃日本語', HashtagName::from(' ＃日本語 ')->display);
    }

    public function test_japanese_tags_keep_their_characters(): void
    {
        self::assertSame('#日本語', HashtagName::from('＃日本語')->key);
        self::assertNotSame(HashtagName::from('#ひらがな')->key, HashtagName::from('#カタカナ')->key);
    }

    public function test_empty_and_overlong_names_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        HashtagName::from('   ');
    }

    public function test_overlong_names_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        HashtagName::from(str_repeat('a', HashtagName::MAX_LENGTH + 1));
    }
}
