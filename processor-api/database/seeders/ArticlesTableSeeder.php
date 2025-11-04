<?php

namespace Database\Seeders;

use App\Http\Models\Article;
use App\Http\Models\ObjectTemplate;
use App\Http\Models\View;
use Illuminate\Database\Seeder;

class ArticlesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // TODO: Create factories for value objects
        $articlesArray = [
            [
                'title_jp' => 'エアコンは窓を開けたときも消さないほうが電気を使わない',
                'title_en' => '',
                'content_jp' => '家庭のエアコンは、外の空気を部屋に入れたり、部屋の空気を外に出したりすることができません。新しいコロナウイルスがうつらないようにするためには、エアコンをつけているときも、時々窓を開けたりしなければなりません。

                エアコンを作る会社の団体は「外の空気を入れるときでもエアコンを消さないほうが、電気を使いません」と言っています。エアコンはつけるときにいちばん電気を使うためです。

                そして、電気を使いすぎないように、「エアコンの温度はいつも２７℃から２８℃にしておくこと」や「家に帰ったら、窓を開けて部屋の暖かい空気を外に出してから、エアコンをつけること」も大事だと言っています。',
                'content_en' => '',
                'source_link' => 'https://www3.nhk.or.jp/news/easy/k10012478041000/k10012478041000.html',
                'publicity' => 1,
                'status' => 3,
                'tags' => '#tag1 #tag2 #electricity',
                'user_id' => 2,
            ],
            [
                'title_jp' => '「日食」で太陽の一部が見えなくなる',
                'title_en' => '',
                'content_jp' => '「日食」は、太陽と月と地球がまっすぐ並んで、地球から見ると、月で太陽が見えなくなることです。日本では去年１２月に日食がありました。

                ２１日の午後、太陽の一部が見えなくなって暗くなる日食があって、日本中のいろいろな場所で見ることができました。関東地方や九州地方などの雲が多かった場所では見ることができませんでした。沖縄県那覇市では太陽の７９％が暗くなりました。北海道札幌市では１７％が暗くなりました。

                次に日本で日食を見ることができるのは２０２３年４月で、九州地方の南の場所だけになりそうです。',
                'content_en' => '',
                'source_link' => 'https://www3.nhk.or.jp/news/easy/k10012478981000/k10012478981000.html',
                'publicity' => 1,
                'status' => 3,
                'tags' => '#tag1 #tag2 #moon',
                'user_id' => 2,
            ],
            [
                'title_jp' => '中華街で獅子舞　新しいコロナウイルスがなくなるように祈る',
                'title_en' => '',
                'content_jp' => '「中華街」には中国の店がたくさんあって、大勢の人が遊びに来ます。しかし、新しいコロナウイルスの問題で、町に来る人がとても少なくなりました。

                神戸と横浜の中華街は２１日、ウイルスがなくなって町がまたにぎやかになることを祈って、中国の「獅子舞」を行いました。

                神戸の中華街では、２ｍぐらいある赤い獅子と青い獅子が、太鼓の音と一緒に踊りました。町に来た人たちは、華やかな「獅子舞」の写真を撮っていました。家族で来た人は「久しぶりに家族と出かけることができました。子どもも楽しそうでした」と話していました。

                インターネットでは神戸と横浜の「獅子舞」を放送しました。',
                'content_en' => '',
                'source_link' => 'https://www3.nhk.or.jp/news/easy/k10012478931000/k10012478931000.html',
                'publicity' => 1,
                'status' => 3,
                'tags' => '#tag1 #tag2 #festival',
                'user_id' => 2,
            ],
            [
                'title_jp' => 'コロナウイルス　子どもの７５％が「ストレスがある',
                'title_en' => '',
                'content_jp' => '国立成育医療研究センターの研究グループは、新しいコロナウイルスの問題で学校が休みになったことなどについて、子どもたちに聞きました。４月から５月に、７歳から１７歳の子どもと親などにインターネットで質問して、８７００人が答えました。

                その結果、７５％の子どもがストレスがあると答えました。「コロナのことを考えると嫌な気持ちになる」とか「最近、気持ちを集中することができない」などと言っています。

                ３１％の子どもが、１日に４時間以上、ゲームやスマートフォン、テレビなどを見るようになったと答えました。６１％の子どもが、朝起きる時間がいつもと変わったと答えました。

                研究グループは「ストレスは長く続くかもしれません。子どもたちの様子に気をつける必要があります」と言っています。',
                'content_en' => '',
                'source_link' => 'https://www3.nhk.or.jp/news/easy/k10012480101000/k10012480101000.html',
                'publicity' => 1,
                'status' => 3,
                'tags' => '#stress #children #coronavirus',
                'user_id' => 1,
            ],
            [
                'title_jp' => 'コロナウイルスのアプリＣＯＣＯＡ　３７１万件ダウンロード',
                'title_en' => '',
                'content_jp' => '「ＣＯＣＯＡ」は、新しいコロナウイルスがうつった人の近くにいた人に連絡するアプリです。国が１９日から利用できるようにしました。

                このアプリを使っている人が１ｍ以内に１５分以上いると、両方の人のスマートフォンにデータが残ります。ウイルスがうつったことがわかった人が、保健所から教えてもらった番号をアプリに入れると、近くにいたことがある人に連絡がいきます。

                厚生労働省によると、このアプリは２３日午前９時までに３７１万件ダウンロードされました。２３日、システムの一部に問題があることがわかったため、システムを直しています。

                加藤大臣は「このアプリは利用する人が多いほうが役に立ちます。プライバシーの問題がないように気をつけているので、多くの人に利用してもらいたいです」と話しました。',
                'content_en' => '',
                'source_link' => 'https://www3.nhk.or.jp/news/easy/k10012479501000/k10012479501000.html',
                'publicity' => 1,
                'status' => 3,
                'tags' => '#mobileapp #cocoa #coronavirus',
                'user_id' => 1,
            ],
            [
                'title_jp' => '日本のスーパーコンピューターが世界で１番になる',
                'title_en' => '',
                'content_jp' => '世界の専門家の会議は、６か月に１回、スーパーコンピューターの世界ランキングを発表しています。

                ２２日の発表によると、理化学研究所と富士通が作ったスーパーコンピューターの「富岳」が、４つの部門で１番になりました。

                「富岳」は１秒の間に１兆の４０万倍以上計算ができて、計算の速さの部門で１番でした。理化学研究所が作ったスーパーコンピューターの「京」も９年前に計算の速さで１番になりました。しかし、それから日本のスーパーコンピューターは計算の速さで１番になっていませんでした。

                「富岳」は、シミュレーションや、人工知能、ビッグデータの部門でも１番になりました。

                理化学研究所は「大事な部門で１番になることができました。富岳を使って、いろいろな社会の問題を解決することができると思います」と言っています。',
                'content_en' => '',
                'source_link' => 'https://www3.nhk.or.jp/news/easy/k10012480091000/k10012480091000.html',
                'publicity' => 1,
                'status' => 3,
                'tags' => '#japan #supercomputer #technologies',
                'user_id' => 1,
            ],
        ];

        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

        for ($i = 0; $i < count($articlesArray); $i++) {
            $article = new Article;
            $article->title_jp = $articlesArray[$i]['title_jp'];
            if (isset($articlesArray[$i]['title_en'])) {
                $article->title_en = $articlesArray[$i]['title_en'];
            }
            if (isset($articlesArray[$i]['content_en'])) {
                $article->content_en = $articlesArray[$i]['content_en'];
            }
            $article->content_jp = $articlesArray[$i]['content_jp'];
            $article->source_link = $articlesArray[$i]['source_link'];
            $article->user_id = $articlesArray[$i]['user_id'];
            $article->publicity = $articlesArray[$i]['publicity'];
            $article->status = $articlesArray[$i]['status'];
            $article->save();

            attachHashTags($articlesArray[$i]['tags'], $article, $objectTemplateId);

            $kanjiResponse = getKanjiIdsFromText($article);
            // $wordResponse  = getWordIdsFromText($article);

            $kanjis = $article->kanjis()->get();
            foreach ($kanjis as $kanji) {
                if ($kanji->jlpt == '1') {
                    $article->n1 = intval($article->n1) + 1;
                } elseif ($kanji->jlpt == '2') {
                    $article->n2 = intval($article->n2) + 1;
                } elseif ($kanji->jlpt == '3') {
                    $article->n3 = intval($article->n3) + 1;
                } elseif ($kanji->jlpt == '4') {
                    $article->n4 = intval($article->n4) + 1;
                } elseif ($kanji->jlpt == '5') {
                    $article->n5 = intval($article->n5) + 1;
                } else {
                    $article->uncommon = intval($article->uncommon) + 1;
                }
            }

            $article->update();

            // cant create helper of incrementView, the original func requires auth() method. For now, static provided
            $view = new View;
            $view->user_id = $article->user_id;
            $view->user_ip = '127.0.0.1';
            $view->template_id = $objectTemplateId;
            $view->real_object_id = $article->id;
            $view->save();
        }
    }
}
