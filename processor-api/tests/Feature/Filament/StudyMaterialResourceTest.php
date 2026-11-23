<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Domain\Activity\Enums\ActivityAction;
use App\Filament\Resources\StudyMaterial\Kanjis\Pages\ListKanjis;
use App\Filament\Resources\StudyMaterial\Kanjis\Pages\ViewKanji;
use App\Filament\Resources\StudyMaterial\Radicals\Pages\ListRadicals;
use App\Filament\Resources\StudyMaterial\RelationManagers\WordsRelationManager;
use App\Filament\Resources\StudyMaterial\Sentences\Pages\ListSentences;
use App\Filament\Resources\StudyMaterial\Sentences\Pages\ViewSentence;
use App\Filament\Resources\StudyMaterial\Words\Pages\ListWords;
use App\Infrastructure\Persistence\Models\AdminActivity;
use App\Infrastructure\Persistence\Models\Kanji;
use App\Infrastructure\Persistence\Models\Radical;
use App\Infrastructure\Persistence\Models\Sentence;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\Word;
use App\Support\JapaneseDataImport\JapaneseDataImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RuntimeException;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\TestCase;

class StudyMaterialResourceTest extends TestCase
{
    use InteractsWithAdminPanel;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAdminPanel();
        $this->admin = $this->createAdmin();
        $this->actingAsPanelUser($this->admin);
    }

    public function test_kanji_can_be_browsed_searched_and_filtered(): void
    {
        $water = $this->kanji('水', ['jlpt' => '5', 'grade' => '1', 'stroke_count' => 4, 'meaning' => 'water']);
        $harmony = $this->kanji('和', ['jlpt' => '3', 'grade' => '3', 'stroke_count' => 8, 'meaning' => 'harmony']);

        Livewire::test(ListKanjis::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$water, $harmony]);

        Livewire::test(ListKanjis::class)
            ->searchTable('水')
            ->assertCanSeeTableRecords([$water])
            ->assertCanNotSeeTableRecords([$harmony]);

        Livewire::test(ListKanjis::class)
            ->filterTable('jlpt', '3')
            ->assertCanSeeTableRecords([$harmony])
            ->assertCanNotSeeTableRecords([$water]);

        Livewire::test(ListKanjis::class)
            ->filterTable('strokes', ['min' => 5])
            ->assertCanSeeTableRecords([$harmony])
            ->assertCanNotSeeTableRecords([$water]);
    }

    /**
     * The public API hides the '????' rows (#545); admins still need to find them to repair them.
     */
    public function test_kanji_browser_still_lists_rows_with_a_corrupt_character(): void
    {
        $water = $this->kanji('水');
        $corrupt = $this->kanji('????');

        Livewire::test(ListKanjis::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$water, $corrupt]);
    }

    public function test_kanji_view_shows_related_words_paginated(): void
    {
        $water = $this->kanji('水', ['meaning' => 'water']);
        $words = collect(range(1, 12))->map(fn (int $index): Word => $this->word("水{$index}"));
        foreach ($words as $word) {
            DB::table('japanese_kanji_word_long')->insert(['kanji_id' => $water->id, 'word_id' => $word->id]);
        }

        Livewire::test(ViewKanji::class, ['record' => $water->uuid])
            ->assertOk()
            ->assertSee('water')
            ->assertSee('EDRDG');

        Livewire::test(WordsRelationManager::class, ['ownerRecord' => $water, 'pageClass' => ViewKanji::class])
            ->assertOk()
            ->assertCanSeeTableRecords($words->take(10))
            ->assertCountTableRecords(12);
    }

    public function test_radicals_words_and_sentences_lists_render(): void
    {
        $radical = Radical::query()->create(['uuid' => (string) Str::uuid(), 'radical' => '氵', 'strokes' => 3, 'meaning' => 'water']);
        $word = $this->word('水道', ['jlpt' => '4']);
        $sentence = $this->sentence('水を飲みます。');

        Livewire::test(ListRadicals::class)->assertOk()->assertCanSeeTableRecords([$radical]);
        Livewire::test(ListWords::class)->assertOk()->assertCanSeeTableRecords([$word]);
        Livewire::test(ListWords::class)->searchTable('水道')->assertCanSeeTableRecords([$word]);
        Livewire::test(ListSentences::class)->assertOk()->assertCanSeeTableRecords([$sentence]);
    }

    public function test_sentence_source_filter_separates_user_authored_rows(): void
    {
        $imported = $this->sentence('輸入された文です。');
        $authored = $this->sentence('私が書いた文です。', $this->createPanelUser('Writer'));

        Livewire::test(ListSentences::class)
            ->filterTable('user_id', true)
            ->assertCanSeeTableRecords([$authored])
            ->assertCanNotSeeTableRecords([$imported]);

        Livewire::test(ListSentences::class)
            ->filterTable('user_id', false)
            ->assertCanSeeTableRecords([$imported])
            ->assertCanNotSeeTableRecords([$authored]);
    }

    public function test_imported_sentences_are_read_only(): void
    {
        $imported = $this->sentence('輸入された文です。');

        Livewire::test(ViewSentence::class, ['record' => $imported->uuid])
            ->assertOk()
            ->assertActionHidden('deleteSentence');
    }

    public function test_staff_remove_user_authored_sentences_but_never_edit_them(): void
    {
        $writer = $this->createPanelUser('Writer');
        $sentence = $this->sentence('古い文です。', $writer);

        Livewire::test(ViewSentence::class, ['record' => $sentence->uuid])
            ->assertActionDoesNotExist('editSentence')
            ->callAction('deleteSentence')
            ->assertNotified('Sentence deleted.');

        self::assertNull($sentence->fresh());

        $activity = AdminActivity::query()->sole();
        self::assertSame(ActivityAction::SentenceDeleted, $activity->action);
        self::assertSame('古い文です。', $activity->changes['before']['content']);
    }

    public function test_the_panel_policy_never_updates_and_deletes_only_user_authored_sentences(): void
    {
        $moderator = $this->createUserWithPermissions(['study.view', 'sentences.moderate']);
        $viewer = $this->createUserWithPermissions(['study.view']);
        $authored = $this->sentence('私が書いた文です。', $this->createPanelUser('Writer'));
        $imported = $this->sentence('辞書の文です。', null);

        self::assertFalse(Gate::forUser($moderator)->allows('update', $authored));
        self::assertTrue(Gate::forUser($moderator)->allows('delete', $authored));
        self::assertFalse(Gate::forUser($moderator)->allows('delete', $imported));
        self::assertFalse(Gate::forUser($viewer)->allows('delete', $authored));
    }

    public function test_sentence_removal_needs_sentences_moderate(): void
    {
        $sentence = $this->sentence('私が書いた文です。', $this->createPanelUser('Writer'));
        $this->actingAsPanelUser($this->createUserWithPermissions(['study.view']));

        Livewire::test(ViewSentence::class, ['record' => $sentence->uuid])
            ->assertOk()
            ->assertActionHidden('deleteSentence');

        $this->actingAs($this->createUserWithPermissions(['posts.view']), 'web')
            ->get('/admin/study-material/kanjis')
            ->assertForbidden();
    }

    public function test_import_rerun_refuses_to_delete_user_authored_sentences(): void
    {
        $this->sentence('私が書いた文です。', $this->createPanelUser('Writer'));

        $importer = app(JapaneseDataImporter::class);
        $guard = new \ReflectionMethod($importer, 'assertNoUserAuthoredSentencesWouldBeLost');

        try {
            $guard->invoke($importer, [['table' => 'japanese_tatoeba_sentences', 'source' => 'x.sql']]);
            self::fail('The guard should have refused.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('1 user-authored sentence', $exception->getMessage());
            self::assertStringContainsString('--discard-user-sentences', $exception->getMessage());
        }

        $guard->invoke($importer, [['table' => 'japanese_kanji_bank_long', 'source' => 'x.sql']]);
        self::assertSame(1, Sentence::query()->count());
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function kanji(string $character, array $attributes = []): Kanji
    {
        return Kanji::query()->forceCreate([
            'uuid' => (string) Str::uuid(),
            'kanji' => $character,
            'onyomi' => 'スイ',
            'kunyomi' => 'みず',
            'meaning' => 'meaning',
            'nanori' => '-',
            'jlpt' => '5',
            'grade' => '1',
            'stroke_count' => 4,
            'frequency' => 1,
            'radicals' => 'water',
            'radical_parts' => '水',
            ...$attributes,
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function word(string $word, array $attributes = []): Word
    {
        return Word::query()->forceCreate([
            'uuid' => (string) Str::uuid(),
            'word' => $word,
            'entry_sequence' => (string) random_int(1000000, 9999999),
            'furigana' => 'みず',
            'jlpt' => '5',
            'word_type' => 'noun',
            'word_k_ele' => '[]',
            'furigana_r_ele' => '[]',
            'sense' => json_encode([[['gloss', ['water']]]]),
            ...$attributes,
        ]);
    }

    private function sentence(string $content, ?User $author = null): Sentence
    {
        return Sentence::query()->forceCreate([
            'uuid' => (string) Str::uuid(),
            'content' => $content,
            'user_id' => $author?->id,
        ]);
    }
}
