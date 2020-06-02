<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Article;
use App\Kanji;
use App\Word;
use App\Like;
use App\Download;
use App\View;
use App\Comment;
use App\ObjectTemplate;
use App\Uniquehashtag;
use App\Http\Requests\ArticleStoreRequest;
use PDF;
use DB;

class ArticleController extends Controller
{
    const KNOWNRADICALS = 1;
    const KNOWNKANJIS = 2;
    const KNOWNWORDS = 3;
    const KNOWNSENTENCES = 4;
    const RADICALS  = 5;
    const KANJIS    = 6;
    const WORDS     = 7;
    const SENTENCES = 8;
    const ARTICLES  = 9;
    const LYRICS    = 10;
    const ARTISTS   = 11;

    public function __constructor(){

    }

    public function index() {
        $articles = Article::where('publicity', 1)->orderBy('created_at', "DESC")->paginate(3);

        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $jp_month = "月";
        $jp_day = "日";
        $jp_hour = "時";
        $jp_minute = "分";
        $jp_year = "年";
        foreach($articles as $singleArticle)
        {
            $singleArticle->jp_year   = $singleArticle->created_at->year   . $jp_year;
            $singleArticle->jp_month  = $singleArticle->created_at->month  . $jp_month;
            $singleArticle->jp_day    = $singleArticle->created_at->day    . $jp_day;
            $singleArticle->jp_hour   = $singleArticle->created_at->hour   . $jp_hour;
            $singleArticle->jp_minute = $singleArticle->created_at->minute . $jp_minute;

            $singleArticle->likesTotal = $this->getImpression("like", $objectTemplateId, $singleArticle, "total");
            $singleArticle->downloadsTotal = $this->getImpression("download", $objectTemplateId, $singleArticle, "total");
            $singleArticle->viewsTotal = $this->getImpression("view", $objectTemplateId, $singleArticle, "total");
            $singleArticle->commentsTotal = $this->getImpression('comment', $objectTemplateId, $singleArticle, 'total');
            $singleArticle->hashtags      = array_slice($this->getUniquehashtags($singleArticle->id, $objectTemplateId), 0, 3);
        }

        if(isset($articles))
         {
            return response()->json([
                'success' => true, 'articles' => $articles, 'message'=> 'articles fetched'
             ]);
         }
         return response()->json([
            'success' => false, 'message' => 'There are no articles...'
         ]);
    }

