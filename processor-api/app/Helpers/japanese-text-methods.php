<?php 

use Illuminate\Support\Facades\DB;
use App\Http\Models\ObjectTemplate;
use App\Http\Models\Kanji;
use App\Http\Models\Word;
use App\Http\Models\Article;


function getArticleImageFromImages($image_name)
{
    return public_path('images/articles/user/'.$image_name);
}

 /**
 * Return true if string found in string array
 * @param string, @param array 
 * @return boolean
 */
function containsWord(string $str, array $arr) {
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
function japanese_custom_mb_str_split(string $string, $split_length = 1) {
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

function extractWordsListAttributes($wordList)
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

/**
 * @param object Article
 */
function getKanjiIdsFromText(Article $article) {
    $raw_text    = japanese_custom_mb_str_split($article->title_jp . $article->content_jp);
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
function getWordIdsFromText(Article $article) {
    $testString     = $article->title_jp . $article->content_jp;
    $testString     = str_replace(array("\n", "\r", " "), "", $testString);
    $fullText       = japanese_custom_mb_str_split($testString);
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

?>