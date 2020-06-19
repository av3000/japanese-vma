<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Kanji;
use App\Word;
use App\Sentence;
use App\Radical;
use App\Like;
use App\Download;
use App\View;
use App\Comment;
use App\ObjectTemplate;
use App\Uniquehashtag;
use App\Article;
use App\User;
use App\CustomList;
use DB;


class JapaneseDataController extends Controller
{
    public function getKanjiTypes($index){
        $kanjiTypes = [
            "N1",
            "N2",
            "N3",
            "N4",
            "N5",
            "Uncommon"
        ];

        $kanjiTypes[20] = "All";

        return $kanjiTypes[$index-1];
    }

    public function getWordTypes($index) {
        $wordTypes = [
            "noun",
            "verb",
            "particle",
            "adverb",
            "adjective",
            "expressions"
        ];

        $wordTypes[20] = "All";

        return $wordTypes[$index-1];
    }

    public function indexRadicals() {
        $radicals = Radical::paginate(10);

        return response()->json([
            'success' => true,
            'radicals' => $radicals
        ]);
    }

    public function showRadical($id){
        $singleRadical = Radical::find($id);
        $singleRadical->kanjis = $singleRadical->kanjis()->get();
        return $singleRadical;
    }

    public function indexKanjis() {
        $kanjis = Kanji::paginate(10);

        return response()->json([
            'success' => true,
            'kanjis' => $kanjis
        ]);
    }

    public function showKanji($id) {
        $singleKanji = Kanji::find($id);
        $singleKanji->words = $this->extractWordsListAttributes($singleKanji->words()->paginate(5));
        $singleKanji->sentences = $singleKanji->sentences()->paginate(5);

        $singleKanji->articles = $singleKanji->articles()->paginate(5);

        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        foreach($singleKanji->articles as $article)
        {
            $article->likes = $this->getImpression("like", $objectTemplateId, $article, "all");
            $article->likesTotal = count($article->likes);
            $article->viewsTotal = $this->getImpression("view", $objectTemplateId, $article, "total");
            $article->comments = $this->getImpression('comment', $objectTemplateId, $article, "all");
            $article->commentsTotal = count($article->comments);
            $article->hashtags      = $this->getUniquehashtags($article->id, $objectTemplateId);
        }
        
        return $singleKanji;
    }

    public function indexWords() {
        $words = $this->extractWordsListAttributes(Word::paginate(10));

        return response()->json([
            'success' => true,
            'words' => $words
        ]);
    }

    public function showWord($id) {
        $singleWord = Word::find($id);
        $singleWord = $this->extractSingleWordAttributes($singleWord);
        $singleWord->articles = $singleWord->articles()->paginate(5);
        $singleWord->kanjis = $singleWord->kanjis()->paginate(5);
        // $singleWord->sentences = $singleWord->sentences()->get(); not yet

        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        foreach($singleWord->articles as $article)
        {
            $article->likes = $this->getImpression("like", $objectTemplateId, $article, "all");
            $article->likesTotal = count($article->likes);
            $article->viewsTotal = $this->getImpression("view", $objectTemplateId, $article, "total");
            $article->comments = $this->getImpression('comment', $objectTemplateId, $article, "all");
            $article->commentsTotal = count($article->comments);
            $article->hashtags      = $this->getUniquehashtags($article->id, $objectTemplateId);
            // $article->hashtags = array_slice($article->hashtags, 0, 3);
        }

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
        $sentences = Sentence::paginate(10);

        return response()->json([
            'success' => true,
            'sentences' => $sentences
        ]);
    }

    public function showSentence($id) {
        $singleSentence = Sentence::find($id);
        $singleSentence->kanjis = $singleSentence->kanjis()->get();
        // $singleSentence->words = $singleSentence->words()->get(); not yet
        $objectTemplateId = ObjectTemplate::where('title', 'sentence')->first()->id;

        $singleSentence->comments = $this->getImpression('comment', $objectTemplateId, $singleSentence, "all");
        $singleSentence->commentsTotal = count($singleSentence->comments);

        $objectTemplateId = ObjectTemplate::where('title', 'comment')->first()->id;
        foreach($singleSentence->comments as $comment)
        {
            $comment->likes = $this->getImpression('like', $objectTemplateId, $comment, "all");
            $comment->likesTotal = count($comment->likes);
            $comment->userName = User::find($comment->user_id)->name;
        }

        return $singleSentence;
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

        $objectTemplateId = ObjectTemplate::where('title', 'sentence')->first()->id;
        
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
            'message' => 'You commented sentence of id: '.$id,
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
        else if( !isset($comment) && auth()->user()->hasRole("admin") == true ){
            $comment = Comment::where([
                'id' => $commentid
            ])->first();

            $objectTemplateId = ObjectTemplate::where('title', 'comment')->first()->id;
            $commentLikes = Like::where("template_id", $objectTemplateId)->where('real_object_id', $commentid)->delete();
 
            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => "comment was deleted by admin",
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

    public function generateSentencesQuery(Request $request) {
        $requestedQuery = "";
        $sentences = new Sentence;
        if(isset( $request->keyword )){
           
            $sentences = Sentence::whereLike(['content'], $request->keyword);
            $requestedQuery .= "Requested: ".$request->keyword. ". ";
        } 

        $sentences = $sentences->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Requested query: '.$requestedQuery,
            'sentences' => $sentences,
            'requestedQuery' => $requestedQuery
        ]);
    }

