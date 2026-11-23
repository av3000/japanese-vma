<?php

declare(strict_types=1);

namespace Tests\Unit\JapaneseMaterial\Words;

use App\Domain\JapaneseMaterial\Words\ValueObjects\WordSense;
use PHPUnit\Framework\TestCase;

class WordSenseTest extends TestCase
{
    public function test_deru_keeps_its_twenty_senses_and_all_77_glosses_in_order(): void
    {
        $senses = WordSense::listFromJmdict((string) file_get_contents(__DIR__.'/../../../Fixtures/words/deru-sense.json'));

        $this->assertCount(20, $senses);
        $this->assertSame(77, array_sum(array_map(static fn (WordSense $sense): int => count($sense->glosses), $senses)));
        $this->assertSame(['to leave', 'to exit', 'to go out', 'to come out', 'to get out'], $senses[0]->glosses);
        $this->assertSame(['Ichidan verb', 'intransitive verb'], $senses[0]->partsOfSpeech);
        $this->assertSame(['to graduate'], $senses[19]->glosses);
    }

    public function test_a_sense_without_parts_of_speech_inherits_the_previous_ones(): void
    {
        $senses = WordSense::listFromJmdict((string) file_get_contents(__DIR__.'/../../../Fixtures/words/deru-sense.json'));

        $this->assertSame(['Ichidan verb', 'intransitive verb'], $senses[1]->partsOfSpeech);
    }

    public function test_notes_misc_and_dialect_are_kept_apart_from_glosses(): void
    {
        $raw = '[[["pos",{"0":"expressions (phrases, clauses, etc.)"}],["misc",{"0":"word usually written using kana alone"}],'
            .'["s_inf",{"0":"commonly conjugated as an adj-i"}],["dial",{"0":"Kansai-ben"}],'
            .'["gloss",{"0":"useless"}],["gloss",{"0":"no good"}]]]';

        $senses = WordSense::listFromJmdict($raw);

        $this->assertCount(1, $senses);
        $this->assertSame(['expressions (phrases, clauses, etc.)'], $senses[0]->partsOfSpeech);
        $this->assertSame(['useless', 'no good'], $senses[0]->glosses);
        $this->assertSame(['commonly conjugated as an adj-i'], $senses[0]->notes);
        $this->assertSame(['word usually written using kana alone', 'Kansai-ben'], $senses[0]->misc);
    }

    public function test_gloss_attributes_are_not_read_as_text(): void
    {
        $raw = '[[["pos",{"0":"noun (common) (futsuumeishi)"}],["xref",{"0":"同の字点"}],'
            .'["gloss",{"@attributes":{"g_type":"expl"},"0":"kanji iteration mark"}]]]';

        $this->assertSame(['kanji iteration mark'], WordSense::listFromJmdict($raw)[0]->glosses);
    }

    public function test_anything_but_the_jmdict_shape_gives_no_senses(): void
    {
        $this->assertSame([], WordSense::listFromJmdict(null));
        $this->assertSame([], WordSense::listFromJmdict('study'));
        $this->assertSame([], WordSense::listFromJmdict('[]'));
        $this->assertSame([], WordSense::listFromJmdict('[[["pos",{"0":"noun"}]]]'));
    }
}
