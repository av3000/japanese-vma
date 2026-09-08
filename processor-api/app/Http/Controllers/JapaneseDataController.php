<?php

namespace App\Http\Controllers;

use App\Http\Models\Comment;
use App\Http\Models\CustomList;
use App\Http\Models\Kanji;
use App\Http\Models\Like;
use App\Http\Models\ObjectTemplate;
use App\Http\Models\Sentence;
use App\Http\Models\Word;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class JapaneseDataController extends Controller
{
    public function storeComment(Request $request, $id, $parentCommentId = null)
    {
        if (! auth()->user()) {
            return response()->json([
                'message' => 'you are not a user',
            ]);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|min:2|max:1000',
        ]);

        if ($validator->fails()) {
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
            'comment' => $comment,
        ]);
    }

    public function deleteComment($id, $commentid)
    {
        if (! auth()->user()) {
            return response()->json([
                'message' => 'you are not a user',
            ]);
        }

        $comment = Comment::where([
            'id' => $commentid,
            'user_id' => auth()->user()->id,
        ])->first();

        if (isset($comment)) {
            $objectTemplateId = ObjectTemplate::where('title', 'comment')->first()->id;
            $commentLikes = Like::where('template_id', $objectTemplateId)->where('real_object_id', $commentid)->delete();

            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => 'comment was deleted',
            ]);
        } elseif (! isset($comment) && auth()->user()->hasRole('admin') == true) {
            $comment = Comment::where([
                'id' => $commentid,
            ])->first();

            $objectTemplateId = ObjectTemplate::where('title', 'comment')->first()->id;
            $commentLikes = Like::where('template_id', $objectTemplateId)->where('real_object_id', $commentid)->delete();

            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => 'comment was deleted by admin',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Comment does not belong to user or comment doesnt exist',
            ]);
        }
    }

    public function updateComment(Request $request, $id, $commentid)
    {
        if (! auth()->user()) {
            return response()->json([
                'message' => 'you are not a user',
            ]);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|min:2|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $comment = Comment::where([
            'id' => $commentid,
            'user_id' => auth()->user()->id,
        ])->first();

        if (isset($comment)) {
            $comment->content = $request->get('content');
            $comment->updated_at = date('Y-m-d H:i:s');
            $comment->update();

            return response()->json([
                'success' => true,
                'message' => 'comment was updated',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Comment does not belong to user or comment doesnt exist',
            ]);
        }
    }

    public function likeComment($id, $commentid)
    {
        if (! auth()->user()) {
            return response()->json([
                'message' => 'you are not a user',
            ]);
        }
        $objectTemplateId = ObjectTemplate::where('title', 'comment')->first()->id;

        $checkLike = Like::where([
            'template_id' => $objectTemplateId,
            'real_object_id' => $commentid,
            'user_id' => auth()->user()->id,
        ])->first();

        if ($checkLike) {
            return response()->json([
                'message' => 'you cannot like the comment twice!',
            ]);
        }

        $like = new Like;
        $like->user_id = auth()->user()->id;
        $like->template_id = $objectTemplateId;
        $like->real_object_id = $commentid;
        $like->value = 1;
        $like->save();

        return response()->json([
            'success' => true,
            'message' => 'You liked comment of id: '.$commentid,
            'like' => $like,
        ]);
    }

    public function unlikeComment($id, $commentid)
    {
        $objectTemplateId = ObjectTemplate::where('title', 'comment')->first()->id;
        $like = Like::where([
            'template_id' => $objectTemplateId,
            'real_object_id' => $commentid,
            'user_id' => auth()->user()->id,
        ]);

        $like->delete();

        return response()->json([
            'success' => true,
            'message' => 'like was deleted',
        ]);
    }

    public function storeSentence(Request $request)
    {
        if (! auth()->user()) {
            return response()->json([
                'message' => 'you are not a user',
            ]);
        }

        $rules = [
            'content' => 'required|string|min:4|max:300',
            'content_en' => 'string|max:300',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 201);
        }

        $sentence = new Sentence;
        $sentence->user_id = auth()->user()->id;
        if (isset($request->content_en)) {
            $sentence->content_en = $request->content_en;
        } else {
            $sentence->content_en = '';
        }
        $sentence->content = $request->content;
        $sentence->save();

        $kanjiResponse = $this->getKanjiIdsFromText($sentence);
        $wordResponse = $this->getWordIdsFromText($sentence);

        return response()->json([
            'success' => true,
            'sentence' => $sentence,
            'kanjis' => $kanjiResponse,
            'words' => $wordResponse,
        ]);
    }

    public function updateSentence(Request $request, $id)
    {
        if (! auth()->user()) {
            return response()->json([
                'message' => 'you are not a user',
            ]);
        }

        $rules = [
            'content' => 'required|string|min:4|max:300',
            'content_en' => 'string|max:300',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 201);
        }

        $sentence = Sentence::find($id);
        if (isset($request->content_en)) {
            $sentence->content_en = $request->content_en;
        } else {
            $sentence->content_en = '';
        }
        $sentence->content = $request->content;
        $sentence->save();

        $sentence->kanjis()->wherePivot('sentence_id', $sentence->id)->detach();
        $sentence->words()->wherePivot('sentence_id', $sentence->id)->detach();

        $kanjiResponse = $this->getKanjiIdsFromText($sentence);
        $wordResponse = $this->getWordIdsFromText($sentence);

        return response()->json([
            'success' => true,
            'updated_sentence' => $sentence,
            'reattached_kanjis' => $kanjiResponse,
            'reattached_words' => $wordResponse,
        ]);
    }

    public function deleteSentence(Request $request, $id)
    {
        if (! auth()->user()) {
            return response()->json([
                'message' => 'you are not a user',
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
     *
     * @return charArray
     */
    public function mb_str_split(string $string, $split_length = 1)
    {
        if ($split_length == 1) {
            return preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);
        } elseif ($split_length > 1) {
            $return_value = [];
            $string_length = mb_strlen($string, 'UTF-8');
            for ($i = 0; $i < $string_length; $i += $split_length) {
                $return_value[] = mb_substr($string, $i, $split_length, 'UTF-8');
            }

            return $return_value;
        } else {
            return false;
        }
    }

    /**
     * @param object Sentence
     */
    public function getKanjiIdsFromText(Sentence $sentence)
    {
        $raw_text = $this->mb_str_split($sentence->content);
        $total = count($raw_text);
        $foundKanjis = [];
        $index = 0;
        while ($index < $total) {
            $kanji = Kanji::where('kanji', 'like', $raw_text[$index])->first();
            if (isset($kanji) && ! in_array($kanji->id, $foundKanjis, true)) {
                array_push($foundKanjis, $kanji->id);
                // echo "<p>kanji found: " .$kanji->kanji. "</p>";
                $sentence->kanjis()->attach($kanji);
            }
            $index++;
        }

        if (count($foundKanjis) == 0) {
            return response()->json(['success' => false, 'kanji_message' => 'There was no kanji characters in sentence text...']);
        }

        return response()->json(['success' => true, 'kanji_message' => 'Kanji characters were attached to the sentence!']);
    }

    /**
     * @param object Sentence
     */
    public function getWordIdsFromText(Sentence $sentence)
    {
        $testString = str_replace(["\n", "\r", ' '], '', $article->content);
        $fullText = $this->mb_str_split($testString);
        $duplicateArray = [];
        $len = count($fullText);
        $cursorStart = 0;
        $cursor = 0;
        $tempWord = '';
        $refreshStop = 0;

        while ($cursorStart < $len) {
            // Makes sure if there is more text to process.
            if (isset($fullText[$cursor])) {
                $tempWord .= $fullText[$cursor];
                $potentialWords = Word::where('word', 'like', $tempWord.'%')->get();
            }
            if (isset($fullText[$cursor + 1])) {
                $tempWordNext = $tempWord.$fullText[$cursor + 1];
                $potentialWordsNext = Word::where('word', 'like', $tempWordNext.'%')->get();
            }
            // Exception 1:
            // When $potentialWords have results, but $potentialWordsNext reached zero, it means that we can begin actual word recognition.
            if (count($potentialWords) >= 1 && count($potentialWordsNext) == 0) {
                $matchOk = 0;
                $moveBack = 0;
                $potentialLost = true;
                while ($matchOk == 0) {
                    // keep going back one char at the time until our EQUAL query will find the word.
                    // or the potentialWords will be wasted.
                    $fetchWordAtTheTime = Word::where('word', $tempWord)->first();
                    if (isset($fetchWordAtTheTime)) {
                        if (in_array($fetchWordAtTheTime->word, $duplicateArray) == false) {
                            array_push($duplicateArray, $fetchWordAtTheTime->word);
                            $sentence->words()->attach($fetchWordAtTheTime);
                        }
                        $tempWord = '';
                        $matchOk = 1;
                        break;
                    } elseif ($tempWord == '') {
                        $matchOk = 1;
                        $potentialLost = false;
                        break;
                    }
                    $moveBack++;
                    $tempWord = mb_substr($tempWord, 0, mb_strlen($tempWord) - 1, 'utf-8');
                }
                // need to check if cursor moved back and minus additional steps so that some text wouldn't be lost.
                if ($potentialLost == false) {
                    $cursorStart = $cursor + 1;
                } else {
                    $cursorStart = $cursor + 1 - $moveBack;
                    $cursor -= $moveBack;
                }
            }
            // Exception 2:
            // Case, when some unwanted symbols get in the way
            // To get rid of it, we refresh $tempWord to empty, without that current unwanted symbol.
            elseif (count($potentialWords) == 0 && count($potentialWordsNext) == 0) {
                $cursorStart = $cursor + 1;
                $tempWord = '';
            }
            //Exception 3:
            // Rare Case, when we still have lost of LIKE potential, but the cursor hits the wall.
            // Need to force the EQUAL querying with minus 1char at the time.
            elseif (count($potentialWords) >= 1 && count($potentialWordsNext) >= 1 && $cursor >= $len - 1) {
                $refreshStop = 1;
                $matchOk = 0;
                $moveBack = 0;
                $potentialLost = true;
                while ($matchOk == 0) {
                    // keep going back one char at the time until our EQUAL query will find the word.
                    // or the potentialWords will be wasted.
                    $fetchWordAtTheTime = Word::where('word', $tempWordNext)->first();
                    if (isset($fetchWordAtTheTime)) {
                        if (in_array($fetchWordAtTheTime->word, $duplicateArray) == false) {
                            array_push($duplicateArray, $fetchWordAtTheTime->word);
                            $sentence->words()->attach($fetchWordAtTheTime);
                        }
                        $tempWord = '';
                        $matchOk = 1;
                        break;
                    } elseif ($tempWordNext == '') {
                        $tempWord = '';
                        $matchOk = 1;
                        $potentialLost = false;
                        break;
                    }
                    $moveBack++;
                    $tempWordNext = mb_substr($tempWordNext, 0, mb_strlen($tempWordNext) - 1, 'utf-8');
                }
                // need to check if cursor moved back and minus additional steps so that some text wouldn't be lost.
                if ($potentialLost == false) {
                    $cursorStart = $cursor + 1;
                } else {
                    $cursorStart = $cursor + 1 - $moveBack;
                    $cursor -= $moveBack;
                }
            }
            // Exception 4:
            // if somehow cursor hits the end of the text, we need to reset and increment the cursors
            if ($cursor >= $len - 1 && $refreshStop == 0) {
                $cursorStart++;
                $cursor = $cursorStart;
                $tempWord = '';
            }
            // index will increase and won't be modified in the first IF statement
            // if none of the exceptions has been entered
            // So, which means that $cursorStart - is still beginning of the word
            // and $cursor is the ending of the word and it takes +1 step to the upcoming word.
            $cursor++;
        }

        if (count($duplicateArray) == 0) {
            return response()->json([
                'success' => false,
                'word_message' => 'There was no words found in the sentence text...',
            ]);
        }

        return response()->json(['success' => true, 'word_message' => 'Words were attached to the sentence!']);
    }

    public function checkIfBelongToList($itemId, $list)
    {
        $foundRows = DB::table('customlist_object')->where('list_id', $list->id)->get();
        foreach ($foundRows as $row) {
            if ($row->entity_id == $itemId) {
                return true;
            }
        }

        return false;
    }

    public function getUserListAndCheckIfListHasItem(Request $request)
    {
        $objects = $request->get('objects');
        $listTypeId = $request->get('listTypeId');

        return response()->json([
            'objects' => $objects,
        ]);

        if (auth()->user() !== null) {
            $list = CustomList::where('user_id', auth()->user()->id)->where('type', $listTypeId)->first();
            if (! isset($list)) {
                return response()->json([
                    'success' => false,
                    'message' => 'list not found',
                ]);
            }

            foreach ($objects as $object) {
                $object->isLearned = $this->checkIfBelongToList($object->id, $list);
            }
        }

        return $objects;
    }
}
