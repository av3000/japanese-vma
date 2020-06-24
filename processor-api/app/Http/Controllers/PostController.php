<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\PostStoreRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Models\User;
use App\Http\Models\Post;
use App\Http\Models\Like;
use App\Http\Models\Download;
use App\Http\Models\Comment;
use App\Http\Models\View;
use App\Http\Models\Uniquehashtag;
use App\Http\Models\ObjectTemplate;
use DB;

class PostController extends Controller
{

    public function getPostTypes($index)
    {

        $postTypes = [
            'Content-related',
            'Off-topic',
            'FAQ',
            'Technical',
            'Bug',
            'Feedback',
            'Announcement'
        ];

        $postTypes[20] = "All";

        return $postTypes[$index-1];
    }

    public function index()
    {
        $posts = Post::orderBy("created_at", "desc")->paginate(5);
        
        $postsByTopics = [
            "Content-related" => [],
            "Off-topic" => [],
            "FAQ" => [],
            "Technical" => [],
            "Bug" => [],
            "Feedback" => [],
            "Announcement" => [],
        ];
        
        $objectTemplateId = ObjectTemplate::where('title', 'post')->first()->id;
        foreach($posts as $singlePost)
        {
            $singlePost->likesTotal    = $this->getImpression("like", $objectTemplateId, $singlePost, "total");
            $singlePost->viewsTotal    = $this->getImpression("view", $objectTemplateId, $singlePost, "total");
            $singlePost->commentsTotal = $this->getImpression('comment', $objectTemplateId, $singlePost, 'total');
            $singlePost->hashtags      = $this->getUniquehashtags($singlePost->id, $objectTemplateId);
            $singlePost->userName = User::find($singlePost->user_id)->name;

            if($singlePost->type == 1) {
                $postsByTopics["Content-related"][] = $singlePost;
                $singlePost->postType = "Content-related";
            }
            else if ($singlePost->type == 2) {
                $postsByTopics["Off-topic"][] = $singlePost;
                $singlePost->postType = "Off-topic";
            }
            else if ($singlePost->type == 3) {
                $postsByTopics["FAQ"][] = $singlePost;
                $singlePost->postType = "FAQ";
            }
            else if ($singlePost->type == 4) {
                $postsByTopics["Technical"][] = $singlePost;
                $singlePost->postType = "Technical";
            }
            else if ($singlePost->type == 5) {
                $postsByTopics["Bug"][] = $singlePost;
                $singlePost->postType = "Bug";
            }
            else if ($singlePost->type == 6) {
                $postsByTopics["Announcement"][] = $singlePost;
                $singlePost->postType = "Announcement";
            }
        }

        if(isset($posts))
        {
            return response()->json([
                'success' => true,
                'posts' => $posts,
                'postsByTopic' => $postsByTopics
            ]);
        }
        return response()->json([
            'success' => false,
            'message' => 'There are no posts...'
        ]);
    }

    public function show($id)
    {
        $post = Post::find($id);

        if(!isset($post)){
            return response()->json([
                'success' => false, 'message' => 'Requested Post does not exist'
            ]);
        }

        $objectTemplateId = ObjectTemplate::where('title', 'post')->first()->id;
        $this->incrementView($post);
        $post->hashtags   = $this->getUniquehashtags($post->id, $objectTemplateId);
        $post->likesTotal = $this->getImpression("like", $objectTemplateId, $post, "total");
        $post->viewsTotal = $this->getImpression("view", $objectTemplateId, $post, "total");
        $post->comments   = $this->getImpression('comment', $objectTemplateId, $post, "all");
        $post->commentsTotal = count($post->comments);

        $objectTemplateId = ObjectTemplate::where('title', 'comment')->first()->id;
        foreach($post->comments as $comment)
        {
            $comment->likes = $this->getImpression('like', $objectTemplateId, $comment, "all");
            $comment->likesTotal = count($comment->likes);
            $comment->userName = User::find($comment->user_id)->name;
        }

        if($post->type == 1) {
            $post->postType = "Content-related";
        }
        else if ($post->type == 2) {
            $post->postType = "Off-topic";
        }
        else if ($post->type == 3) {
            $post->postType = "FAQ";
        }
        else if ($post->type == 4) {
            $post->postType = "Technical";
        }
        else if ($post->type == 5) {
            $post->postType = "Bug";
        }
        else if ($post->type == 6) {
            $post->postType = "Announcement";
        }

        $user = User::find($post->user_id);
        $post->userName = $user->name;
        $post->userId = $user->id;

        return response()->json([
            'success' => true,
            'post' => $post
        ]);
    }

