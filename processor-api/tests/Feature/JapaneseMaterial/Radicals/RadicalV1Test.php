<?php

namespace Tests\Feature\JapaneseMaterial\Radicals;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class RadicalV1Test extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_list_radicals_with_pagination(): void
    {
        $firstUuid = (string) Str::uuid();
        $secondUuid = (string) Str::uuid();

        $this->createRadical(id: 1, uuid: $firstUuid, radical: '一', strokes: 1, meaning: 'one', hiragana: 'いち / ichi');
        $this->createRadical(id: 2, uuid: $secondUuid, radical: '丨', strokes: 1, meaning: 'line', hiragana: 'ぼう / bou');

        $response = $this->getJson('/api/v1/radicals?per_page=1');

        $response->assertOk()
            ->assertJsonMissingPath('success')
            ->assertJsonMissingPath('data')
            ->assertJsonPath('items.0.id', 1)
            ->assertJsonPath('items.0.uuid', $firstUuid)
            ->assertJsonPath('items.0.radical', '一')
            ->assertJsonPath('items.0.strokes', 1)
            ->assertJsonPath('items.0.meaning', 'one')
            ->assertJsonPath('items.0.hiragana', 'いち / ichi')
            ->assertJsonPath('pagination.page', 1)
            ->assertJsonPath('pagination.per_page', 1)
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonPath('pagination.has_more', true);
    }

    public function test_guest_can_filter_radicals_by_keyword_and_strokes(): void
    {
        $this->createRadical(id: 1, radical: '一', strokes: 1, meaning: 'one', hiragana: 'いち / ichi');
        $this->createRadical(id: 2, radical: '水', strokes: 4, meaning: 'water', hiragana: 'みず / mizu');
        $this->createRadical(id: 3, radical: '火', strokes: 4, meaning: 'fire', hiragana: 'ひ / hi');

        $response = $this->getJson('/api/v1/radicals?keyword=water&strokes=4');

        $response->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.radical', '水')
            ->assertJsonPath('items.0.meaning', 'water')
            ->assertJsonPath('pagination.total', 1);
    }

    public function test_guest_can_fetch_radical_detail_by_uuid_with_related_kanjis(): void
    {
        $radicalUuid = (string) Str::uuid();
        $kanjiUuid = (string) Str::uuid();

        $this->createRadical(id: 1, uuid: $radicalUuid, radical: '一', strokes: 1, meaning: 'one', hiragana: 'いち / ichi');
        $this->createKanji(id: 10, uuid: $kanjiUuid, kanji: '一');

        DB::table('japanese_radical_kanji_long')->insert([
            'radical_id' => 1,
            'kanji_id' => 10,
        ]);

        $response = $this->getJson("/api/v1/radicals/{$radicalUuid}");

        $response->assertOk()
            ->assertJsonMissingPath('success')
            ->assertJsonMissingPath('data')
            ->assertJsonPath('id', 1)
            ->assertJsonPath('uuid', $radicalUuid)
            ->assertJsonPath('radical', '一')
            ->assertJsonPath('kanjis.0.uuid', $kanjiUuid)
            ->assertJsonPath('kanjis.0.character', '一');
    }

    public function test_guest_can_fetch_radical_detail_by_legacy_numeric_id(): void
    {
        $radicalUuid = (string) Str::uuid();

        $this->createRadical(id: 77, uuid: $radicalUuid, radical: '水', strokes: 4, meaning: 'water', hiragana: 'みず / mizu');

        $response = $this->getJson('/api/v1/radicals/77');

        $response->assertOk()
            ->assertJsonPath('id', 77)
            ->assertJsonPath('uuid', $radicalUuid)
            ->assertJsonPath('radical', '水')
            ->assertJsonPath('kanjis', []);
    }

    public function test_unknown_radical_returns_problem_response(): void
    {
        $missingUuid = (string) Str::uuid();

        $response = $this->getJson("/api/v1/radicals/{$missingUuid}");

        $response->assertNotFound()
            ->assertJsonPath('status', 404)
            ->assertJsonPath('title', "Radical with identifier '{$missingUuid}' not found.");
    }

    public function test_invalid_radical_identifier_returns_bad_request_problem_response(): void
    {
        $response = $this->getJson('/api/v1/radicals/not-a-valid-identifier');

        $response->assertBadRequest()
            ->assertJsonPath('status', 400)
            ->assertJsonPath('title', 'Identifier must be a valid UUID or numeric radical ID.');
    }

    public function test_radical_list_rejects_invalid_per_page(): void
    {
        $response = $this->getJson('/api/v1/radicals?per_page=101');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    private function createRadical(
        int $id,
        ?string $uuid = null,
        string $radical = '一',
        int $strokes = 1,
        string $meaning = 'one',
        string $hiragana = 'いち / ichi',
    ): void {
        DB::table('japanese_radicals_bank_long')->insert([
            'id' => $id,
            'uuid' => $uuid ?? (string) Str::uuid(),
            'radical' => $radical,
            'strokes' => $strokes,
            'meaning' => $meaning,
            'hiragana' => $hiragana,
        ]);
    }

    private function createKanji(int $id, ?string $uuid = null, string $kanji = '一'): void
    {
        DB::table('japanese_kanji_bank_long')->insert([
            'id' => $id,
            'uuid' => $uuid ?? (string) Str::uuid(),
            'kanji' => $kanji,
            'onyomi' => 'イチ',
            'kunyomi' => 'ひと',
            'meaning' => 'one',
            'nanori' => '',
            'grade' => '1',
            'stroke_count' => '1',
            'jlpt' => '5',
            'frequency' => '2',
            'radicals' => '1',
            'radical_parts' => $kanji,
        ]);
    }
}
