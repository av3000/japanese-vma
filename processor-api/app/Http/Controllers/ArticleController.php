<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\Article;
use App\Kanji;
use App\Word;
use App\Http\Requests\ArticleStoreRequest;

class ArticleController extends Controller
{
    public function index() {
        return Article::all();
    }

    public function show($id) { // Helper function which will count and save jlpts after kanji extract
        $article = Article::find($id);
        $article->jlpt1 = 0;
        $article->jlpt2 = 0;
        $article->jlpt3 = 0;
        $article->jlpt4 = 0;
        $article->jlpt5 = 0;
        $article->jlptcommon = 0;

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
        $article->author = $user->name;
        
        return $article;
    }

    public function getUserArticles($id) {
        return $articles = Article::where("user_id", $id)->get();
    }

    public function articleKanjis($id){
        return $article = Text::find($id)->kanjis()->get();
    }

    public function articleWords($id){
        return $article = Text::find($id)->words()->get();
    }

    public function generateQuery(Request $request) { // Not sure about encoding part if it work.
        $q = $request->get("q");
        return Article::where("title_en", "like", "%".$q."%")
                        ->orWhere("title_jp", "like", "%".$q."%")->get();
    }

    public function store(ArticleStoreRequest $request) { // Test out the request

        return $validated = $request->validated();

        // $article = new Article;
        // $article->title = $request->title;
        // $article->user_id = $request->user_id;
        // $article->content = $request->content;
        // $article->source_link = $request->source_link;
        
        // $article->save();

        // $this->getKanjiIdsFromText($article);
        // $this->getWordIdsFromText($article);

        // return response()->json('Article created!');
    }

    public function update(Request $request, $id) {
        $article = Article::find($id);

        $article->kanjis()->wherePivot('article_id', $article->id)->detach();
        $article->words()->wherePivot('article_id', $article->id)->detach();
        
        $article->update($request->all());

        $this->getKanjiIdsFromText($article);
        $this->getWordIdsFromText($article);
        
        return response()->json("Article updated!");
    }

    public function delete(Request $request, $id) {
        $article = article::find($id);

        $article->kanjis()->wherePivot('article_id', $article->id)->detach();
        $article->words()->wherePivot('article_id', $article->id)->detach();

        $article->delete();

        return response()->json("Article deleted!");
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
        $raw_text    = $this->mb_str_split($article->title . $article->content);
        $total       = sizeof($raw_text);
        $foundKanjis = [];
        $index       = 0;

        while($index < $total) {
            $kanji = Kanji::where( 'kanji', 'like', $raw_text[$index])->first();
                if(isset($kanji) && !in_array($kanji->id, $foundKanjis, TRUE) ){ 
                    array_push($foundKanjis, $kanji->id);
                    $article->kanjis()->attach($kanji);
                }
            $index++;
        }

    }

    /**
     * @param object Article
     */
    public function getWordIdsFromText(Article $article) {
        $testString     = $article->title . $article->content;
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
    }
}
