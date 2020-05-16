<?php

use Illuminate\Database\Seeder;

class CustomListsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // list type indexes for optimization
        $knowRadicalsList    = 1;
        $knowKanjisList      = 2;
        $knowWordsList       = 3;
        $knowSentencesList   = 4;
        $customRadicalsList  = 5;
        $customKanjisList    = 6;
        $customWordsList     = 7;
        $customSentencesList = 8;
        $customArticlesList  = 9;
        $customLyricsList    = 10;
        $customArtistsList   = 11;

        # Seeds
        // Known, lists for japanese learning material    
        $knowRadicalsList = new \App\CustomList;
        $knowRadicalsList->type = 1;
        $knowRadicalsList->title = "known_radicals_list";
        $knowRadicalsList->save();

        $knowKanjisList = new \App\CustomList;
        $knowKanjisList->type = 2;
        $knowKanjisList->title = "known_kanjis_list";
        $knowKanjisList->save();

        $knowWordsList = new \App\CustomList;
        $knowWordsList->type = 3;
        $knowWordsList->title = "known_words_list";
        $knowWordsList->save();

        $knowSentencesList = new \App\CustomList;
        $knowSentencesList->type = 4;
        $knowSentencesList->title = "known_sentences_list";
        $knowSentencesList->save();

        // Custom, Lists for grouping any content.
        $customRadicalsList = new \App\CustomList;
        $customRadicalsList->type = 5;
        $customRadicalsList->title = "custom_radicals_list_title";
        $customRadicalsList->save();

        $customKanjisList = new \App\CustomList;
        $customKanjisList->type = 6;
        $customKanjisList->title = "custom_kanjis_list_title";
        $customKanjisList->save();

        $customWordsList = new \App\CustomList;
        $customWordsList->type = 7;
        $customWordsList->title = "custom_words_list_title";
        $customWordsList->save();

        $customSentencesList = new \App\CustomList;
        $customSentencesList->type = 8;
        $customSentencesList->title = "custom_sentences_list_title";
        $customSentencesList->save();

        $customArticlesList = new \App\CustomList;
        $customArticlesList->type = 9;
        $customArticlesList->title = "custom_articles_list_title";
        $customArticlesList->save();

        $customLyricsList = new \App\CustomList;
        $customLyricsList->type = 10;
        $customLyricsList->title = "custom_lyrics_list_title";
        $customLyricsList->save();

        $customArtistsList = new \App\CustomList;
        $customArtistsList->type = 11;
        $customArtistsList->title = "custom_artists_list_title";
        $customArtistsList->save();
    }
}