    public function contains($needle, $haystack)
    {
        return strpos($haystack, $needle) !== false;
    }
    public function generateWordsQuery(Request $request) {
        $requestedQuery = "";
        $words = new Word;
        if(isset( $request->keyword )){
            $query = explode(' ',trim($request->keyword))[0];

            $words = Word::whereLike(['word', 'furigana'], $query);
            $requestedQuery .= "Requested: ".$query. ". ";
        } 

        if(isset( $request->filterType ) && $request->filterType != 20){ // 20 = All, so no need to filter by type.
            $words = $words->where('word_type', 'LIKE', '%'.$this->getWordTypes($request->filterType).'%');
            $requestedQuery .= "Filter by: ".$this->getWordTypes($request->filterType). ". ";
        }

        $words = $words->paginate(20);
        
        $words = $this->extractWordsListAttributes($words);

        return response()->json([
            'success' => true,
            'message' => 'Requested query: '.$requestedQuery,
            'words' => $words,
            'requestedQuery' => $requestedQuery
        ]);
    }

    public function generateKanjisQuery(Request $request) {
        $requestedQuery = "";
        $kanjis = new Kanji;
        if(isset( $request->keyword )){
            $query = explode(' ',trim($request->keyword))[0];

            $kanjis = Kanji::whereLike(['kanji', 'meaning'], $query);
            $requestedQuery .= "Requested: ".$query. ". ";
        } 

        if(isset( $request->filterType ) && $request->filterType != 20){ // 20 = All, so no need to filter by type.
            $kanjis = $kanjis->where('jlpt', $request->filterType);
            $requestedQuery .= "Filter by: ".$this->getKanjiTypes($request->filterType). ". ";
        }

        $kanjis = $kanjis->paginate(20);
        
        return response()->json([
            'success' => true,
            'message' => 'Requested query: '.$requestedQuery,
            'kanjis' => $kanjis,
            'requestedQuery' => $requestedQuery
        ]);
    
    }

    public function generateRadicalsQuery(Request $request) {
        $requestedQuery = "";
        $radicals = new Radical;
        if(isset( $request->keyword )){
            $query = explode(' ',trim($request->keyword))[0];

            $radicals = Radical::whereLike(['radical', 'meaning', 'hiragana'], $query);
            $requestedQuery .= "Requested: ".$query;
        } 

        $radicals = $radicals->paginate(20);
        
        return response()->json([
            'success' => true,
            'message' => 'Requested query: '.$requestedQuery,
            'requestFilter' => $request->filterType,
            'radicals' => $radicals,
            'requestedQuery' => $requestedQuery
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
        $sentence = Sentence::find($id);

        $sentence->kanjis()->wherePivot('sentence_id', $sentence->id)->detach();
        // $sentence->words()->wherePivot('sentence_id', $sentence->id)->detach();

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
            // $word->meaning = "000";
            $word->meaning = implode(", ", array_slice(explode("|", $word->gloss[0]), 0, 3));
        }

        return $wordList;
    }

    public function extractSingleWordAttributes($word)
    {
        $differentTags = [];
        
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

        // $word->meaning = "000";
        $word->meaning = implode(", ", array_slice(explode("|", $word->gloss[0]), 0, 3));

        return $word;
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

    public function checkIfBelongToList($itemId, $list)
    {
        $foundRows = DB::table('customlist_object')->where('list_id', $list->id)->get();
        foreach($foundRows as $row)
        {
            if($row->real_object_id == $itemId)
            {
                return true;
            }
        }

        return false;
    }

    public function getUserListAndCheckIfListHasItem(Request $request){
        $objects = $request->get("objects");
        $listTypeId = $request->get("listTypeId");
        
        return response()->json([
            "objects" => $objects
        ]);

        if(auth()->user() !== null) {
            $list = CustomList::where("user_id", auth()->user()->id)->where("type", $listTypeId)->first();
            if( !isset($list) )
            {
                return response()->json([
                    'success' => false, 'message' => 'list not found',
                ]);
            }

            foreach($objects as $object)
            {
                $object->isLearned = $this->checkIfBelongToList($object->id, $list);
            }
        }

        return $objects;
    }
}
