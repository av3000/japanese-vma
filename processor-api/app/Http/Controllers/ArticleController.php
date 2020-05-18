<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\Article;
use App\Kanji;
use App\Word;
use App\Like;
use App\ObjectTemplate;
use App\Http\Requests\ArticleStoreRequest;
use PDF;

class ArticleController extends Controller
{
    public function __constructor(){
        // Do the middleware stuff
        //  or add only to specific routes "auth()->user()" 
        // or something like that.
        //  FOR NOW AUTH:API ROUTES ARE DOING IT
    }

    public function index() {
        $articles = Article::all();

        # Need to Test
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        foreach($articles as $singleArticle)
        {
            $singleArticle->likes = Like::where([
                'template_id' => $objectTemplateId,
                'real_object_id' => $singleArticle->id
            ])->get();
            $singleArticle->likesTotal = count($singleArticle->likes);
        }

        if(isset($articles))
         {
            return response()->json([
                'success' => true, 'articles' => $articles
             ]);
         }
         return response()->json([
            'success' => false, 'message' => 'There are no articles...'
         ]);
    }

    public function unlikeArticle($id) {
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $like = Like::where([
            'template_id' => $objectTemplateId,
            'real_object_id' => $id,
            'user_id' => auth()->user()->id
        ]);

        $like->delete();

        return response()->json([
            'success' => true,
            'message' => "like was deleted",
        ]);
    }

    public function likeArticle($id) {
        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

        $checkLike = Like::where([
            'template_id' => $objectTemplateId,
            'real_object_id' => $id,
            'user_id' => auth()->user()->id
        ])->first();
        
        if($checkLike) {
            return response()->json([
                'message' => 'you cannot like it twice!'
            ]);
        }
        
        $like = new Like;
        $like->user_id = auth()->user()->id;
        $like->template_id = $objectTemplateId;
        $like->real_object_id = $id;
        $like->value=1;
        $like->save();

        return response()->json([
            'success' => true, 'You liked article of id: '.$id, 'like' => $like
        ]);
    }

    public function show($id) {
        $article = Article::find($id);
        if(!isset($article)){
            return response()->json([
                'success' => false, 'message' => 'Requested article does not exist'
            ]);
        }

        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $article->likes = Like::where([
            'template_id' => $objectTemplateId,
            'real_object_id' => $article->id
        ])->get();
        $article->likesTotal = count($article->likes);

        $article->jlpt1 = 0;
        $article->jlpt2 = 0;
        $article->jlpt3 = 0;
        $article->jlpt4 = 0;
        $article->jlpt5 = 0;
        $article->jlptcommon = 0;

        $article->words = $article->words()->get();
        $article->kanjis = $article->kanjis()->get();
        foreach($article->kanjis as $kanji){
            if($kanji->jlpt == "1")      { $article->jlpt1++; }
            else if($kanji->jlpt == "2") { $article->jlpt2++; }
            else if($kanji->jlpt == "3") { $article->jlpt3++; }
            else if($kanji->jlpt == "4") { $article->jlpt4++; }
            else if($kanji->jlpt == "5") { $article->jlpt5++; }
            else { $article->jlptcommon++; }
        }
        $article->kanjiTotal = $article->jlpt1 + $article->jlpt2 + $article->jlpt3 + $article->jlpt4 + $article->jlpt5 + $article->jlptcommon;

        $user = User::find($article->user_id);
        $article->userName = $user->name;
        $article->userId = $user->id;

        #           Method 1:
        // Furigana battle. Display furigana above text functionality
        // $article->wordsWithFurigana = [
        //     array($recognizedWordFromContentJp, $wordFromArticleWords)
        //     array($recognizedWordFromContentJp, $wordFromArticleWords)
        //     array($recognizedWordFromContentJp, $wordFromArticleWords)
        //     ...
        // ]
        #  Seems like we will need to save straight after WordsExtracting
        # into DB table "articles.content_furi"
        #           Method 2:
        # display ONLY furigana, without trying to find each of content_jp word place in text.

        return response()->json([
            'success' => true,
            'article' => $article
        ]);
    }

