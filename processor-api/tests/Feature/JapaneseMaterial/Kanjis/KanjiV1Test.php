<?php

declare(strict_types=1);

namespace Tests\Feature\JapaneseMaterial\Kanjis;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class KanjiV1Test extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_list_kanjis_with_pagination(): void
    {
        $firstUuid = (string) Str::uuid();
        $secondUuid = (string) Str::uuid();

        $this->createKanji(id: 1, uuid: $firstUuid, kanji: '水', meaning: 'water|river', jlpt: '5', strokeCount: '4');
        $this->createKanji(id: 2, uuid: $secondUuid, kanji: '火', meaning: 'fire|flame', jlpt: '5', strokeCount: '4');

        $response = $this->getJson('/api/v1/kanjis?per_page=1');

        $response->assertOk()
            ->assertJsonMissingPath('success')
            ->assertJsonMissingPath('data')
            ->assertJsonPath('items.0.id', 1)
            ->assertJsonPath('items.0.uuid', $firstUuid)
            ->assertJsonPath('items.0.character', '水')
            ->assertJsonPath('items.0.meanings.0', 'water')
            ->assertJsonPath('items.0.meanings.1', 'river')
            ->assertJsonPath('items.0.onyomi.0', 'スイ')
            ->assertJsonPath('items.0.kunyomi.0', 'みず')
            ->assertJsonPath('items.0.stroke_count', 4)
            ->assertJsonPath('items.0.jlpt', '5')
            ->assertJsonPath('items.0.frequency', 2)
            ->assertJsonPath('pagination.page', 1)
            ->assertJsonPath('pagination.per_page', 1)
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonPath('pagination.has_more', true);
    }

    public function test_guest_can_filter_kanjis_by_keyword_and_jlpt(): void
    {
        $this->createKanji(id: 1, kanji: '水', meaning: 'water|river', jlpt: '5');
        $this->createKanji(id: 2, kanji: '火', meaning: 'fire|flame', jlpt: '4');
        $this->createKanji(id: 3, kanji: '川', meaning: 'river|stream', jlpt: '5');

        $response = $this->getJson('/api/v1/kanjis?keyword=river&jlpt=5');

        $response->assertOk()
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('items.0.character', '水')
            ->assertJsonPath('items.1.character', '川')
            ->assertJsonPath('pagination.total', 2);
    }

    public function test_guest_can_filter_kanjis_by_stroke_range(): void
    {
        $this->createKanji(id: 1, kanji: '一', meaning: 'one', strokeCount: '1');
        $this->createKanji(id: 2, kanji: '水', meaning: 'water', strokeCount: '4');
        $this->createKanji(id: 3, kanji: '語', meaning: 'language', strokeCount: '14');

        $response = $this->getJson('/api/v1/kanjis?min_stroke_count=4&max_stroke_count=14');

        $response->assertOk()
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('items.0.character', '水')
            ->assertJsonPath('items.1.character', '語')
            ->assertJsonPath('pagination.total', 2);
    }

    public function test_guest_can_fetch_kanji_detail_by_uuid(): void
    {
        $uuid = (string) Str::uuid();

        $this->createKanji(id: 10, uuid: $uuid, kanji: '水', meaning: 'water|river', jlpt: '5');

        $response = $this->getJson("/api/v1/kanjis/{$uuid}");

        $response->assertOk()
            ->assertJsonMissingPath('success')
            ->assertJsonMissingPath('data')
            ->assertJsonPath('id', 10)
            ->assertJsonPath('uuid', $uuid)
            ->assertJsonPath('character', '水')
            ->assertJsonPath('meanings.0', 'water')
            ->assertJsonPath('meanings.1', 'river')
            ->assertJsonPath('jlpt', '5');
    }

    public function test_guest_can_fetch_kanji_detail_by_character(): void
    {
        $uuid = (string) Str::uuid();

        $this->createKanji(id: 20, uuid: $uuid, kanji: '火', meaning: 'fire', jlpt: '4');

        $response = $this->getJson('/api/v1/kanjis/'.rawurlencode('火'));

        $response->assertOk()
            ->assertJsonPath('id', 20)
            ->assertJsonPath('uuid', $uuid)
            ->assertJsonPath('character', '火')
            ->assertJsonPath('meanings.0', 'fire');
    }

    public function test_invalid_kanji_identifier_returns_bad_request_problem_response(): void
    {
        $response = $this->getJson('/api/v1/kanjis/not-a-valid-identifier');

        $response->assertBadRequest()
            ->assertJsonPath('status', 400)
            ->assertJsonPath('title', 'Identifier must be a valid UUID or a single Kanji character.');
    }

    public function test_kanji_list_rejects_invalid_per_page(): void
    {
        $response = $this->getJson('/api/v1/kanjis?per_page=101');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    private function createKanji(
        int $id,
        ?string $uuid = null,
        string $kanji = '水',
        string $onyomi = 'スイ',
        string $kunyomi = 'みず',
        string $meaning = 'water',
        string $nanori = '',
        string $grade = '1',
        string $strokeCount = '4',
        string $jlpt = '5',
        string $frequency = '2',
        string $radicals = '水',
        string $radicalParts = '水',
    ): void {
        DB::table('japanese_kanji_bank_long')->insert([
            'id' => $id,
            'uuid' => $uuid ?? (string) Str::uuid(),
            'kanji' => $kanji,
            'onyomi' => $onyomi,
            'kunyomi' => $kunyomi,
            'meaning' => $meaning,
            'nanori' => $nanori,
            'grade' => $grade,
            'stroke_count' => $strokeCount,
            'jlpt' => $jlpt,
            'frequency' => $frequency,
            'radicals' => $radicals,
            'radical_parts' => $radicalParts,
        ]);
    }
}
