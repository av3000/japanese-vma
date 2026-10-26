<?php

declare(strict_types=1);

namespace Tests\Feature\JapaneseMaterial\Kanjis;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Rows whose character was stored as a literal '????' (#545) are left out of every
 * kanji read instead of failing the whole response with a 500.
 */
class CorruptKanjiCharacterTest extends TestCase
{
    use RefreshDatabase;

    private const CORRUPT_UUID = 'd87e0edc-bf17-41c0-92a7-e3a5b4be1a74';

    private string $readableUuid;

    protected function setUp(): void
    {
        parent::setUp();

        $this->readableUuid = (string) Str::uuid();
        $this->createKanji(1, $this->readableUuid, '水');
        $this->createKanji(2, self::CORRUPT_UUID, '????');
    }

    public function test_kanji_list_skips_the_corrupt_row_and_counts_only_readable_ones(): void
    {
        $this->getJson('/api/v1/kanjis')
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.uuid', $this->readableUuid)
            ->assertJsonPath('pagination.total', 1);
    }

    public function test_corrupt_kanji_detail_is_not_found(): void
    {
        $this->getJson('/api/v1/kanjis/'.self::CORRUPT_UUID)
            ->assertNotFound()
            ->assertJsonPath('status', 404);

        $this->getJson('/api/v1/kanjis/2')->assertNotFound();
    }

    public function test_kanji_list_filtered_by_radical_skips_the_corrupt_row(): void
    {
        $radicalUuid = (string) Str::uuid();
        DB::table('japanese_radicals_bank_long')->insert([
            'id' => 1,
            'uuid' => $radicalUuid,
            'radical' => '水',
            'strokes' => 4,
            'meaning' => 'water',
            'hiragana' => 'みず / mizu',
        ]);
        DB::table('japanese_radical_kanji_long')->insert([
            ['radical_id' => 1, 'kanji_id' => 1],
            ['radical_id' => 1, 'kanji_id' => 2],
        ]);

        $this->getJson("/api/v1/kanjis?radical_uuid={$radicalUuid}")
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.uuid', $this->readableUuid)
            ->assertJsonPath('pagination.total', 1);
    }

    public function test_word_detail_with_kanjis_skips_the_corrupt_row(): void
    {
        $wordUuid = (string) Str::uuid();
        DB::table('japanese_word_bank_long')->insert([
            'id' => 10,
            'uuid' => $wordUuid,
            'entry_sequence' => '1010',
            'word' => '咬????吧',
            'furigana' => 'こう',
            'jlpt' => '-',
            'word_type' => 'noun',
            'word_k_ele' => '咬????吧',
            'furigana_r_ele' => 'こう',
            'sense' => '[]',
        ]);
        DB::table('japanese_kanji_word_long')->insert([
            ['kanji_id' => 1, 'word_id' => 10],
            ['kanji_id' => 2, 'word_id' => 10],
        ]);

        $response = $this->getJson("/api/v1/words/{$wordUuid}?include=kanjis");

        $response->assertOk()->assertJsonCount(1, 'kanjis');
        $this->assertSame([$this->readableUuid], array_column($response->json('kanjis'), 'uuid'));
    }

    private function createKanji(int $id, string $uuid, string $kanji): void
    {
        DB::table('japanese_kanji_bank_long')->insert([
            'id' => $id,
            'uuid' => $uuid,
            'kanji' => $kanji,
            'onyomi' => 'スイ',
            'kunyomi' => 'みず',
            'meaning' => 'water',
            'nanori' => '',
            'grade' => '1',
            'stroke_count' => '4',
            'jlpt' => '5',
            'frequency' => '2',
            'radicals' => '水',
            'radical_parts' => $kanji,
        ]);
    }
}
