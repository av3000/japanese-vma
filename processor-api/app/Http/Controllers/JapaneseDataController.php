<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Kanji;
use App\Word;
use App\Sentence;
use App\Radical;

class JapaneseDataController extends Controller
{
    public function indexRadicals() {
        return $radicals = Radical::all()->skip(0)->take(10);
    }

    public function showRadical($id){
        $singleRadical = Radical::find($id);
        $singleRadical->kanjis = $singleRadical->kanjis()->get();
        return $singleRadical;
    }

    public function indexKanjis() {
        return $kanjis = Kanji::all()->skip(0)->take(10);
    }

    public function showKanji($id) {
        $singleKanji = Kanji::find($id);
        $singleKanji->words = $singleKanji->words()->get();
        $singleKanji->sentences = $singleKanji->sentences()->get();
        $singleKanji->articles = $singleKanji->articles()->get();
        return $singleKanji;
    }

    public function indexWords() {
        return $words = Word::all()->skip(200)->take(10);
    }

    public function showWord($id) {
        $singleWord = Word::find($id);
        $singleWord->articles = $singleWord->articles()->get();
        $singleWord->kanjis = $singleWord->kanjis()->get();
        // $singleWord->sentences = $singleWord->sentences()->get(); not yet
        return $singleWord;
    }

    public function wordKanjis($id) {
        $wordKanjis = Sentence::find($id)->kanjis()->get();

        if(isset($wordKanjis))
         {
            return response()->json([
                'success' => true, 'wordKanjis' => $wordKanjis
             ]);
         }
         return response()->json([
            'success' => false, 'message' => 'Requested Word does not have kanjis'
         ]);
    }

    public function indexSentences() {
        return $sentences = Sentence::all()->skip(0)->take(10);
    }

    public function showSentence($id) {
        $singleSentence = Sentence::find($id);
        $singleSentence->kanjis = $singleSentence->kanjis()->get();
        $singleSentence->words = $singleSentence->words()->get();
        return $singleSentence;
    }

    public function generateQuery(Request $request) { // Not sure about encoding part if it work.
        $q = $request->get("word");
        $results = [];
        $results['words'] = Word::where("word", "like", "%".$q."%")
                        ->orWhere("furigana", "like", "%".$q."%")->get();

        if(!isset($results['words'])) {
            return response()->json([
                'success' => false, 'message' => 'Requested query: '.$q. ' returned zero results'
             ]);
        }
        return response()->json([
            'success' => true,
            'message' => 'Requested words query: '.$q. ' returned: '.count($results["words"]).' results',
            'results' => $results["words"]
        ]);
    }

    public function sentenceKanjis($id){
        $sentenceKanjis = Sentence::find($id)->kanjis()->get();

        if(isset($sentenceKanjis))
         {
            return response()->json([
                'success' => true, 'sentenceKanjis' => $sentenceKanjis
             ]);
         }
         return response()->json([
            'success' => false, 'message' => 'Requested Sentence does not have kanjis'
         ]);
    }

    public function sentenceWords($id){
        $sentenceWords = Article::find($id)->words()->get();

        if(isset($sentenceWords))
         {
            return response()->json([
                'success' => true, 'sentenceWords' => $sentenceWords
             ]);
         }
         return response()->json([
            'success' => false, 'message' => 'Requested Sentence does not have words'
         ]);
    }

    public function storeSentence(Request $request){
        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }   

        $rules = [
            'content' => 'required|string|min:4|max:300',
            'content_en' => 'string|max:300',
        ];

        $validator=Validator::make($request->all(), $rules);
        if($validator->fails())
        {
            return response()->json($validator->errors(), 201);
        }

        $sentence = new Sentence;
        $sentence->user_id = auth()->user()->id;
        if(isset($request->content_en))
        {
            $sentence->content_en = $request->content_en;
        } else {
            $sentence->content_en = "";
        }
        $sentence->content = $request->content;
        $sentence->save();

        $kanjiResponse = $this->getKanjiIdsFromText($sentence);
        $wordResponse  = $this->getWordIdsFromText($sentence);

        return response()->json([
            'success' => true,
            'sentence' => $sentence,
            'kanjis' => $kanjiResponse,
            'words' => $wordResponse
        ]);
    }

    public function updateSentence(Request $request, $id) {
        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }   

        $rules = [
            'content' => 'required|string|min:4|max:300',
            'content_en' => 'string|max:300',
        ];

        $validator=Validator::make($request->all(), $rules);
        if($validator->fails())
        {
            return response()->json($validator->errors(), 201);
        }

        $sentence = Sentence::find($id);
        if(isset($request->content_en))
        {
            $sentence->content_en = $request->content_en;
        } else {
            $sentence->content_en = "";
        }
        $sentence->content = $request->content;
        $sentence->save();

        $sentence->kanjis()->wherePivot('sentence_id', $sentence->id)->detach();
        $sentence->words()->wherePivot('sentence_id', $sentence->id)->detach();

        $kanjiResponse = $this->getKanjiIdsFromText($sentence);
        $wordResponse  = $this->getWordIdsFromText($sentence);

        return response()->json([
            'success' => true,
            'updated_sentence' => $sentence,
            'reattached_kanjis' => $kanjiResponse,
            'reattached_words' => $wordResponse
        ]);
    }

    public function deleteSentence(Request $request, $id) {
        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }   
        $sentence = sentence::find($id);

        $sentence->kanjis()->wherePivot('sentence_id', $sentence->id)->detach();
        $sentence->words()->wherePivot('sentence_id', $sentence->id)->detach();

        $sentence->delete();

        return response()->json([
            'success' => true,
            'deleted_sentence' => $sentence,
        ]);
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
     * @param object Sentence
     */
    public function getKanjiIdsFromText(Sentence $sentence) {
        $raw_text    = $this->mb_str_split($sentence->content);
        $total       = sizeof($raw_text);
        $foundKanjis = [];
        $index       = 0;
        while($index < $total) {
            $kanji = Kanji::where( 'kanji', 'like', $raw_text[$index])->first();
                if(isset($kanji) && !in_array($kanji->id, $foundKanjis, TRUE) ){ 
                    array_push($foundKanjis, $kanji->id);
                    // echo "<p>kanji found: " .$kanji->kanji. "</p>";
                    $sentence->kanjis()->attach($kanji);
                }
            $index++;
        }

        if( count($foundKanjis)  == 0) {
            return response()->json(['success' => false, 'kanji_message'=> 'There was no kanji characters in sentence text...']);
        }
        return response()->json(['success' => true, 'kanji_message'=> 'Kanji characters were attached to the sentence!']);
    }

    /**
     * @param object Sentence
     */
    public function getWordIdsFromText(Sentence $sentence) {
        $testString     = str_replace(array("\n", "\r", " "), "", $article->content);
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
                                $sentence->words()->attach($fetchWordAtTheTime);
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
                                $sentence->words()->attach($fetchWordAtTheTime);
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
            return response()->json(['success' => false, 'word_message'=> 'There was no words found in the sentence text...'
            ]);
        }
        return response()->json(['success' => true, 'word_message'=> 'Words were attached to the sentence!']);
    }

    /**
     * @param object Sentence TODO
     *
     */
    public function getWordsFuriganaFromText(Sentence $sentence){

    }
}
