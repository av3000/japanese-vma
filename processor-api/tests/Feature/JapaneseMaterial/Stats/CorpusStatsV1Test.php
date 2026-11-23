<?php

namespace Tests\Feature\JapaneseMaterial\Stats;

use App\Application\JapaneseMaterial\Stats\Interfaces\Caches\CorpusStatsCacheInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CorpusStatsV1Test extends TestCase
{
    use RefreshDatabase;

    public function test_guest_gets_corpus_counts_as_integers(): void
    {
        $this->createRadical(1);
        $this->createRadical(2);
        $this->createKanji(1);
        $this->createWord(1);
        $this->createWord(2);
        $this->createWord(3);
        $this->createSentence(1);

        $response = $this->getJson('/api/v1/japanese-material/stats');

        $response->assertOk()
            ->assertJsonMissingPath('data')
            ->assertJsonMissingPath('success')
            ->assertExactJson([
                'radicals' => 2,
                'kanjis' => 1,
                'words' => 3,
                'sentences' => 1,
            ]);
    }

    public function test_empty_corpus_returns_zeroes(): void
    {
        $this->getJson('/api/v1/japanese-material/stats')
            ->assertOk()
            ->assertExactJson(['radicals' => 0, 'kanjis' => 0, 'words' => 0, 'sentences' => 0]);
    }

    public function test_response_is_publicly_cacheable_for_five_minutes(): void
    {
        $response = $this->getJson('/api/v1/japanese-material/stats');

        $response->assertOk();
        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=300', $cacheControl);
    }

    public function test_counts_are_served_from_cache_until_forgotten(): void
    {
        $this->createSentence(1);

        $this->getJson('/api/v1/japanese-material/stats')->assertJsonPath('sentences', 1);

        // A user-authored sentence does not invalidate the cache: up to an hour of lag is accepted.
        $this->createSentence(2);
        $this->getJson('/api/v1/japanese-material/stats')->assertJsonPath('sentences', 1);

        $this->app->make(CorpusStatsCacheInterface::class)->forget();

        $this->getJson('/api/v1/japanese-material/stats')->assertJsonPath('sentences', 2);
    }

    private function createRadical(int $id): void
    {
        DB::table('japanese_radicals_bank_long')->insert([
            'id' => $id,
            'uuid' => (string) Str::uuid(),
            'radical' => '一',
            'strokes' => 1,
            'meaning' => 'one',
            'hiragana' => 'いち / ichi',
        ]);
    }

    private function createKanji(int $id): void
    {
        DB::table('japanese_kanji_bank_long')->insert([
            'id' => $id,
            'uuid' => (string) Str::uuid(),
            'kanji' => '一',
            'onyomi' => 'イチ',
            'kunyomi' => 'ひと',
            'meaning' => 'one',
            'nanori' => '',
            'grade' => '1',
            'stroke_count' => '1',
            'jlpt' => '5',
            'frequency' => '2',
            'radicals' => '1',
            'radical_parts' => '一',
        ]);
    }

    private function createWord(int $id): void
    {
        DB::table('japanese_word_bank_long')->insert([
            'id' => $id,
            'uuid' => (string) Str::uuid(),
            'entry_sequence' => (string) (1000 + $id),
            'word' => '一',
            'furigana' => 'いち',
            'jlpt' => 'N5',
            'word_type' => 'noun',
            'word_k_ele' => '一',
            'furigana_r_ele' => 'いち',
            'sense' => json_encode([[['gloss', ['one']]]], JSON_THROW_ON_ERROR),
        ]);
    }

    private function createSentence(int $id): void
    {
        DB::table('japanese_tatoeba_sentences')->insert([
            'id' => $id,
            'uuid' => (string) Str::uuid(),
            'user_id' => null,
            'tatoeba_entry' => (string) (2000 + $id),
            'content' => '一つです。',
        ]);
    }
}