    public function show($id) {
        $article = Article::find($id);

        if(!isset($article)){
            return response()->json([
                'success' => false, 'message' => 'Requested article does not exist'
            ]);
        }

        $jp_month = "月";
        $jp_day = "日";
        $jp_hour = "時";
        $jp_minute = "分";
        $jp_year = "年";

        $article->jp_year   = $article->created_at->year   . $jp_year;
        $article->jp_month  = $article->created_at->month  . $jp_month;
        $article->jp_day    = $article->created_at->day    . $jp_day;
        $article->jp_hour   = $article->created_at->hour   . $jp_hour;
        $article->jp_minute = $article->created_at->minute . $jp_minute;

        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $this->incrementView($article);

        $article->likes = $this->getImpression("like", $objectTemplateId, $article, "all");
        $article->likesTotal = count($article->likes);
        $article->downloadsTotal = $this->getImpression("download", $objectTemplateId, $article, "total");
        $article->viewsTotal = $this->getImpression("view", $objectTemplateId, $article, "total");
        $article->comments = $this->getImpression('comment', $objectTemplateId, $article, "all");
        $article->commentsTotal = count($article->comments);
        $article->hashtags      = $this->getUniquehashtags($article->id, $objectTemplateId);

        $objectTemplateId = ObjectTemplate::where('title', 'comment')->first()->id;
        foreach($article->comments as $comment)
        {
            $comment->likes = $this->getImpression('like', $objectTemplateId, $comment, "all");
            $comment->likesTotal = count($comment->likes);
            $comment->userName = User::find($comment->user_id)->name;
        }
        
        $article->jlptcommon = 0;

        $article->words = $this->extractWordsListAttributes($article->words()->get());
        $article->kanjis = $article->kanjis()->get();
        foreach($article->kanjis as $kanji){
            if($kanji->jlpt == "-") { $article->jlptcommon++; }
        }
        $article->kanjiTotal = intval($article->n1) + intval($article->n2) + intval($article->n3) + intval($article->n4) + intval($article->n5) + $article->jlptcommon;

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

    public function store(Request $request) 
    {
        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }
        // $validated = $request->validated();

        $validator = Validator::make($request->all(), [
            // 'title_en'    => 'max:255',
            'title_jp'    => 'required|min:2|max:255',
            // 'content_en'  => 'max:3000',
            'content_jp'  => 'required|min:2|max:3000',
            'source_link' => 'required'
        ]);

        if($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $article = new Article;
        $article->user_id = auth()->user()->id;
        $article->title_jp = $request->get('title_jp');
        if(isset( $request->title_en ))
        {
            $article->title_en = $request->get('title_en');
        } else {
            $article->title_en = "";
        }
        if(isset( $request->content_en ))
        {
            $article->content_en = $request->get('content_en');
        } else {
            $article->content_en = "";
        }
        if(isset( $request->publicity )) {
            $article->publicity = $request->get('publicity');
        }
        $article->content_jp = $request->get('content_jp');
        $article->source_link = $request->get('source_link');
        $article->save();

        $this->attachHashTags($request->tags, $article);

        if(isset($request->attach) && $request->attach == 1)
        {
            $kanjiResponse = $this->getKanjiIdsFromText($article);
            $wordResponse  = $this->getWordIdsFromText($article);
            $kanjis = $article->kanjis()->get();
            foreach($kanjis as $kanji){
                if     ($kanji->jlpt == "1") { $article->n1 = intval($article->n1) + 1; }
                else if($kanji->jlpt == "2") { $article->n2 = intval($article->n2) + 2; }
                else if($kanji->jlpt == "3") { $article->n3 = intval($article->n3) + 3; }
                else if($kanji->jlpt == "4") { $article->n4 = intval($article->n4) + 4; }
                else if($kanji->jlpt == "5") { $article->n5 = intval($article->n5) + 5; }
            }

            $article->update();

            return response()->json([
                'success' => true,
                'attach' => $request->attach,
                'article' => $article,
                'kanjis' => $kanjiResponse,
                'words' => $wordResponse
            ]);
        }

        return response()->json([
            'success' => true,
            'attach' => $request->attach,
            'article' => $article
        ]);
    }

    public function update(Request $request, $id) {
        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }   
        
        $article = Article::find($id);

        if( !$article || $article->user_id != auth()->user()->id ){
            return response()->json([
                'success' => false,
                'message' => 'article doesnt exist or does not belong to the user'
            ]);
        }   

        if(isset($request->title_jp))
        {
            $article->title_jp = $request->title_jp;
        }
        if(isset($request->title_en))
        {
            $article->title_en = $request->title_en;
        }
        if(isset($request->content_en))
        {
            $article->content_en = $request->content_en;
        } 
        if(isset($request->content_jp))
        {
            $article->content_jp = $request->content_jp;
        } 
        if(isset($request->source_link))
        {
            $article->source_link = $request->source_link;
        } 
        if(isset($request->status))
        {
            $article->status = $request->status;
        } 
        if(isset( $request->publicity )) {
            $article->publicity = $request->publicity;
        }

        $article->update();

        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

        if(isset($request->tags))
        {
            $this->removeHashtags($article->id, $objectTemplateId, $request->tags);
            $this->attachHashTags($request->tags, $article);
        }

        if( $request->reattach == 1)
        {
            // die("I should not have been here"); debugging
            $article->kanjis()->wherePivot('article_id', $article->id)->detach();
            $article->words()->wherePivot('article_id', $article->id)->detach();
        
            $kanjiResponse = $this->getKanjiIdsFromText($article);
            $wordResponse  = $this->getWordIdsFromText($article);

            return response()->json([
                'success' => true,
                'reattach' => $request->reattach,
                'updated_article' => $article,
                'reattached_kanjis' => $kanjiResponse,
                'reattached_words' => $wordResponse
            ]);
        }

        return response()->json([
            'success' => true,
            'reattach' => $request->reattach,
            'updated_article' => $article,
            'reattached_kanjis' => "none",
            'reattached_words' => "none"
        ]);
    }

