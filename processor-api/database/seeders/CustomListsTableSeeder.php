<?php

namespace Database\Seeders;

use App\Http\Models\CustomList;
use App\Http\Models\Kanji;
use App\Http\Models\ObjectTemplate;
use App\Http\Models\Radical;
use App\Http\Models\Sentence;
use App\Http\Models\View;
use App\Http\Models\Word;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
        // $knowRadicalsList    = 1;
        // $knowKanjisList      = 2;
        // $knowWordsList       = 3;
        // $knowSentencesList   = 4;
        // $customRadicalsList  = 5;
        // $customKanjisList    = 6;
        // $customWordsList     = 7;
        // $customSentencesList = 8;
        // $customArticlesList  = 9;

        // Seeds
        // Known, lists for japanese learning material
        // $knowRadicalsList = new CustomList;
        // $knowRadicalsList->type = 1;
        // $knowRadicalsList->user_id = 1;
        // $knowRadicalsList->title = "Known Radicals";
        // $knowRadicalsList->save();

        // $knowKanjisList = new CustomList;
        // $knowKanjisList->type = 2;
        // $knowKanjisList->user_id = 1;
        // $knowKanjisList->title = "Known Kanjis";
        // $knowKanjisList->save();

        // $knowWordsList = new CustomList;
        // $knowWordsList->type = 3;
        // $knowWordsList->user_id = 1;
        // $knowWordsList->title = "Known Words";
        // $knowWordsList->save();

        // $knowSentencesList = new CustomList;
        // $knowSentencesList->type = 4;
        // $knowSentencesList->user_id = 1;
        // $knowSentencesList->title = "Known Sentences";
        // $knowSentencesList->save();

        // // Custom, Lists for grouping any content.
        // $customRadicalsList = new CustomList;
        // $customRadicalsList->type = 5;
        // $customRadicalsList->user_id = 1;
        // $customRadicalsList->title = "My Custom Radicals";
        // $customRadicalsList->save();

        // $customKanjisList = new CustomList;
        // $customKanjisList->type = 6;
        // $customKanjisList->user_id = 1;
        // $customKanjisList->title = "My Custom Kanjis";
        // $customKanjisList->save();

        // $customWordsList = new CustomList;
        // $customWordsList->type = 7;
        // $customWordsList->user_id = 1;
        // $customWordsList->title = "My Custom Words";
        // $customWordsList->save();

        // $customSentencesList = new CustomList;
        // $customSentencesList->type = 8;
        // $customSentencesList->user_id = 1;
        // $customSentencesList->title = "My Custom Sentences";
        // $customSentencesList->save();

        // $customArticlesList = new CustomList;
        // $customArticlesList->type = 9;
        // $customArticlesList->user_id = 1;
        // $customArticlesList->title = "My Custom Articles";
        // $customArticlesList->save();

        // testuser Lists

        $listsArray = [
            [
                'title' => 'Popular Radicals',
                'description' => 'Stored my Popular radicals from the latest period of time.',
                'publicity' => 1,
                'type' => 5,
                'tags' => '#tag1 #tag2 #Popularradicals',
                'user_id' => 2,
                'listItemsAmount' => rand(10, 30),
            ],
            [
                'title' => 'Popular Kanjis',
                'description' => 'Stored my Popular Kanjis from the latest period of time.',
                'publicity' => 1,
                'type' => 6,
                'tags' => '#tag1 #tag2 #Popularkanjis',
                'user_id' => 2,
                'listItemsAmount' => rand(10, 30),
            ],
            [
                'title' => 'Popular Words',
                'description' => 'Stored my Popular Words from the latest period of time.',
                'publicity' => 1,
                'type' => 7,
                'tags' => '#tag1 #tag2 #Popularwords',
                'user_id' => 2,
                'listItemsAmount' => rand(10, 30),
            ],
            [
                'title' => 'Popular Sentences',
                'description' => 'Stored my Popular Sentences from the latest period of time.',
                'publicity' => 1,
                'type' => 8,
                'tags' => '#tag1 #tag2 #Popularsentences',
                'user_id' => 2,
                'listItemsAmount' => rand(10, 30),
            ],
            // [
            //     'title' => "Popular Articles",
            //     'description' => "Stored my Popular Articles from the latest period of time.",
            //     'publicity' => 1,
            //     'type' => 9,
            //     'status' => 3,
            //     'tags' => "#tag1 #tag2 #favoritearticles",
            //     'user_id' => 2,
            //     'listItemsAmount' => rand(1, 10)
            // ],
        ];

        $objectTemplateId = ObjectTemplate::where('title', 'list')->first()->id;

        for ($i = 0; $i < count($listsArray); $i++) {
            $list = new CustomList;
            $list->title = $listsArray[$i]['title'];
            $list->description = $listsArray[$i]['description'];
            $list->user_id = $listsArray[$i]['user_id'];
            $list->publicity = $listsArray[$i]['publicity'];
            $list->type = $listsArray[$i]['type'];
            $list->save();

            if ($list->type === 5) {
                for ($j = 0; $j < $listsArray[$i]['listItemsAmount']; $j++) {
                    $row = [
                        'real_object_id' => Radical::find(rand(1, 214))->id,
                        'listtype_id' => $list->type,
                        'list_id' => $list->id,
                    ];

                    $x = DB::table('customlist_object')->insert($row);
                }
            } elseif ($list->type === 6) {
                for ($j = 0; $j < $listsArray[$i]['listItemsAmount']; $j++) {
                    $row = [
                        'real_object_id' => Kanji::find(rand(1, 13108))->id,
                        'listtype_id' => $list->type,
                        'list_id' => $list->id,
                    ];

                    $x = DB::table('customlist_object')->insert($row);
                }
            } elseif ($list->type === 7) {
                for ($j = 0; $j < $listsArray[$i]['listItemsAmount']; $j++) {
                    $row = [
                        'real_object_id' => Word::find(rand(1, 184938))->id,
                        'listtype_id' => $list->type,
                        'list_id' => $list->id,
                    ];

                    $x = DB::table('customlist_object')->insert($row);
                }
            } elseif ($list->type === 8) {
                for ($j = 0; $j < $listsArray[$i]['listItemsAmount']; $j++) {
                    $row = [
                        'real_object_id' => Sentence::find(rand(1, 192145))->id,
                        'listtype_id' => $list->type,
                        'list_id' => $list->id,
                    ];

                    $x = DB::table('customlist_object')->insert($row);
                }
            }

            $list->tags = attachHashTags($listsArray[$i]['tags'], $list, $objectTemplateId);

            // cant create helper of incrementView, the original func requires auth() method. For now, static provided
            $view = new View;
            $view->user_id = $list->user_id;
            $view->user_ip = '127.0.0.1';
            $view->template_id = $objectTemplateId;
            $view->real_object_id = $list->id;
            $view->save();
        }
    }
}
