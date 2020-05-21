<?php

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
        $articleTemplate = new \App\ObjectTemplate;
        $articleTemplate->title = "article";
        $articleTemplate->save();

        $artistTemplate = new \App\ObjectTemplate;
        $artistTemplate->title = "artist";
        $artistTemplate->save();
        
        $lyricTemplate = new \App\ObjectTemplate;
        $lyricTemplate->title = "lyric";
        $lyricTemplate->save();

        $radicalTemplate = new \App\ObjectTemplate;
        $radicalTemplate->title = "radical";
        $radicalTemplate->save();

        $kanjiTemplate = new \App\ObjectTemplate;
        $kanjiTemplate->title = "kanji";
        $kanjiTemplate->save();
        
        $wordTemplate = new \App\ObjectTemplate;
        $wordTemplate->title = "word";
        $wordTemplate->save();

        $sentenceTemplate = new \App\ObjectTemplate;
        $sentenceTemplate->title = "sentence";
        $sentenceTemplate->save();

        $listTemplate = new \App\ObjectTemplate;
        $listTemplate->title = "list";
        $listTemplate->save();

        $postTemplate = new \App\ObjectTemplate;
        $postTemplate->title = "post";
        $postTemplate->save();

        $commentTemplate = new \App\ObjectTemplate;
        $commentTemplate->title = "comment";
        $commentTemplate->save();
    }
}