    public function getUserArticles($id) {
        $articles = Article::where("user_id", $id)->get();

        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $article->likes = Like::where([
            'template_id' => $objectTemplateId,
            'real_object_id' => $article->id
        ])->get();
        $article->likesTotal = count($article->likes);
        # Need to Test
        //  foreach($articles as $singleArticle)
        //  {
        //      $singleArticle->kanjis = $this->articleKanjis($singleArticle->id);
        //      $singleArticle->words = $this->articleWords($singleArticle->id);
        //  }

         if(isset($articles))
         {
            return response()->json([
                'success' => true, 'articles' => $articles
             ]);
         }
         return response()->json([
            'success' => false, 'message' => 'Requested Article does not exist or User has no articles'
         ]);
    }

    public function articleKanjis($id){
        $articleKanjis = Article::find($id)->kanjis()->get();

        if(isset($articleKanjis))
         {
            return response()->json([
                'success' => true, 'articleKanjis' => $articleKanjis
             ]);
         }
         return response()->json([
            'success' => false, 'message' => 'Requested Article does not have kanjis'
         ]);
    }

    public function articleWords($id){
        $articleWords = Article::find($id)->words()->get();

        if(isset($articleWords))
         {
            return response()->json([
                'success' => true, 'articleWords' => $articleWords
             ]);
         }
         return response()->json([
            'success' => false, 'message' => 'Requested Article does not have words'
         ]);
    }

    public function generateQuery(Request $request) { // Not sure about encoding part if it work.
        $q = $request->get("q");
        $results = Article::where("title_en", "like", "%".$q."%")
                        ->orWhere("title_jp", "like", "%".$q."%")->get();
        if(!isset($results)) {
            return response()->json([
                'success' => false, 'message' => 'Requested query: '.$q. ' returned zero results'
             ]);
        }
        return response()->json([
            'success' => true,
            'message' => 'Requested query: '.$q. ' returned: '.count($results).' results',
            'results' => $results
        ]);
    }

    public function store(ArticleStoreRequest $request) {
        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }   

        $validated = $request->validated();

        $article = new Article;
        $article->user_id = auth()->user()->id;
        $article->title_jp = $validated['title_jp'];
        if(isset($validated['title_en']))
        {
            $article->title_en = $validated['title_en'];
        } else {
            $article->title_en = "";
        }
        if(isset($validated['content_en']))
        {
            $article->content_en = $validated['content_en'];
        } else {
            $article->content_en = "";
        }
        $article->content_jp = $validated['content_jp'];
        $article->source_link = $validated['source_link'];
        $article->status = $validated['status'];
        $article->save();

        $kanjiResponse = $this->getKanjiIdsFromText($article);
        $wordResponse  = $this->getWordIdsFromText($article);

