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
use App\Like;
use App\ObjectTemplate;
use Illuminate\Support\Facades\DB;

class CustomListController extends Controller
{
    public function getListTypes()
    {      
        /* 
            // $knowRadicalsList    = 1;
            // $knowKanjisList      = 2;
            // $knowWordsList       = 3;
            // $knowSentencesList   = 4;
            // $customRadicalsList  = 5;
            // $customKanjisList    = 6;
            // $customWordsList     = 7;
            // $customSentencesList = 8;
            // $customArticlesList  = 9;
            // $customLyricsList    = 10;
            // $customArtistsList   = 11;
        */
        $createListOptions = [
            5 => "Radicals",
            6 => "Kanjis",
            7 => "Words",
            8 => "Sentences",
            9 => "Articles",
            10 => "Lyrics",
            11 => "Artists"
        ];

        return $createListOptions;
    }

    public function getListItems(CustomList $list)
    {
        $objectsArray = [];
        $foundRows    = [];

        $foundRows = DB::table('customlist_object')->where('list_id', $list->id)->get();
        if( $list->type == 1 || $list->type == 5 ){ // radicals
            foreach( $foundRows as $row )
            {
                array_push( $objectsArray, Radical::where('id', $row->real_object_id)->first() );
            }
        }
        else if( $list->type == 2 || $list->type == 6 ){ // kanjis
            foreach( $foundRows as $row )
            {
                array_push( $objectsArray, Kanji::where('id', $row->real_object_id)->first() );
            }
        }
        else if( $list->type == 3 || $list->type == 7 ){ // words
            foreach( $foundRows as $row )
            {
                array_push( $objectsArray, Word::where('id', $row->real_object_id)->first() );
            }
        }
        else if( $list->type == 4 || $list->type == 8 ){ // sentences
            foreach( $foundRows as $row )
            {
                array_push( $objectsArray, Sentence::where('id', $row->real_object_id)->first() );
            }
        }
        else if( $list->type == 9 ){ // articles
            foreach( $foundRows as $row )
            {
                array_push( $objectsArray, Article::where('id', $row->real_object_id)->first() );
            }
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

        $objectTemplateId = ObjectTemplate::where('title', 'list')->first()->id;
        $list->likes = Like::where([
            'template_id' => $objectTemplateId,
            'real_object_id' => $list->id
        ])->get();
        $list->likesTotal = count($list->likes);

        if($list->listItems)
        {
            return response()->json([
                'success' => true,
                'listItemsCount' => count($list->listItems),
                'list' => $list
            ]);
        }
        else {
            return response()->json([
                'success' => false,
                'message' => "List is empty",
            ]);
        }
    }

    public function index()
    {
        $lists = CustomList::where('status', 1)->get();

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
            $singleList = $this->getListItems($singleList);
            $singleList->itemsTotal = count($singleList->listItems);
            $singleList->likes = Like::where([
                'template_id' => $objectTemplateId,
                'real_object_id' => $singleList->id
            ])->get();
            $singleList->likesTotal = count($singleList->likes);
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
        $newList->status = $request->status;
        $newList->type = $request->type;
        $newList->title = $request->title;
        $newList->save();

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
        
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:2|max:255',
        ]);

        if($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $list->title = $request->get("title");
        $list->save();

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
        
        if( !$list ) {
            return response()->json([
                'success' => false,
                'message' => "List is not found",
            ]);
        }
        $foundRows    = [];

        $foundRows = DB::table('customlist_object')->where('list_id', $list->id)->get();
        foreach($foundRows as $row)
        {
            DB::table('customlist_object')->where('list_id', $row->id)->delete();
        }    

        $list->delete();

        return response()->json([
            'success' => true,
            'deletedList' => $list
        ]);
    }

    public function addToList(Request $request, $id)
    {
        $list = CustomList::find($id)->first();
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
            'list_id' => $id
        ];
        $x = DB::table('customlist_object')->insert($row);
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
        $list = CustomList::find($id)->first();
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

    public function generateQuery(Request $request) { // Not sure about encoding part if it work.
        $q = $request->get("title");
        $results = CustomList::where("title", "like", "%".$q."%")->get();
        
        if(!isset($results)) {
            return response()->json([
                'success' => false, 'message' => ' returned zero results'
             ]);
        }
        return response()->json([
            'success' => true,
            'message' => ' returned: '.count($results).' results',
            'results' => $results
        ]);
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
            'success' => true, 'You liked list of id: '.$id, 'like' => $like
        ]);
    }
}