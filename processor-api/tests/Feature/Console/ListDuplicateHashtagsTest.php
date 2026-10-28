<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesHashtags;
use Tests\TestCase;

final class ListDuplicateHashtagsTest extends TestCase
{
    use CreatesHashtags;
    use RefreshDatabase;

    public function test_it_lists_each_group_of_case_and_width_variants(): void
    {
        $lower = $this->hashtag('#jlpt');
        $upper = $this->hashtag('#JLPT');
        $wide = $this->hashtag('＃ＪＬＰＴ');
        $this->hashtag('#n5');

        $this->artisan('hashtags:duplicates')
            ->expectsTable(['Key', 'Tags', 'Count'], [
                ['#jlpt', "#jlpt (#{$lower->id}), #JLPT (#{$upper->id}), ＃ＪＬＰＴ (#{$wide->id})", 3],
            ])
            ->expectsOutputToContain('1 duplicate group(s)')
            ->assertSuccessful();
    }

    public function test_it_says_so_when_there_are_none(): void
    {
        $this->hashtag('#jlpt');
        $this->hashtag('#n5');

        $this->artisan('hashtags:duplicates')
            ->expectsOutput('No duplicate hashtags.')
            ->assertSuccessful();
    }
}