        return response()->json([
            'success' => true,
            'article' => $article,
            'kanjis' => $kanjiResponse,
            'words' => $wordResponse
        ]);
    }

    public function update(Request $request, $id) {
        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }   
        $article = Article::find($id);
        $article->title_jp = $request->title_jp;
        if(isset($request->title_en))
        {
            $article->title_en = $request->title_en;
        } else {
            $article->title_en = "";
        }
        if(isset($request->content_en))
        {
            $article->content_en = $request->content_en;
        } else {
            $article->content_en = "";
        }
        $article->content_jp = $request->content_jp;
        $article->source_link = $request->source_link;
        $article->status = $request->status;
        $article->save();

        $article->kanjis()->wherePivot('article_id', $article->id)->detach();
        $article->words()->wherePivot('article_id', $article->id)->detach();
        
        $kanjiResponse = $this->getKanjiIdsFromText($article);
        $wordResponse  = $this->getWordIdsFromText($article);

        return response()->json([
            'success' => true,
            'updated_article' => $article,
            'reattached_kanjis' => $kanjiResponse,
            'retattached_words' => $wordResponse
        ]);
    }

    public function delete(Request $request, $id) {
        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }   
        $article = article::find($id);

        $article->kanjis()->wherePivot('article_id', $article->id)->detach();
        $article->words()->wherePivot('article_id', $article->id)->detach();

        $article->delete();

        return response()->json([
            'success' => true,
            'deleted_article' => $article,
        ]);
    }

    #====================================================== Japanese Text Handling

    // Optional. 
    // public function addJltpsToArticle(Article $article){
    //     return $article;
    // }

    /**
     * Return true if string found in string array
     * @param string, @param array 
     * @return boolean
     */
    public function containsWord(string $str, array $arr) {
         foreach($arr as $a)
         {
             if ($str == $a) return true;
         }
         return false;
     }

    /**
     * Return string as japanese char array
     * @param  string $string
     * @return charArray
     */
    public function mb_str_split(string $string, $split_length = 1) {
        if ($split_length == 1)
        {
            return preg_split("//u", $string, -1, PREG_SPLIT_NO_EMPTY);
        }
        elseif ($split_length > 1)
        {
            $return_value = [];
            $string_length = mb_strlen($string, "UTF-8");
            for ($i = 0; $i < $string_length; $i += $split_length)
            {
                $return_value[] = mb_substr($string, $i, $split_length, "UTF-8");
            }
            return $return_value;
        }
        else
        {
            return false;
        }
    }

     /**
     * @param object Article
     */
    public function getKanjiIdsFromText(Article $article) {
        $raw_text    = $this->mb_str_split($article->title_jp . $article->content_jp);
        $total       = sizeof($raw_text);
        $foundKanjis = [];
        $index       = 0;
        while($index < $total) {
            $kanji = Kanji::where( 'kanji', 'like', $raw_text[$index])->first();
                if(isset($kanji) && !in_array($kanji->id, $foundKanjis, TRUE) ){ 
                    array_push($foundKanjis, $kanji->id);
                    // echo "<p>kanji found: " .$kanji->kanji. "</p>";
                    $article->kanjis()->attach($kanji);
                }
            $index++;
        }

        if( count($foundKanjis)  == 0) {
            return response()->json(['success' => false, 'kanji_message'=> 'There was no kanji characters in article text...']);
        }
        return response()->json(['success' => true, 'kanji_message'=> 'Kanji characters were attached to the article!']);
    }

    /**
     * @param object Article
     */
    public function getWordIdsFromText(Article $article) {
        $testString     = $article->title_jp . $article->content_jp;
        $testString     = str_replace(array("\n", "\r", " "), "", $testString);
        $fullText       = $this->mb_str_split($testString);
        $duplicateArray = [];
        $len            = sizeof($fullText);
        $cursorStart    = 0;
        $cursor         = 0;
        $tempWord       = "";
        $refreshStop    = 0;
        
        while( $cursorStart < $len ) {
            # Makes sure if there is more text to process.
            if( isset( $fullText[$cursor] ) ){
                $tempWord .= $fullText[$cursor];
                $potentialWords = Word::where('word', 'like', $tempWord.'%')->get();
            }
            if( isset( $fullText[$cursor+1] ) ){
                $tempWordNext = $tempWord . $fullText[$cursor+1];
                $potentialWordsNext = Word::where('word', 'like', $tempWordNext.'%')->get();
            }
            # Exception 1:
            # When $potentialWords have results, but $potentialWordsNext reached zero, it means that we can begin actual word recognition.
            if( count( $potentialWords ) >= 1 && count( $potentialWordsNext ) == 0 ){
                $matchOk=0;
                $moveBack=0;
                $potentialLost=true;
                while( $matchOk == 0 ) {
                        # keep going back one char at the time until our EQUAL query will find the word.
                        # or the potentialWords will be wasted.
                        $fetchWordAtTheTime = Word::where('word', $tempWord)->first();
                        if( isset( $fetchWordAtTheTime ) ){
                            if( in_array( $fetchWordAtTheTime->word, $duplicateArray ) == false ){
                                array_push( $duplicateArray, $fetchWordAtTheTime->word );
                                $article->words()->attach($fetchWordAtTheTime);
                            }
                            $tempWord = "";
                            $matchOk=1;
                            break;
                        }
                        else if ($tempWord == ""){
                            $matchOk=1;
                            $potentialLost=false;
                            break;
                        }
                        $moveBack++;
                        $tempWord = mb_substr($tempWord, 0, mb_strlen($tempWord)-1, 'utf-8');
                }
                # need to check if cursor moved back and minus additional steps so that some text wouldn't be lost.
                if( $potentialLost == false ){ $cursorStart = $cursor+1; } 
                else {
                    $cursorStart = $cursor+1 - $moveBack;
                    $cursor -= $moveBack;
                }
            }
            # Exception 2:
            # Case, when some unwanted symbols get in the way
            # To get rid of it, we refresh $tempWord to empty, without that current unwanted symbol.
            else if( count( $potentialWords ) == 0 && count( $potentialWordsNext ) == 0  ){
                $cursorStart = $cursor+1;
                $tempWord="";
            }
            #Exception 3:
            # Rare Case, when we still have lost of LIKE potential, but the cursor hits the wall.
            # Need to force the EQUAL querying with minus 1char at the time.
            else if(  count( $potentialWords ) >= 1 && count( $potentialWordsNext ) >= 1 && $cursor >= $len - 1 ){
                $refreshStop=1;
                $matchOk=0;
                $moveBack=0;
                $potentialLost=true;
                while( $matchOk == 0 ) {
                        # keep going back one char at the time until our EQUAL query will find the word.
                        # or the potentialWords will be wasted.
                        $fetchWordAtTheTime = Word::where('word', $tempWordNext)->first();
                        if( isset( $fetchWordAtTheTime ) ){
                            if( in_array( $fetchWordAtTheTime->word, $duplicateArray ) == false ){
                                array_push( $duplicateArray, $fetchWordAtTheTime->word );
                                $article->words()->attach($fetchWordAtTheTime);
                            }
                            $tempWord = "";
                            $matchOk=1;
                            break;
                        }
                        else if ($tempWordNext == ""){
                            $tempWord = "";
                            $matchOk=1;
                            $potentialLost=false;
                            break;
                        }
                        $moveBack++;
                        $tempWordNext = mb_substr($tempWordNext, 0, mb_strlen($tempWordNext)-1, 'utf-8');
                }
                 # need to check if cursor moved back and minus additional steps so that some text wouldn't be lost.
                 if( $potentialLost == false ){ $cursorStart = $cursor+1; } 
                 else {
                     $cursorStart = $cursor+1 - $moveBack;
                     $cursor -= $moveBack;
                 }
            }
            # Exception 4: 
            # if somehow cursor hits the end of the text, we need to reset and increment the cursors
            if( $cursor >= $len-1 && $refreshStop == 0) {
                $cursorStart++;
                $cursor=$cursorStart;
                $tempWord="";
            }
            # index will increase and won't be modified in the first IF statement
            # if none of the exceptions has been entered
            # So, which means that $cursorStart - is still beginning of the word
            # and $cursor is the ending of the word and it takes +1 step to the upcoming word.
            $cursor++;
        }

        if( count($duplicateArray) == 0) 
        {
            return response()->json(['success' => false, 'word_message'=> 'There was no words found in the article text...'
            ]);
        }
        return response()->json(['success' => true, 'word_message'=> 'Words were attached to the article!']);
    }

    /**
     * @param object Article TODO
     *
     */
    public function getWordsFuriganaFromText(Article $article){ return response()->json([ 'message' => 'getWordsFuriganaFromText. Im still empty tho.']); }

    public function generateWordsPdf($id) 
	{
        if( !auth()->user() ){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }

        $article = Article::find($id);

        if( !$article )
        {
            return response()->json([
                'success' => false,
                'message' => 'requested article does not exist'
            ]);
        }
        $user = User::find($article->user_id);
        $wordList = $article->words()->get();

        $differentTags = [];
        foreach($wordList as $word){
            $posArr=[];
            $miscArr=[];
            $glossArr=[];
            $fieldArr=[];

            foreach(json_decode($word->sense) as $singleSense)
            {
                // if(count($singleSense) > $maxCount) { $maxCount = count($singleSense); }
                $pos="";
                $misc="";
                $gloss="";
                $field="";
                
                // echo "<h2> singleSense </h2>";
                // echo "<pre>";
                // print_r($singleSense);
                // echo "</pre>";
                foreach($singleSense as $singleTag)
                {
                    // echo "<h3> singleTag </h3>";
                    // echo "<pre>";
                    // print_r($singleTag);
                    // echo "</pre>";
                    if( !in_array($singleTag[0], $differentTags) ) { array_push($differentTags, $singleTag[0]); }
                    if( isset( $singleTag[0] ) )
                    {
                        // echo "<p>TagType: " .$singleTag[0]. "</p>";
                        # Exceptions for empty or wrong values
                        if( strcmp( $singleTag[0], "lsource" ) == 0 ) { continue; }
                        # stdClass conversion to get string
                        if( isset($singleTag[1]) && !is_string($singleTag[1]) )
                        {
                            $itemAsArr = json_decode(json_encode($singleTag[1]), true);
                            // echo "<p>STR TagValue: " .$itemAsArr[0]. "</p>";
                            # TagType assigning
                            if( strcmp( $singleTag[0], "gloss" ) == 0) 
                            {
                                $gloss .= $itemAsArr[0] . "|";
                            }
                            // else if( strcmp( $singleTag[0], "pos" ) == 0) 
                            // {
                            //     $pos .= $itemAsArr[0] . "|";
                            // }
                            // else if( strcmp( $singleTag[0], "misc" ) == 0) 
                            // {
                            //     $misc .= $itemAsArr[0] . "|";
                            // }
                            // else if( strcmp( $singleTag[0], "field" ) == 0) 
                            // {
                            //     $field .= $itemAsArr[0] . "|";
                            // }
                        }
                    }
                }
                // echo "<h4> Assigning values: </h4>";
                // echo "<p>pos: " .$pos. "</p>";
                // echo "<p>misc: " .$misc. "</p>";
                // echo "<p>gloss: " .$gloss. "</p>";
                // echo "<p>field: " .$field. "</p>";
                
                array_push($posArr, $pos);
                array_push($miscArr, $misc);
                array_push($glossArr, $gloss);
                array_push($fieldArr, $field);
                
            }
            $word->pos = $posArr;
            $word->gloss = $glossArr;
            $word->misc = $miscArr;
            $word->field = $fieldArr;
        }

        foreach($wordList as $word) {
            $word->meaning = implode(", ", array_slice(explode("|", $word->gloss[0]), 0, 3));
        }

        $data = [
            'article_id' => $article->id,
            'title_jp' => $article->title_jp,
            'title_en' => $article->title_en,
            'content_jp' => $article->content_jp,
            'content_en' => $article->content_en,
            'author' => $user->name,
            'user_id' => $user->id,
            'date' => $article->created_at,
            'source_link' => $article->source_link,
            'wordList' => $wordList
        ];
        // return $data;
        // return view("pdf.words.article-pdf", $data);
        $pdf = PDF::loadView("pdf.kanjis.article-words", $data);
        $pdf->setOptions([
            // 'footer-html' => view('pdf.words._footer')
            'footer-center' => '[page]',
            // 'header-left' => 'header-left',
            // 'header-right' => 'header-right',
            'page-size'=> 'a4'
        ]);
        
        // https://wkhtmltopdf.org/usage/wkhtmltopdf.txt
        return $pdf->stream("article-words.pdf");
    }
    
    public function generateKanjisPdf($id) 
	{
        if( !auth()->user() ){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }

        $article = Article::find($id);

        if( !$article )
        {
            return response()->json([
                'success' => false,
                'message' => 'requested article does not exist'
            ]);
        }
        $user = User::find($article->user_id);
        $kanjiList = $article->kanjis()->get();

        foreach($kanjiList as $kanji) {
            $kanji->onyomi = implode(", ", array_slice(explode("|", $kanji->onyomi), 0, 3));
            $kanji->kunyomi = implode(", ", array_slice(explode("|", $kanji->kunyomi), 0, 3));
            $kanji->meaning = implode(", ", array_slice(explode("|", $kanji->meaning), 0, 3));
        }

        $data = [
            'article_id' => $article->id,
            'title_jp' => $article->title_jp,
            'title_en' => $article->title_en,
            'content_jp' => $article->content_jp,
            'content_en' => $article->content_en,
            'author' => $user->name,
            'user_id' => $user->id,
            'date' => $article->created_at,
            'source_link' => $article->source_link,
            'kanjiList' => $kanjiList
        ];
        // return $data;
        // return view("pdf.kanjis.article-pdf", $data);
        $pdf = PDF::loadView("pdf.kanjis.article-kanjis", $data);
        $pdf->setOptions([
            // 'footer-html' => view('pdf.kanjis._footer')
            'footer-center' => '[page]',
            // 'header-left' => 'header-left',
            // 'header-right' => 'header-right',
            'page-size'=> 'a4'
        ]);
        
        // https://wkhtmltopdf.org/usage/wkhtmltopdf.txt
        return $pdf->stream("article-kanjis.pdf");
	}
}
