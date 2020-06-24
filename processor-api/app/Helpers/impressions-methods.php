<?php 

use App\Http\Models\Like;
use App\Http\Models\Download;
use App\Http\Models\View;
use App\Http\Models\Comment;
use App\Http\Models\ObjectTemplate;

function getImpression($impressionType, $objectTemplateId, $object, $amount)
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

function removeImpressions($object, $objectTemplateId)
{
    // $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
    $likes = Like::where("template_id", $objectTemplateId)->where("real_object_id", $object->id)->delete();
    $views = View::where("template_id", $objectTemplateId)->where("real_object_id", $object->id)->delete();

    $comments = Comment::where("template_id", $objectTemplateId)->where("real_object_id", $object->id)->get();
    $objectTemplateId = ObjectTemplate::where('title', 'comment')->first()->id;
    foreach($comments as $comment){
        $commentLikes = Like::where("template_id", $objectTemplateId)->where('real_object_id', $comment->id)->delete();
        $comment->delete();
    }
}

function incrementView($object, $objectTemplateId)
{
    if( !auth()->user() ){
        return response()->json([
            'success' => true,
            'message' => "User unauthenticated, no views counted"
        ]);
    }

    $checkView = View::where([
        'template_id' => $objectTemplateId,
        'real_object_id' => $object->id,
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
        $view->real_object_id = $object->id;
        $view->save();
    }
}

?>