    public function store(PostStoreRequest $request)
    {
        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }   

        $validated = $request->validated();

        $post = new Post;
        $post->user_id = auth()->user()->id;
        $post->title = $validated['title'];
        $post->content = $validated['content'];
        $post->type = $validated['type'];
        $post->save();

        $this->attachHashTags($request->tags, $post);

        $this->incrementView($post);

        return response()->json([
            'success' => true,
            'post' => $post
        ]);
    }

    public function update(Request $request, $id)
    {
        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }   
        $post = Post::find($id);
        
        if( !$post || $post->user_id != auth()->user()->id ){
            return response()->json([
                'message' => 'post doesnt exist or does not belong to the user'
            ]);
        } 

        $objectTemplateId = ObjectTemplate::where('title', 'post')->first()->id;

        if( isset($request->tags) )
        {
            $this->removeHashtags($post->id, $objectTemplateId, $request->tags);
            $this->attachHashTags($request->tags, $post);
        }
        if( isset($request->title) )
        {
            $post->title = $request->title;
        }
        if( isset($request->content) )
        {
            $post->content = $request->content;
        }
        if( isset($request->type) )
        {
            $post->type = $request->type;
        }

        $post->save();

        return response()->json([
            'success' => true,
            'updatedPost' => $post
        ]);
    }

    public function delete(Request $request, $id) {
        if(!auth()->user())
        {
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }   
        $post = Post::find($id);

        if( !$post || $post->user_id != auth()->user()->id && auth()->user()->hasRole("admin") == false ){
            return response()->json([
                'success' => false,
                'message' => 'post doesnt exist or does not belong to the user'
            ]);
        } 

        $this->removeImpressions($post);
        $objectTemplateId = ObjectTemplate::where('title', 'post')->first()->id;
        $this->removeHashtags($post->id, $objectTemplateId);

        $post->delete();

        return response()->json([
            'success' => true,
            'deleted_post' => $post,
        ]);
    }

    public function getPostImpressionsSearch($posts)
    {
        $objectTemplateId = ObjectTemplate::where('title', 'post')->first()->id;
        $jp_month = "月";
        $jp_day = "日";
        $jp_hour = "時";
        $jp_minute = "分";
        $jp_year = "年";
        foreach($posts as $singlePost)
        {
            $user = User::find($singlePost->user_id);
            $singlePost->userName = $user->name;
            $singlePost->userId = $user->id;

            if($singlePost->type == 1) {
                $postsByTopics["Content-related"][] = $singlePost;
                $singlePost->postType = "Content-related";
            }
            else if ($singlePost->type == 2) {
                $postsByTopics["Off-topic"][] = $singlePost;
                $singlePost->postType = "Off-topic";
            }
            else if ($singlePost->type == 3) {
                $postsByTopics["FAQ"][] = $singlePost;
                $singlePost->postType = "FAQ";
            }
            else if ($singlePost->type == 4) {
                $postsByTopics["Technical"][] = $singlePost;
                $singlePost->postType = "Technical";
            }
            else if ($singlePost->type == 5) {
                $postsByTopics["Bug"][] = $singlePost;
                $singlePost->postType = "Bug";
            }
            else if ($singlePost->type == 6) {
                $postsByTopics["Announcement"][] = $singlePost;
                $singlePost->postType = "Announcement";
            }

            $singlePost->jp_year   = $singlePost->created_at->year   . $jp_year;
            $singlePost->jp_month  = $singlePost->created_at->month  . $jp_month;
            $singlePost->jp_day    = $singlePost->created_at->day    . $jp_day;
            $singlePost->jp_hour   = $singlePost->created_at->hour   . $jp_hour;
            $singlePost->jp_minute = $singlePost->created_at->minute . $jp_minute;

            $singlePost->likesTotal = $this->getImpression("like", $objectTemplateId, $singlePost, "total");
            $singlePost->viewsTotal = $this->getImpression("view", $objectTemplateId, $singlePost, "total");
            $singlePost->commentsTotal = $this->getImpression('comment', $objectTemplateId, $singlePost, 'total');
            $singlePost->hashtags      = array_slice($this->getUniquehashtags($singlePost->id, $objectTemplateId), 0, 3);
        }

        return $posts;
    }

    public function sortByViewsTotal($objectsCollection, $objectTemplateId)
    {
        // sort by popularity aka impressions / views
        // PROBLEM: I need to count views totals and join those viewsTotals to each post as $post->viewsTotal
        // to loop each post I need to make it to array, but when it becomes array->get(); I cannot use paginate
        // To make results right, I need to get views before pagination to apply sort order for all results
        // $rawStatement = "SELECT posts.*, (SELECT COUNT(*) FROM views WHERE template_id = 9 AND real_object_id = posts.id) AS viewsTotal FROM posts ORDER BY viewsTotal DESC";

        $objectsCollection = $objectsCollection
            ->select('posts.*')
            ->leftJoin('views', 'posts.id', '=', 'views.real_object_id')
            ->where('views.template_id', '=', $objectTemplateId)
            ->addSelect(DB::raw('count(views.real_object_id) as viewsTotal'))
            ->groupBy('posts.id')
            ->orderBy('viewsTotal', 'desc');

        return $objectsCollection;
    }

    public function generateQuery(Request $request) 
    {
        $posts = new Post;
        $requestedQuery = "";
        if(isset( $request->keyword ))
        {
            $request->keyword = trim($request->keyword);
            $singleTag = explode(' ',trim($request->keyword))[0];

            $search = '#';

            if(preg_match("/{$search}/i", $singleTag)) {
                $posts = $this->getUniquehashtagPosts($singleTag);
                $requestedQuery .= $singleTag .". ";
            }

            else {
                $posts = Post::whereLike(['title', 'content'], $request->keyword);
                $requestedQuery .= "keyword: ".$request->keyword. ". ";
            }
        }
        
        if(isset( $request->filterType ) && $request->filterType != 20) // 20 = all
        {
            $posts = $posts->where('type', $request->filterType);
            $requestedQuery .= "Filter by ". $this->getPostTypes($request->filterType). ".";
        }

        if(isset( $request->sortByWhat ))
        {

            if( $request->sortByWhat === "new" ){
                $posts = $posts->orderBy('created_at', 'desc');
                $requestedQuery .= " Sort by Newest. ";
            }

            else if ($request->sortByWhat === "pop") {
                $objectTemplateId = ObjectTemplate::where('title', 'post')->first()->id;

                $posts = $this->sortByViewsTotal($posts, $objectTemplateId);
                $requestedQuery .= " Sort by Popular. ";
            }
        }
        
        $posts = $posts->paginate(5);

        $posts = $this->getPostImpressionsSearch($posts);

        return response()->json([
            'success' => true,
            'posts' => $posts,
            'requestedQuery' => $requestedQuery
        ]);
    }

    #=================================== Impressions

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
    }

    public function removeImpressions($object)
    {
        $objectTemplateId = ObjectTemplate::where('title', 'post')->first()->id;
        $likes = Like::where("template_id", $objectTemplateId)->where("real_object_id", $object->id)->delete();
        $views = View::where("template_id", $objectTemplateId)->where("real_object_id", $object->id)->delete();

        $comments = Comment::where("template_id", $objectTemplateId)->where("real_object_id", $object->id)->get();
        $objectTemplateId = ObjectTemplate::where('title', 'comment')->first()->id;
        foreach($comments as $comment){
            $commentLikes = Like::where("template_id", $objectTemplateId)->where('real_object_id', $comment->id)->delete();
            $comment->delete();
        }
    }

    public function incrementView(Post $post)
    {
        if( !auth()->user() ){
            return response()->json([
                'success' => true,
                'message' => "User unauthenticated, no views counted"
            ]);
        }

        $objectTemplateId = ObjectTemplate::where('title', 'post')->first()->id;
        $checkView = View::where([
            'template_id' => $objectTemplateId,
            'real_object_id' => $post->id,
            'user_id' => auth()->user()->id
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
            $view->real_object_id = $post->id;
            $view->save();
        }
    }

    public function toggleLock($id)
    {
        $post = Post::find($id);

        if($post->locked == 1)
        {
            $post->locked = 0;
            $post->update();
            return response()->json([
                'success' => true,
                'message' => 'Post of id: '.$id. ' was unlocked',
                'locked' => $post->locked
            ]);
        }
        else {
            $post->locked = 1;
            $post->update();
            return response()->json([
                'success' => true,
                'message' => 'Post of id: '.$id. ' was locked',
                'locked' => $post->locked
            ]);
        }
    }

    public function storeComment(Request $request, $id, $parentCommentId = null)
    {
        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }

        if(Post::find($id)->locked == 1)
        {
            return response()->json([
                'success' => false,
                'message' => 'Post is locked from commenting'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|min:2|max:1000',
        ]);
        
        if($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $objectTemplateId = ObjectTemplate::where('title', 'post')->first()->id;
        
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
            'message' => 'You commented post of id: '.$id,
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

    public function unlikePost($id) {
        $objectTemplateId = ObjectTemplate::where('title', 'post')->first()->id;
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

    public function likePost($id) {
        if(!auth()->user()){
            return response()->json([
                'message' => 'you are not a user'
            ]);
        }
        $objectTemplateId = ObjectTemplate::where('title', 'post')->first()->id;

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
            'message' => 'You liked Post of id: '.$id,
            'like' => $like
        ]);
    }

    public function checkIfLikedPost($id) {
        $objectTemplateId = ObjectTemplate::where('title', 'post')->first()->id;

        $checkLike = Like::where([
            'template_id' => $objectTemplateId,
            'real_object_id' => $id,
            'user_id' => auth()->user()->id
        ])->first();
        
        if($checkLike) {
            return response()->json([
                'userId' => auth()->user()->id,
                'isLiked' => true,
                'message' => 'you already liked this post'
            ]);
        }

        return response()->json([
            'userId' => auth()->user()->id,
            'isLiked' => false,
            'message' => 'you havent liked the post yet'
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

    public function getUniquehashtagPosts($wantedTag)
    {
        $objectTemplateId = ObjectTemplate::where('title', 'post')->first()->id;
        // get tag which was input id
        $uniqueTag = Uniquehashtag::where("content", $wantedTag)->first();
        if( !isset( $uniqueTag )) {
            return null;
        }
        // get all hashtag foreign table rows
        $foundRows = DB::table('hashtags')->where('uniquehashtag_id', $uniqueTag->id)
            ->where('template_id', $objectTemplateId)->get();

        $ids = [];
        // get all posts with that tag id
        foreach($foundRows as $postlink)
        {
            $ids[] = $postlink->real_object_id;
        }
        
        $posts = Post::whereIn('id', $ids);

        return $posts;
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
        $objectTemplateId = ObjectTemplate::where('title', 'post')->first()->id;

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
