<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ListStoreRequest;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\Article;
use App\Radical;
use App\Kanji;
use App\Word;
use App\Sentence;
use App\CustomList;
use App\Comment;
use App\Like;
use App\Download;
use App\View;
use App\ObjectTemplate;
use App\Uniquehashtag;
use Illuminate\Support\Facades\DB;
use PDF;

class CustomListController extends Controller
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

    public function getListItems(CustomList $list)
    {
        $objectsArray = [];
        $foundRows    = [];

        $foundRows = DB::table('customlist_object')->where('list_id', $list->id)->get();
        if( $list->type == self::KNOWNRADICALS || $list->type == self::RADICALS ){ // radicals
            foreach( $foundRows as $row )
            {
                $radical = Radical::where('id', $row->real_object_id)->first();
                $radical->savesTotal = DB::table('customlist_object')
                    ->where('real_object_id', $row->real_object_id)
                    ->where('listtype_id', $list->type)->count();
                array_push( $objectsArray, $radical );
            }
        }
        else if( $list->type == self::KNOWNKANJIS || $list->type == self::KANJIS ){ // kanjis
            foreach( $foundRows as $row )
            {
                $kanji = Kanji::where('id', $row->real_object_id)->first();
                $kanji->savesTotal = DB::table('customlist_object')
                    ->where('real_object_id', $row->real_object_id)
                    ->where('listtype_id', $list->type)->count();
                array_push( $objectsArray, $kanji );
            }
        }
        else if( $list->type == self::KNOWNWORDS || $list->type == self::WORDS ){ // words
            foreach( $foundRows as $row )
            {
                $word = Word::where('id', $row->real_object_id)->first();
                $word->savesTotal = DB::table('customlist_object')
                    ->where('real_object_id', $row->real_object_id)
                    ->where('listtype_id', $list->type)->count();
                array_push( $objectsArray, $word );
            }
            $objectsArray = $this->extractWordsListAttributes($objectsArray);
        }
        else if( $list->type == self::KNOWNSENTENCES || $list->type == self::SENTENCES ){ // sentences
            foreach( $foundRows as $row )
            {
                $sentence = Sentence::where('id', $row->real_object_id)->first();
                $sentence->savesTotal = DB::table('customlist_object')
                    ->where('real_object_id', $row->real_object_id)
                    ->where('listtype_id', $list->type)->count();
                array_push( $objectsArray, $sentence );
            }
        }
        else if( $list->type == self::ARTICLES ){ // articles
            $downloadsTotal=0;

            foreach( $foundRows as $row )
            {   
                $article = Article::where('id', $row->real_object_id)->first();
                $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
                $article->likesTotal     = $this->getImpression("like", $objectTemplateId, $article, "total");
                $article->downloadsTotal = $this->getImpression("download", $objectTemplateId, $article, "total");
                $downloadsTotal += $article->downloadsTotal;
                $article->viewsTotal     = $this->getImpression("view", $objectTemplateId, $article, "total");
                $article->commentsTotal  = $this->getImpression("comment", $objectTemplateId, $article, "total");
                $article->hashtags       = $this->getUniquehashtags($article->id, $objectTemplateId);

                $article->savesTotal = DB::table('customlist_object')
                    ->where('real_object_id', $row->real_object_id)
                    ->where('listtype_id', $list->type)->count();

                array_push( $objectsArray, $article );
            }

            $list->downloadsTotal = $downloadsTotal;
        }

        $list->listItems = $objectsArray;
        
        return $list;
    }

    public function show($id)
    {
        $list = CustomList::find($id);
        if( !$list ) {
            return response()->json([
                'success' => false,
                'message' => "List is not found",
            ]);
        }

        $list = $this->getListItems($list);
        $this->incrementView($list);
        $objectTemplateId     = ObjectTemplate::where('title', 'list')->first()->id;
        $list->hashtags       = $this->getUniquehashtags($list->id, $objectTemplateId);
        $list->likesTotal     = $this->getImpression("like", $objectTemplateId, $list, "total");
        $list->viewsTotal     = $this->getImpression("view", $objectTemplateId, $list, "total");
        $list->comments       = $this->getImpression("comment", $objectTemplateId, $list, "all");
        
        $objectTemplateId = ObjectTemplate::where('title', 'comment')->first()->id;
        foreach($list->comments as $comment)
        {
            $comment->likes = $this->getImpression('like', $objectTemplateId, $comment, "all");
            $comment->likesTotal = count($comment->likes);
            $comment->userName = User::find($comment->user_id)->name;
        }

        $list->commentsTotal  = count($list->comments);
        $list->userName = User::find($list->user_id)->name;


        return response()->json([
            'success' => true,
            'listItemsCount' => count($list->listItems),
            'list' => $list
        ]);
    }

    public function index()
    {
        $lists = CustomList::where('publicity', 1)->orderBy('created_at', "DESC")->paginate(3);

        if(!$lists)
        {
            return response()->json([
                'success' => false,
                'message' => "Lists not found..."
            ]);
        }

        $objectTemplateId = ObjectTemplate::where('title', 'list')->first()->id;
        foreach($lists as $singleList)
        {
            $singleList                 = $this->getListItems($singleList);
            $singleList->itemsTotal     = count($singleList->listItems);
            $singleList->likesTotal     = $this->getImpression("like", $objectTemplateId, $singleList, "total");
            $singleList->downloadsTotal = $this->getImpression("download", $objectTemplateId, $singleList, "total");
            $singleList->viewsTotal     = $this->getImpression("view", $objectTemplateId, $singleList, "total");
            $singleList->commentsTotal  = $this->getImpression("comment", $objectTemplateId, $singleList, "total");
            $singleList->hashtags       = $this->getUniquehashtags($singleList->id, $objectTemplateId);
        }

        return response()->json([
            'success' => true,
            'message' => 'returned: '.count($lists).' results',
            'lists' => $lists
        ]);
    }

    public function store(ListStoreRequest $request)
    {
        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }   

        $newList = new CustomList;
        $newList->user_id = auth()->user()->id;
        $newList->type = $request->type;
        $newList->title = $request->title;
        $newList->publicity = $request->publicity;
        $newList->save();
        
        $this->attachHashTags($request->tags, $newList);
        
        return response()->json([
            'success' => true,
            'newList' => $newList
        ]);
    }

    public function update(Request $request, $id)
    {
        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }   
        $list = CustomList::find($id);
        
        if( !$list || $list->user_id != auth()->user()->id ){
            return response()->json([
                'message' => 'list doesnt exist or does not belong to the user'
            ]);
        } 

        $objectTemplateId = ObjectTemplate::where('title', 'list')->first()->id;

        if( isset($request->tags) )
        {
            $this->removeHashtags($list->id, $objectTemplateId, $request->tags);
            $this->attachHashTags($request->tags, $list);
        }
        if( isset($request->title) )
        {
            $list->title = $request->get("title");
        }
        if( isset($request->publicity) )
        {
            $list->publicity = $request->get("publicity");
        }
        if( isset($request->type) )
        {
            $list->type = $request->get("type");
        }
        $list->update();

        return response()->json([
            'success' => true,
            'updatedList' => $list
        ]);
    }

    public function delete(Request $request, $id) 
    {
        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }

        $list = CustomList::find($id);
        
        if( !$list || $list->user_id != auth()->user()->id ){
            return response()->json([
                'success' => false,
                'message' => 'list doesnt exist or does not belong to the user'
            ]);
        } 

        $this->removeListItems($list->id);
        $this->removeImpressions($list);
        $objectTemplateId = ObjectTemplate::where('title', 'list')->first()->id;
        $this->removeHashtags($list->id, $objectTemplateId);

        $list->delete();

        return response()->json([
            'success' => true,
            'deletedList' => $list
        ]);
    }

    public function handleListJlpt(CustomList $list, $id, $method)
    {
        $kanji = Kanji::find($id);
        if($method == "add"){
            if     ($kanji->jlpt == "1") { $list->n1 = intval($list->n1) + 1; }
            else if($kanji->jlpt == "2") { $list->n2 = intval($list->n2) + 1; }
            else if($kanji->jlpt == "3") { $list->n3 = intval($list->n3) + 1; }
            else if($kanji->jlpt == "4") { $list->n4 = intval($list->n4) + 1; }
            else if($kanji->jlpt == "5") { $list->n5 = intval($list->n5) + 1; }
        }
        else if( $method == "remove"){
            if     ($kanji->jlpt == "1") { $list->n1 = intval($list->n1) - 1; }
            else if($kanji->jlpt == "2") { $list->n2 = intval($list->n2) - 1; }
            else if($kanji->jlpt == "3") { $list->n3 = intval($list->n3) - 1; }
            else if($kanji->jlpt == "4") { $list->n4 = intval($list->n4) - 1; }
            else if($kanji->jlpt == "5") { $list->n5 = intval($list->n5) - 1; }
        }
    }

    public function addToList(Request $request, $id)
    {
        $list = CustomList::find($id);

        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }
        else if($list->user_id != auth()->user()->id){
            return response()->json([
                'success' => false,
                'message' => 'unauthorized access'
            ]);
        }

        $newObjectId = $request->get("real_object_id");

        $row = [
            'real_object_id' => $newObjectId,
            'listtype_id' => $list->type,
            'list_id' => $id
        ];

        $x = DB::table('customlist_object')->insert($row);

        if($list->type == self::KANJIS || $list->type == self::KNOWNKANJIS) { $this->handleListJlpt($list, $newObjectId, "add"); $list->save(); }

        if($x) {
            return response()->json([
                'success' => true,
                'newObjectId' => $newObjectId,
                'idOfModifiedList' => $id
            ]);
        }
        return response()->json([
            'success' => false,
            'message' => "addToList failed."
        ]);
    }

    public function removeFromList(Request $request, $id)
    {
        $list = CustomList::find($id);
        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }
        else if($list->user_id != auth()->user()->id){
            return response()->json([
                'success' => false,
                'message' => 'unauthorized access'
            ]);
        }

        $deletedId = $request->get("real_object_id");

        $x = DB::table('customlist_object')->where('list_id', $id)->where('real_object_id', $deletedId)->delete();

        if($list->type == self::KANJIS || $list->type == self::KNOWNKANJIS) { $this->handleListJlpt($list, $deletedId, "remove"); $list->save(); }

        if($x) {
            return response()->json([
                'success' => true,
                'deletedObjectId' => $deletedId,
                'idOfModifiedList' => $id
            ]);
        }
        return response()->json([
            'success' => false,
            'message' => "removeFromList failed."
        ]);
    }

    public function getUserLists($userid)
    {
        $userLists = CustomList::where("user_id", $userid)->get();
        if( !isset($userLists) || count($userLists) == 0 )
        {
            return response()->json([
                'success' => false, 'message' => 'user has zero lists'
             ]);
        }

        foreach($userLists as $singleList)
        {
            // return $this->show($singleList->id);
            $singleList = $this->getListItems($singleList);
            $singleList->itemsTotal = count($singleList->listItems);
        }

        # Need to test LIKES
        // $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        // $article->likes = Like::where([
        //     'template_id' => $objectTemplateId,
        //     'real_object_id' => $article->id
        // ])->get();
        // $article->likesTotal = count($article->likes);

        return response()->json([
            'success' => true,
            'message' => 'returned: '.count($userLists).' results',
            'userLists' => $userLists
        ]);
    }

    // TODO. 
    // Question is, should user be able to save it? 
    // Well, some kind of bookmarking is definitely necessary.
    // You can save stuff into lists of other users
    // But how about lists of other users? Duplicate vs create references?
    public function saveUserList(CustomList $list){
       // 
    }

    public function getListImpressionsSearch($lists)
    {
        $objectTemplateId = ObjectTemplate::where('title', 'list')->first()->id;
        foreach($lists as $singleList)
        {
            $singleList                 = $this->getListItems($singleList);
            $singleList->itemsTotal     = count($singleList->listItems);
            $singleList->likesTotal     = $this->getImpression("like", $objectTemplateId, $singleList, "total");
            $singleList->downloadsTotal = $this->getImpression("download", $objectTemplateId, $singleList, "total");
            $singleList->viewsTotal     = $this->getImpression("view", $objectTemplateId, $singleList, "total");
            $singleList->commentsTotal  = $this->getImpression("comment", $objectTemplateId, $singleList, "total");
            $singleList->hashtags       = $this->getUniquehashtags($singleList->id, $objectTemplateId);
        }

        return $lists;
    }

    public function generateQuery(Request $request) {
        $q = "";
        if(isset( $request->title )){
            $request->title = trim($request->title);
            $singleTag = explode(' ',trim($request->title))[0];

            $search = '#';
            if(preg_match("/{$search}/i", $singleTag)) {
            // if( strpos($request->title, "#") === true){

                $lists = $this->getUniquehashtagLists($singleTag);
                $q .= $singleTag;
                if( isset($lists) )
                {
                    $lists = $lists->where('publicity', 1);
                }
            }
            else {
                $lists = CustomList::whereLike(['title'], $request->title)->where('publicity', 1);
                $q .= $request->title;
            }
        } 

        //if search has search fields, return lists of requested fields
        if(isset( $lists )) {
            $lists = $lists->paginate(3);

            // add impressions
            $lists = $this->getListImpressionsSearch($lists);

            return response()->json([
                'success' => true,
                'lists' => $lists,
                'message' => 'Requested query: '.$q. ' returned some results',
                'q' => $q
            ]);
        }

        // if search is empty, return default lists
        if( $q == "")
        {
            $lists = CustomList::where('publicity', 1)->orderBy('created_at', "DESC")->paginate(3);

            // add impressions
            $lists = $this->getListImpressionsSearch($lists);

             return response()->json([
                'success' => true,
                'lists' => $lists,
                'q' => $q
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Requested query: '.$q. ' returned zero lists',
            'q' => $q
        ]);
    }

    # PDF generating
    public function generateRadicalsPdf($id)
    {
        if( !auth()->user() ){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }

        $list = CustomList::find($id);

        if( !$list )
        {
            return response()->json([
                'success' => false,
                'message' => 'requested list does not exist'
            ]);
        }

        if( $list->type != self::RADICALS && $list->type != self::KNOWNRADICALS)
        {
            return response()->json([
                'success' => false,
                'message' => 'requested list does not contain Radicals'
            ]);
        }

        $this->incrementDownload($list);
        $user = User::find($list->user_id);
        $list = $this->getListItems($list);

        $data = [
            'list_id' => $list->id,
            'title' => $list->title,
            'author' => $user->name,
            'user_id' => $user->id,
            'date' => $list->created_at,
            'radicalList' => $list->listItems
        ];

        $pdf = PDF::loadView("pdf.kanjis.list-radicals", $data);
        $pdf->setOptions([
            'footer-center' => '[page]',
            'page-size'=> 'a4'
        ]);
        
        return $pdf->stream("list-radicals.pdf");
    }

    public function generateKanjisPdf($id)
    {
        if( !auth()->user() ){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }

        $list = CustomList::find($id);

        if( !$list )
        {
            return response()->json([
                'success' => false,
                'message' => 'requested list does not exist'
            ]);
        }

        if( $list->type != self::KANJIS && $list->type != self::KNOWNKANJIS)
        {
            return response()->json([
                'success' => false,
                'message' => 'requested list does not contain Kanjis'
            ]);
        }

        $this->incrementDownload($list);
        $user = User::find($list->user_id);
        $list = $this->getListItems($list);

        $kanjiList = $list->listItems;
        foreach($kanjiList as $kanji) {
            $kanji->onyomi = implode(", ", array_slice(explode("|", $kanji->onyomi), 0, 3));
            $kanji->kunyomi = implode(", ", array_slice(explode("|", $kanji->kunyomi), 0, 3));
            $kanji->meaning = implode(", ", array_slice(explode("|", $kanji->meaning), 0, 3));
        }

        $data = [
            'list_id' => $list->id,
            'title' => $list->title,
            'author' => $user->name,
            'user_id' => $user->id,
            'date' => $list->created_at,
            'kanjiList' => $list->listItems
        ];

        $pdf = PDF::loadView("pdf.kanjis.list-kanjis", $data);
        $pdf->setOptions([
            'footer-center' => '[page]',
            'page-size'=> 'a4'
        ]);
        
        return $pdf->stream("list-kanjis.pdf");
    }

    public function extractWordsListAttributes($wordList)
    {
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
                    // if( !in_array($singleTag[0], $differentTags) ) { array_push($differentTags, $singleTag[0]); }
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

        return $wordList;
    }

    public function generateWordsPdf($id)
    {
        if( !auth()->user() ){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }

        $list = CustomList::find($id);
        if( !$list )
        {
            return response()->json([
                'success' => false,
                'message' => 'requested list does not exist'
            ]);
        }

        if( $list->type != self::WORDS && $list->type != self::KNOWNWORDS)
        {
            return response()->json([
                'success' => false,
                'message' => 'requested list does not contain Words'
            ]);
        }

        $this->incrementDownload($list);
        $user = User::find($list->user_id);
        $list = $this->getListItems($list);

        $wordList = $this->extractWordsListAttributes($list->listItems);

        $data = [
            'list_id' => $list->id,
            'title' => $list->title,
            'author' => $user->name,
            'user_id' => $user->id,
            'date' => $list->created_at,
            'wordList' => $list->listItems
        ];

        $pdf = PDF::loadView("pdf.kanjis.list-words", $data);
        $pdf->setOptions([
            'footer-center' => '[page]',
            'page-size'=> 'a4'
        ]);
        
        return $pdf->stream("list-words.pdf");
    }

    public function generateSentencesPdf($id)
    {
        if( !auth()->user() ){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }

        $list = CustomList::find($id);

        if( !$list )
        {
            return response()->json([
                'success' => false,
                'message' => 'requested list does not exist'
            ]);
        }

        if( $list->type != self::SENTENCES && $list->type != self::KNOWNSENTENCES )
        {
            return response()->json([
                'success' => false,
                'message' => 'requested list does not contain Sentences'
            ]);
        }

        $this->incrementDownload($list);
        $user = User::find($list->user_id);
        $list = $this->getListItems($list);

        $data = [
            'list_id' => $list->id,
            'title' => $list->title,
            'author' => $user->name,
            'user_id' => $user->id,
            'date' => $list->created_at,
            'sentenceList' => $list->listItems
        ];

        $pdf = PDF::loadView("pdf.kanjis.list-sentences", $data);
        $pdf->setOptions([
            'footer-center' => '[page]',
            'page-size'=> 'a4'
        ]);
        
        return $pdf->stream("list-sentences.pdf");
    }

    public function togglePublicity($id)
    {
        $list = CustomList::find($id);

        if( !$list || $list->user_id != auth()->user()->id || auth()->user()->role() != "admin" )
        {
            return response()->json([
                'success' => false,
                'message' => 'requested list does not exist or user is unauthorized'
            ]);
        }

        if($list->publicity == 1)
        {
            $list->publicity = 0;
            $list->update();
            return response()->json([
                'success' => true,
                'message' => 'list of id: '.$id. ' is now private'
            ]);
        }
        else {
            $list->publicity = 1;
            $list->update();
            return response()->json([
                'success' => true,
                'message' => 'list of id: '.$id. ' is now public'
            ]);
        }
    }

    #===================================== Impressions

    public function removeListItems($id)
    {
        DB::table('customlist_object')->where('list_id', $id)->delete();
    }

    public function removeImpressions($object)
    {
        $objectTemplateId = ObjectTemplate::where('title', 'list')->first()->id;
        $likes = Like::where("template_id", $objectTemplateId)->where("real_object_id", $object->id)->delete();
        $views = View::where("template_id", $objectTemplateId)->where("real_object_id", $object->id)->delete();

        $comments = Comment::where("template_id", $objectTemplateId)->where("real_object_id", $object->id)->get();
        $objectTemplateId = ObjectTemplate::where('title', 'comment')->first()->id;
        foreach($comments as $comment){
            $commentLikes = Like::where("template_id", $objectTemplateId)->where('real_object_id', $comment->id)->delete();
            $comment->delete();
        }
    }

    public function unlikeList($id) {
        $objectTemplateId = ObjectTemplate::where('title', 'list')->first()->id;
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

    public function likeList($id) {
        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }
        $objectTemplateId = ObjectTemplate::where('title', 'list')->first()->id;
        
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

    public function checkIfLikedList($id) {
        $objectTemplateId = ObjectTemplate::where('title', 'list')->first()->id;

        $checkLike = Like::where([
            'template_id' => $objectTemplateId,
            'real_object_id' => $id,
            'user_id' => auth()->user()->id
        ])->first();
        
        if($checkLike) {
            return response()->json([
                'userId' => auth()->user()->id,
                'isLiked' => true,
                'message' => 'you already liked this list'
            ]);
        }

        return response()->json([
            'userId' => auth()->user()->id,
            'isLiked' => false,
            'message' => 'you havent liked the list yet'
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
    } // Client app check by map.likes (like => like.user_id === currentUser.user.id)

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
            else if($amount == "all") { return $comments->get(); }        
        }
        else if($impressionType == 'hashtag') 
        {
            $hashtags = Comment::where([
                'template_id' => $objectTemplateId,
                'real_object_id' => $object->id
                ]);   
            if($amount == "total") { return $hashtags->count(); }        
            else if($amount == "all") { return $hashtags->get(); }        
        }
    }

    public function incrementDownload(CustomList $list)
    {
        $objectTemplateId = ObjectTemplate::where('title', 'list')->first()->id;
        $download = new Download;
        $download->user_id = auth()->user()->id;
        $download->template_id = $objectTemplateId;
        $download->real_object_id = $list->id;
        $download->save();
    }

    public function incrementView(CustomList $list)
    {
        if( !auth()->user() ){
            return response()->json([
                'success' => true,
                'message' => "User unauthenticated, no views counted"
            ]);
        }

        $objectTemplateId = ObjectTemplate::where('title', 'list')->first()->id;
        $checkView = View::where([
            'template_id' => $objectTemplateId,
            'real_object_id' => $list->id,
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
            $view->real_object_id = $list->id;
            $view->save();
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

        $objectTemplateId = ObjectTemplate::where('title', 'list')->first()->id;
        
        $comment = new Comment;
        $comment->user_id = auth()->user()->id;
        $comment->template_id = $objectTemplateId;
        $comment->real_object_id = $id;
        $comment->content = $request->get('content');
        $comment->save();
        $comment->likesTotal = 0;
        $comment->likes = [];

        return response()->json([
            'success' => true,
            'message' => 'You commented list of id: '.$id,
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
            $objectTemplateId = ObjectTemplate::where('title', 'list')->first()->id;
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

    public function getUniquehashtagLists($wantedTag)
    {
        $objectTemplateId = ObjectTemplate::where('title', 'list')->first()->id;
        // get tag which was input id
        $uniqueTag = Uniquehashtag::where("content", $wantedTag)->first();
        if( !isset( $uniqueTag )) {
            return null;
        }
        // get all hashtag foreign table rows
        $foundRows = DB::table('hashtags')->where('uniquehashtag_id', $uniqueTag->id)
            ->where('template_id', $objectTemplateId)->get();

        $ids = [];
        // get all lists with that tag id
        foreach($foundRows as $listlink)
        {
            $ids[] = $listlink->real_object_id;
        }
        
        $lists = CustomList::whereIn('id', $ids);

        return $lists;
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

        // if(count($finalTags) > 0)
        // {
        //     return response()->json([
        //         'success' => true,
        //         'same' => $same,
        //         'unique' => $unique,
        //         'finalTags' => $finalTags
        //     ]);
        // }

        // return response()->json([
        //     'success' => false,
        //     'same' => $same,
        //     'unique' => $unique,
        //     'finalTags' => $finalTags
        // ]);
        return $finalTags;
    }

    public function removeHashtags($id, $objectTemplateId)
    {
        $oldTags = DB::table('hashtags')
            ->where('template_id', $objectTemplateId)
            ->where('real_object_id', $id)
            ->delete();
    }

    public function attachHashtags($tags, $object)
    {
        $tags = $this->getHashtags($tags);
        $tags = $this->checkIfHashtagsAreUnique($tags);
        $objectTemplateId = ObjectTemplate::where('title', 'list')->first()->id;

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