    public function delete(Request $request, $id) {
        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }   
        $article = Article::find($id);

        if( !$article || $article->user_id != auth()->user()->id ){
            return response()->json([
                'success' => false,
                'message' => 'article doesnt exist or does not belong to the user'
            ]);
        }  

        $article->kanjis()->wherePivot('article_id', $article->id)->detach();
        $article->words()->wherePivot('article_id', $article->id)->detach();
        $this->removeImpressions($article);

        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $this->removeHashtags($article->id, $objectTemplateId);
        $this->removeArticleFromLists($article->id);

        $article->delete();

        return response()->json([
            'success' => true,
            'deleted_article' => $article,
        ]);
    }

    public function removeArticleFromLists($id)
    {
        DB::table('customlist_object')
            ->where('real_object_id', $id)
            ->where('listtype_id', self::ARTICLES)
            ->delete();
    }

    public function getUserArticles($id) {
        $articles = Article::where("user_id", $id)->get();

        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

        foreach($articles as $singleArticle)
        {
            $singleArticle->likesTotal = $this->getImpression("like", $objectTemplateId, $singleArticle, "total");
            $singleArticle->downloadsTotal = $this->getImpression("download", $objectTemplateId, $singleArticle, "total");
            $singleArticle->viewsTotal = $this->getImpression("view", $objectTemplateId, $singleArticle, "total");
            $singleArticle->commentsTotal = $this->getImpression('comment', $objectTemplateId, $singleArticle, 'total');
            // hashtags
        }

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

    public function getArticleImpressionsSearch($articles)
    {
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $jp_month = "月";
        $jp_day = "日";
        $jp_hour = "時";
        $jp_minute = "分";
        $jp_year = "年";
        foreach($articles as $singleArticle)
        {
            $singleArticle->jp_year   = $singleArticle->created_at->year   . $jp_year;
            $singleArticle->jp_month  = $singleArticle->created_at->month  . $jp_month;
            $singleArticle->jp_day    = $singleArticle->created_at->day    . $jp_day;
            $singleArticle->jp_hour   = $singleArticle->created_at->hour   . $jp_hour;
            $singleArticle->jp_minute = $singleArticle->created_at->minute . $jp_minute;

            $singleArticle->likesTotal = $this->getImpression("like", $objectTemplateId, $singleArticle, "total");
            $singleArticle->downloadsTotal = $this->getImpression("download", $objectTemplateId, $singleArticle, "total");
            $singleArticle->viewsTotal = $this->getImpression("view", $objectTemplateId, $singleArticle, "total");
            $singleArticle->commentsTotal = $this->getImpression('comment', $objectTemplateId, $singleArticle, 'total');
            $singleArticle->hashtags      = array_slice($this->getUniquehashtags($singleArticle->id, $objectTemplateId), 0, 3);
        }

        return $articles;
    }

    public function generateQuery(Request $request) {
        $q = "";
        if(isset( $request->title )){
            $request->title = trim($request->title);
            $singleTag = explode(' ',trim($request->title))[0];

            $search = '#';
            if(preg_match("/{$search}/i", $singleTag)) {
            // if( strpos($request->title, "#") === true){

                $articles = $this->getUniquehashtagArticles($singleTag);
                $q .= $singleTag;
                if( isset($articles) )
                {
                    $articles = $articles->where('publicity', 1);
                }
            }
            else {
                $articles = Article::whereLike(['title_jp', 'content_jp'], $request->title)->where('publicity', 1);
                $q .= $request->title;
            }
        } 

        //if search has search fields, return articles of requested fields
        if(isset( $articles )) {
            $articles = $articles->paginate(3);

            // add impressions
            $articles = $this->getArticleImpressionsSearch($articles);

            return response()->json([
                'success' => true,
                'articles' => $articles,
                'message' => 'Requested query: '.$q. ' returned some results',
                'q' => $q
            ]);
        }

        // if search is empty, return default articles
        if( $q == "")
        {
            $articles = Article::where('publicity', 1)->orderBy('created_at', "DESC")->paginate(3);

            // add impressions
            $articles = $this->getArticleImpressionsSearch($articles);

             return response()->json([
                'success' => true,
                'articles' => $articles,
                'q' => $q
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Requested query: '.$q. ' returned zero articles',
            'q' => $q
        ]);
    }

    #====================================================== Japanese Text Handling

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

    public function extractWordsListAttributes($wordList)
    {
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
            $word->meaning = "000";
            // implode(", ", array_slice(explode("|", $word->gloss[0]), 0, 3));
        }

        return $wordList;
    }

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
        $this->incrementDownload($article);
        $user = User::find($article->user_id);
        $wordList = $article->words()->get();

        $wordList = $this->extractWordsListAttributes($wordList);

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
        $this->incrementDownload($article);
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

    public function togglePublicity($id)
    {
        $article = Article::find($id);

        if( !$article || $article->user_id != auth()->user()->id || auth()->user()->role() != "admin" )
        {
            return response()->json([
                'success' => false,
                'message' => 'requested article does not exist or user is unauthorized'
            ]);
        }

        if($article->publicity == 1)
        {
            $article->publicity = 0;
            $article->update();
            return response()->json([
                'success' => true,
                'message' => 'Article of id: '.$id. ' is now private'
            ]);
        }
        else {
            $article->publicity = 1;
            $article->update();
            return response()->json([
                'success' => true,
                'message' => 'Article of id: '.$id. ' is now public'
            ]);
        }
    }

    public function setStatus(Request $request, $id)
    {
        $article = Article::find($id);
        $article->status = $request->get('status');

        if     ($request->get('status') == 2) $status = "approved";
        else if($request->get('status') == 1) $status = "unapproved";
        
        $article->update();
        
        return response()->json([
            'success' => true,
            'message' => 'Article of id: '.$id. ' set to ' .$status
        ]);
    }
    
    #========================= Impressions

    public function removeImpressions($object)
    {
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $likes = Like::where("template_id", $objectTemplateId)->where("real_object_id", $object->id)->delete();
        $views = View::where("template_id", $objectTemplateId)->where("real_object_id", $object->id)->delete();

        $comments = Comment::where("template_id", $objectTemplateId)->where("real_object_id", $object->id)->get();
        $objectTemplateId = ObjectTemplate::where('title', 'comment')->first()->id;
        foreach($comments as $comment){
            $commentLikes = Like::where("template_id", $objectTemplateId)->where('real_object_id', $comment->id)->delete();
            $comment->delete();
        }
    }

    public function incrementDownload(Article $article)
    {
        // if( !auth()->user() ){
        //     return response()->json([
        //         'message' => 'you are not a user'
        //     ]);
        // }
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $download = new Download;
        $download->user_id = auth()->user()->id;
        $download->template_id = $objectTemplateId;
        $download->real_object_id = $article->id;
        $download->save();
    }

    public function incrementView(Article $article)
    {
        if( !auth()->user() ){
            return response()->json([
                'success' => true,
                'message' => "User unauthenticated, no views counted"
            ]);
        }

        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $checkView = View::where([
            'template_id' => $objectTemplateId,
            'real_object_id' => $article->id,
            'user_id' => auth()->user()->id,
            'user_ip' => request()->ip()
        ])->first();

        if($checkView)
        {
            $checkView->updated_at = date('Y-m-d H:i:s');
            $checkView->update();
        }
        else {
            $view = new View;
            $view->user_id = auth()->user()->id;
            $view->user_ip = request()->ip();
            $view->template_id = $objectTemplateId;
            $view->real_object_id = $article->id;
            $view->save();
        }
    }

    public function getImpression($impressionType, $objectTemplateId, $object, $amount)
    {
        if($impressionType == 'like') 
        {
            $likes = Like::where([
                'template_id' => $objectTemplateId,
                'real_object_id' => $object->id
                ]);
            if($amount == "total") { return $likes->count(); }        
            else if($amount == "all") { return $likes->get(); }        
        }
        else if($impressionType == 'download') 
        {
            $downloads = Download::where([
                'template_id' => $objectTemplateId,
                'real_object_id' => $object->id
                ]);   
            if($amount == "total") { return $downloads->count(); }        
            else if($amount == "all") { return $downloads->get(); }        
        }
        else if($impressionType == 'view') 
        {
            $views = View::where([
                'template_id' => $objectTemplateId,
                'real_object_id' => $object->id
                ]);   
            if($amount == "total") { return $views->count(); }        
            else if($amount == "all") { return $views->get(); }        
        }
        else if($impressionType == 'comment') 
        {
            $comments = Comment::where([
                'template_id' => $objectTemplateId,
                'real_object_id' => $object->id
                ]);   
            if($amount == "total") { return $comments->count(); }        
            else if($amount == "all") { return $comments->orderBy('created_at', "DESC")->get(); }        
        }
    }

    public function storeComment(Request $request, $id, $parentCommentId = null)
    {
        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|min:2|max:1000',
        ]);

        if($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        
        $comment = new Comment;
        $comment->user_id = auth()->user()->id;
        $comment->template_id = $objectTemplateId;
        $comment->real_object_id = $id;
        $comment->parent_comment_id = null;
        $comment->content = $request->get('content');
        $comment->save();
        $comment->likesTotal = 0;
        $comment->likes = [];

        return response()->json([
            'success' => true,
            'message' => 'You commented article of id: '.$id,
            'comment' => $comment
        ]);
    }

    public function deleteComment($id, $commentid)
    {
        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }

        $comment = Comment::where([
            'id' => $commentid,
            'user_id' => auth()->user()->id
        ])->first();

        if(isset($comment))
        {
            $objectTemplateId = ObjectTemplate::where('title', 'comment')->first()->id;
            $commentLikes = Like::where("template_id", $objectTemplateId)->where('real_object_id', $commentid)->delete();

            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => "comment was deleted",
            ]);
        }
        else {
            return response()->json([
                'success' => false,
                'message' => "Comment does not belong to user or comment doesnt exist",
            ]);
        }
    }

    public function updateComment(Request $request, $id, $commentid)
    {
        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|min:2|max:1000',
        ]);

        if($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $comment = Comment::where([
            'id' => $commentid,
            'user_id' => auth()->user()->id
        ])->first();

        if(isset($comment))
        {
            $comment->content = $request->get('content');
            $comment->updated_at = date('Y-m-d H:i:s');
            $comment->update();

            return response()->json([
                'success' => true,
                'message' => "comment was updated",
            ]);
        }
        else {
            return response()->json([
                'success' => false,
                'message' => "Comment does not belong to user or comment doesnt exist",
            ]);
        }
    }

    public function likeComment($id, $commentid)
    {
        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }
        $objectTemplateId = ObjectTemplate::where('title', 'comment')->first()->id;

        $checkLike = Like::where([
            'template_id' => $objectTemplateId,
            'real_object_id' => $commentid,
            'user_id' => auth()->user()->id
        ])->first();
        
        if($checkLike) {
            return response()->json([
                'message' => 'you cannot like the comment twice!'
            ]);
        }
        
        $like = new Like;
        $like->user_id = auth()->user()->id;
        $like->template_id = $objectTemplateId;
        $like->real_object_id = $commentid;
        $like->value=1;
        $like->save();

        return response()->json([
            'success' => true,
            'message' => 'You liked comment of id: '.$commentid,
            'like' => $like
        ]);
    }

    public function unlikeComment($id, $commentid) {
        $objectTemplateId = ObjectTemplate::where('title', 'comment')->first()->id;
        $like = Like::where([
            'template_id' => $objectTemplateId,
            'real_object_id' => $commentid,
            'user_id' => auth()->user()->id
        ]);

        $like->delete();

        return response()->json([
            'success' => true,
            'message' => "like was deleted",
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
            'success' => true,
            'message' => 'You liked list of id: '.$id,
            'like' => $like
        ]);
    }

    public function checkIfLikedArticle($id) {
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

        $checkLike = Like::where([
            'template_id' => $objectTemplateId,
            'real_object_id' => $id,
            'user_id' => auth()->user()->id
        ])->first();
        
        if($checkLike) {
            return response()->json([
                'userId' => auth()->user()->id,
                'isLiked' => true,
                'message' => 'you already liked this article'
            ]);
        }

        return response()->json([
            'userId' => auth()->user()->id,
            'isLiked' => false,
            'message' => 'you havent liked the article yet'
        ]);
    }

    public function checkIfLikedComment($id) {
        $objectTemplateId = ObjectTemplate::where('title', 'comment')->first()->id;

        $checkLike = Like::where([
            'template_id' => $objectTemplateId,
            'real_object_id' => $id,
            'user_id' => auth()->user()->id
        ])->first();
        
        if($checkLike) {
            return response()->json([
                'userId' => auth()->user()->id,
                'isLiked' => true,
                'message' => 'you already liked this comment'
            ]);
        }

        return response()->json([
            'userId' => auth()->user()->id,
            'isLiked' => false,
            'message' => 'you havent liked the comment yet'
        ]);
    }
    
    #======================== Hashtags

    public function getUniquehashtagArticles($wantedTag)
    {
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        // get tag which was input id
        $uniqueTag = Uniquehashtag::where("content", $wantedTag)->first();
        if( !isset( $uniqueTag )) {
            return null;
        }
        // get all hashtag foreign table rows
        $foundRows = DB::table('hashtags')->where('uniquehashtag_id', $uniqueTag->id)
            ->where('template_id', $objectTemplateId)->get();

        $ids = [];
        // get all articles with that tag id
        foreach($foundRows as $articlelink)
        {
            $ids[] = $articlelink->real_object_id;
        }
        
        $articles = Article::whereIn('id', $ids);

        return $articles;
    }

    public function getUniquehashtags($id, $objectTemplateId)
    {  
        $foundRows = DB::table('hashtags')->where('real_object_id', $id)
        ->where('template_id', $objectTemplateId)->get();
        $finalTags = [];

        foreach($foundRows as $taglink)
        {
            $uniqueTag = Uniquehashtag::find($taglink->uniquehashtag_id);
            $finalTags[] = $uniqueTag;
        }

        return $finalTags;
    }

    public function checkIfHashtagsAreUnique($tags)
    {
        $finalTags = [];
        $same = 0;
        $unique = 0;
        foreach($tags as $tag)
        {
            $uniqueTag = Uniquehashtag::where("content", $tag)->first();
            if($uniqueTag)
            {   
                // tag is not unique
                $finalTags[] = $uniqueTag;
                $same++;
            }
            else {
                // tag is unique
                $uniqueTag = new Uniquehashtag;
                $uniqueTag->content = $tag;
                $uniqueTag->save();
                $finalTags[] = $uniqueTag;
                $unique++;
            }
        }

        return $finalTags;
    }

    public function removeHashtags($id, $objectTemplateId)
    {
        $oldTags = DB::table('hashtags')
            ->where('template_id', $objectTemplateId)
            ->where('real_object_id', $id)
            ->delete();
    }

    public function attachHashTags($tags, $object)
    {
        $tags = $this->getHashtags($tags);
        $tags = $this->checkIfHashtagsAreUnique($tags);
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

        foreach($tags as $tag)
        {
            $row = [
                'template_id' => $objectTemplateId,
                'uniquehashtag_id' => $tag->id,
                'real_object_id' => $object->id,
                'user_id' => $object->user_id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $x = DB::table('hashtags')->insert($row);            
        }

        return response()->json([
            'success' => true,
            'message' => "hashtags were added."
        ]);
    }

    public function getHashtags($string) {  
        $hashtags= FALSE;  
        preg_match_all("/(#\w+)/u", $string, $matches);  
        if ($matches) {
            $hashtagsArray = array_count_values($matches[0]);
            $hashtags = array_keys($hashtagsArray);
        }
        return $hashtags;
    }
}