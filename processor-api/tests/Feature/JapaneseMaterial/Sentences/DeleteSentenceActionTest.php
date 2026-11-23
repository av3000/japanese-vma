<?php

declare(strict_types=1);

namespace Tests\Feature\JapaneseMaterial\Sentences;

use App\Application\JapaneseMaterial\Sentences\Actions\DeleteSentenceAction;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\SavedListType;
use App\Infrastructure\Persistence\Models\Sentence;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

/**
 * The one place a user-authored sentence and its dependents are removed, for both the author's
 * delete and a moderator's removal. Rows that belong to another entity with the same id stay.
 */
final class DeleteSentenceActionTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    public function test_the_sentence_and_its_dependent_rows_are_removed(): void
    {
        $author = User::factory()->create();
        $sentence = Sentence::query()->forceCreate([
            'uuid' => (string) Str::uuid(),
            'content' => '私が書いた文です。',
            'user_id' => $author->id,
        ]);
        $sentenceTemplate = ObjectTemplateType::SENTENCE->getLegacyId();
        $articleTemplate = ObjectTemplateType::ARTICLE->getLegacyId();

        DB::table('customlist_object')->insert([
            ['list_id' => 1, 'listtype_id' => SavedListType::SENTENCES->value, 'real_object_id' => $sentence->id],
            ['list_id' => 2, 'listtype_id' => SavedListType::KNOWNSENTENCES->value, 'real_object_id' => $sentence->id],
            ['list_id' => 3, 'listtype_id' => SavedListType::ARTICLES->value, 'real_object_id' => $sentence->id],
        ]);

        foreach ([$sentenceTemplate, $articleTemplate] as $template) {
            $row = ['template_id' => $template, 'real_object_id' => $sentence->id, 'user_id' => $author->id];
            DB::table('views')->insert($row);
            DB::table('downloads')->insert($row);
            DB::table('likes')->insert([...$row, 'value' => true]);
            DB::table('comments')->insert([...$row, 'uuid' => (string) Str::uuid(), 'content' => 'A comment']);
        }

        app(DeleteSentenceAction::class)->execute($sentence->id);

        self::assertNull($sentence->fresh());
        self::assertSame([SavedListType::ARTICLES->value], DB::table('customlist_object')->where('real_object_id', $sentence->id)->pluck('listtype_id')->map(intval(...))->all());

        foreach (['views', 'downloads', 'likes', 'comments'] as $table) {
            self::assertSame(0, DB::table($table)->where('template_id', $sentenceTemplate)->where('real_object_id', $sentence->id)->count(), $table);
            self::assertSame(1, DB::table($table)->where('template_id', $articleTemplate)->where('real_object_id', $sentence->id)->count(), $table);
        }
    }
}
