<?php

namespace Database\Seeders;

use App\Http\Models\ObjectTemplate;
use Illuminate\Database\Seeder;

class ObjectTemplatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $articleTemplate = new ObjectTemplate;
        $articleTemplate->title = 'article';
        $articleTemplate->save();

        $artistTemplate = new ObjectTemplate;
        $artistTemplate->title = 'artist';
        $artistTemplate->save();

        $lyricTemplate = new ObjectTemplate;
        $lyricTemplate->title = 'lyric';
        $lyricTemplate->save();

        $radicalTemplate = new ObjectTemplate;
        $radicalTemplate->title = 'radical';
        $radicalTemplate->save();

        $kanjiTemplate = new ObjectTemplate;
        $kanjiTemplate->title = 'kanji';
        $kanjiTemplate->save();

        $wordTemplate = new ObjectTemplate;
        $wordTemplate->title = 'word';
        $wordTemplate->save();

        $sentenceTemplate = new ObjectTemplate;
        $sentenceTemplate->title = 'sentence';
        $sentenceTemplate->save();

        $listTemplate = new ObjectTemplate;
        $listTemplate->title = 'list';
        $listTemplate->save();

        $postTemplate = new ObjectTemplate;
        $postTemplate->title = 'post';
        $postTemplate->save();

        $commentTemplate = new ObjectTemplate;
        $commentTemplate->title = 'comment';
        $commentTemplate->save();
    }
}
