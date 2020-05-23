<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\PostStoreRequest;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\Post;
use App\Like;
use App\Download;
use App\Comment;
use App\View;
use App\Uniquehashtag;
use App\ObjectTemplate;
use DB;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::all();

        $objectTemplateId = ObjectTemplate::where('title', 'post')->first()->id;
        foreach($posts as $singlePost)
        {
            $singlePost->likesTotal    = $this->getImpression("like", $objectTemplateId, $singlePost, "total");
            $singlePost->viewsTotal    = $this->getImpression("view", $objectTemplateId, $singlePost, "total");
            $singlePost->commentsTotal = $this->getImpression('comment', $objectTemplateId, $singlePost, 'total');
            $singlePost->hashtags      = $this->getUniquehashtags($singlePost->id, $objectTemplateId);
        }

        if(isset($posts))
        {
            return response()->json([
                'success' => true,
                'posts' => $posts
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
        $post->type = $this->getPostTypes( $validated['type']);
        $post->save();

        $this->attachHashTags($request->tags, $post);

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
            $post->type = $this->getPostTypes( $request->type);
        }

        $post->update();

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

        if( !$post || $post->user_id != auth()->user()->id ){
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

    public function getPostTypes($typeName)
    {

        $postTypes = [
            'Content-related' => 1,
            'Off-topic' => 2,
            'FAQ' => 3,
            'Technical' => 4,
            'Bug' => 5,
            'Feedback' => 6,
            'Annoucement' => 7
        ];

        return $postTypes[$typeName];
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
                'message' => 'Post of id: '.$id. ' was unlocked'
            ]);
        }
        else {
            $post->locked = 1;
            $post->update();
            return response()->json([
                'success' => true,
                'message' => 'Post of id: '.$id. ' was locked'
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
            $objectTemplateId = ObjectTemplate::where('title', 'post')->first()->id;
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
