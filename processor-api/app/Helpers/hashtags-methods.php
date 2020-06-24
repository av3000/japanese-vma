<?php 

use Illuminate\Support\Facades\DB;
use App\Http\Models\ObjectTemplate;
use App\Http\Models\Uniquehashtag;

function getHashtags($string) {  
    $hashtags= FALSE;  
    preg_match_all("/(#\w+)/u", $string, $matches);  
    if ($matches) {
        $hashtagsArray = array_count_values($matches[0]);
        $hashtags = array_keys($hashtagsArray);
    }
    return $hashtags;
}

function removeHashtags($id, $objectTemplateId)
{
    $oldTags = DB::table('hashtags')
        ->where('template_id', $objectTemplateId)
        ->where('real_object_id', $id)
        ->delete();
}

function attachHashTags($tags, $object, $objectTemplateId)
{
    $tags = getHashtags($tags);
    $tags = checkIfHashtagsAreUnique($tags);
    // $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

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

function getUniquehashtags($id, $objectTemplateId)
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

function checkIfHashtagsAreUnique($tags)
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

?>