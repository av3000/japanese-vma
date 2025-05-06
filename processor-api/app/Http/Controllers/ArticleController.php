<?php

namespace App\Http\Controllers;

use App\Http\Models\Article;
use App\Http\Models\Comment;
use App\Http\Models\Download;
use App\Http\Models\Like;
use App\Http\Models\ObjectTemplate;
use App\Http\Models\Uniquehashtag;
use App\Http\Models\Word;
use App\Http\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use PDF;

class ArticleController extends Controller
{
    // TODO: Move const enums to proper location
    const KNOWNRADICALS = 1;

    const KNOWNKANJIS = 2;

    const KNOWNWORDS = 3;

    const KNOWNSENTENCES = 4;

    const RADICALS = 5;

    const KANJIS = 6;

    const WORDS = 7;

    const SENTENCES = 8;

    const ARTICLES = 9;

    const LYRICS = 10;

    const ARTISTS = 11;

    const ARTICLE_STATUS_TYPES =
        [
            'pending' => 0,
            'reviewing' => 1,
            'rejected' => 2,
            'approved' => 3,
        ];

    public function __constructor()
    {

    }

    public function getArticleJlptTypes($index)
    {
        $articleJlptTypes = [
            'N1',
            'N2',
            'N3',
            'N4',
            'N5',
            'Uncommon',
        ];

        $articleJlptTypes[20] = 'All';

        return $articleJlptTypes[$index - 1];
    }

    /**
     * GET /api/articles
     * Returns paginated list of published articles
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $query = Article::query()->where('publicity', 1);

        // Add filters conditionally
        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->has('search')) {
            $query->where('title', 'LIKE', '%' . $request->search . '%');
        }

        $sortDir = $request->has('sort_dir') ? $request->sort_dir : 'DESC';

        $sortBy = $request->has('sort_by') ? $request->sort_by : 'created_at';

        $perPage = $request->has('per_page') ? (int)$request->per_page : 4;

        $articles = $query->orderBy($sortBy, $sortDir)->paginate($perPage);

        // $articles = Article::where('publicity', 1)->orderBy('created_at', 'DESC')->paginate(3);
        // where('status', $this->ARTICLE_STATUS_TYPES['approved'])->

        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

        foreach ($articles as $singleArticle) {
            $singleArticle->likesTotal = getImpression('like', $objectTemplateId, $singleArticle, 'total');
            $singleArticle->downloadsTotal = getImpression('download', $objectTemplateId, $singleArticle, 'total');
            $singleArticle->viewsTotal = getImpression('view', $objectTemplateId, $singleArticle, 'total');
            $singleArticle->commentsTotal = getImpression('comment', $objectTemplateId, $singleArticle, 'total');
            $singleArticle->hashtags = getUniquehashtags($singleArticle->id, $objectTemplateId);
        }

        if (!isset($articles)) {
            return response()->json([
                'success' => false,
                'message' => 'There are no articles...',
            ]);
        }

        return response()->json([
            'test' => true,
            'success' => true,
            'articles' => $articles,
            'message' => 'articles fetched',
            'imagePath' => getArticleImageFromImages('testing-image.jpg'),
        ]);
    }

    public function show($id)
    {
        $article = Article::find($id);

        if (! isset($article)) {
            return response()->json([
                'success' => false, 'message' => 'Requested article does not exist',
            ]);
        }

        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        incrementView($article, $objectTemplateId);

        $article->likes = getImpression('like', $objectTemplateId, $article, 'all');
        $article->likesTotal = count($article->likes);
        $article->downloadsTotal = getImpression('download', $objectTemplateId, $article, 'total');
        $article->viewsTotal = getImpression('view', $objectTemplateId, $article, 'total');
        $article->comments = getImpression('comment', $objectTemplateId, $article, 'all');
        $article->commentsTotal = count($article->comments);
        $article->hashtags = getUniquehashtags($article->id, $objectTemplateId);

        $objectTemplateId = ObjectTemplate::where('title', 'comment')->first()->id;
        foreach ($article->comments as $comment) {
            $comment->likes = getImpression('like', $objectTemplateId, $comment, 'all');
            $comment->likesTotal = count($comment->likes);
            $comment->userName = User::find($comment->user_id)->name;
        }

        $article->jlptcommon = 0;

        // TODO: Move kanjis and words extraction for PDF click action to separate endpoint.
        $article->words = extractWordsListAttributes($article->words()->get());
        $article->kanjis = $article->kanjis()->get();
        foreach ($article->kanjis as $kanji) {
            if ($kanji->jlpt == '-') {
                $article->jlptcommon++;
            }
        }
        $article->kanjiTotal = intval($article->n1) + intval($article->n2) + intval($article->n3) + intval($article->n4) + intval($article->n5) + $article->jlptcommon;

        $user = User::find($article->user_id);
        $article->userName = $user->name;
        $article->userId = $user->id;

        //           Method 1:
        // Furigana battle. Display furigana above text functionality
        // $article->wordsWithFurigana = [
        //     array($recognizedWordFromContentJp, $wordFromArticleWords)
        //     array($recognizedWordFromContentJp, $wordFromArticleWords)
        //     array($recognizedWordFromContentJp, $wordFromArticleWords)
        //     ...
        // ]
        //  Seems like we will need to save straight after WordsExtracting
        // into DB table "articles.content_furi"
        //           Method 2:
        // display ONLY furigana, without trying to find each of content_jp word place in text.
        return response()->json([
            'success' => true,
            'article' => $article,
        ]);
    }

    /**
     * POST /api/article
     * Creates a new article
     * @param ArticleStoreRequest $request
     * @return JsonResponse
     */
    public function store(ArticleStoreRequest $request)
    {
        // Get validated data
        $validated = $request->validated();

        // Create article and fill basic properties
        $article = new Article;
        $article->user_id = auth()->id();
        $article->title_jp = $validated['title_jp'];
        $article->title_en = $validated['title_en'] ?? '';
        $article->content_jp = $validated['content_jp'];
        $article->content_en = $validated['content_en'] ?? '';
        $article->source_link = $validated['source_link'];
        $article->publicity = $validated['publicity'] ?? 0;
        $article->save();

        // Get object template ID
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

        // Attach hashtags
        if (isset($validated['tags'])) {
            attachHashTags($validated['tags'], $article, $objectTemplateId);
        }

        // Process kanji attachments if requested
        $attachData = [];
        if (isset($validated['attach']) && $validated['attach'] == 1) {
            $kanjiResponse = getKanjiIdsFromText($article);
            $kanjis = $article->kanjis()->get();

            // Count and update JLPT levels
            foreach ($kanjis as $kanji) {
                $jlptLevel = $kanji->jlpt;
                if (in_array($jlptLevel, ['1', '2', '3', '4', '5'])) {
                    $field = 'n' . $jlptLevel;
                    $article->$field = intval($article->$field) + 1;
                } else {
                    $article->uncommon = intval($article->uncommon) + 1;
                }
            }

            $article->update();
            $attachData = [
                'attach' => $validated['attach'],
                'kanjis' => $kanjiResponse,
            ];
        }

        // Increment view counter
        incrementView($article, $objectTemplateId);

        // Return response
        return response()->json([
            'success' => true,
            'article' => $article,
            'message' => 'Article created successfully',
        ] + $attachData, 201);
    }

    /**
     * PUT /api/article/{id}
     * Updates an existing article
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        if (! auth()->user()) {
            return response()->json([
                'message' => 'you are not a user',
            ]);
        }

        $article = Article::find($id);

        if (! $article || $article->user_id != auth()->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'article doesnt exist or does not belong to the user',
            ]);
        }
        if (isset($request->title_jp)) {
            $article->title_jp = $request->title_jp;
        }
        if (isset($request->title_en)) {
            $article->title_en = $request->title_en;
        }
        if (isset($request->content_en)) {
            $article->content_en = $request->content_en;
        }
        if (isset($request->content_jp)) {
            $article->content_jp = $request->content_jp;
        }
        if (isset($request->source_link)) {
            $article->source_link = $request->source_link;
        }
        if (isset($request->status)) {
            $article->status = $request->status;
        }
        if (isset($request->publicity)) {
            $article->publicity = $request->publicity;
        }

        $article->save();

        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

        if (isset($request->tags)) {
            removeHashtags($article->id, $objectTemplateId, $request->tags);
            attachHashTags($request->tags, $article, $objectTemplateId);
        }

        if ($request->reattach == 1) {
            // die("I should not have been here"); debugging
            $article->kanjis()->wherePivot('article_id', $article->id)->detach();
            $article->words()->wherePivot('article_id', $article->id)->detach();

            $kanjiResponse = getKanjiIdsFromText($article);
            $wordResponse = getWordIdsFromText($article);

            return response()->json([
                'success' => true,
                'reattach' => $request->reattach,
                'updated_article' => $article,
                'reattached_kanjis' => $kanjiResponse,
                'reattached_words' => $wordResponse,
            ]);
        }

        return response()->json([
            'success' => true,
            'reattach' => $request->reattach,
            'updated_article' => $article,
            'reattached_kanjis' => 'none',
            'reattached_words' => 'none',
        ]);
    }

    /**
     * DELETE /api/article/{id}
     * Deletes an article
     * @param int $id
     * @return JsonResponse
     */
    public function delete(Request $request, $id)
    {
        if (! auth()->user()) {
            return response()->json([
                'message' => 'you are not a user',
            ]);
        }
        $article = Article::find($id);

        if (! $article || $article->user_id != auth()->user()->id && auth()->user()->hasRole('admin') == false) {
            return response()->json([
                'success' => false,
                'message' => 'article doesnt exist or does not belong to the user',
            ]);
        }

        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

        $article->kanjis()->wherePivot('article_id', $article->id)->detach();
        $article->words()->wherePivot('article_id', $article->id)->detach();
        removeImpressions($article, $objectTemplateId);

        removeHashtags($article->id, $objectTemplateId);
        $this->removeArticleFromLists($article->id);

        $article->delete();

        return response()->json([
            'success' => true,
            'deleted_article' => $article,
        ]);
    }

    public function removeArticleFromLists($id)
    {
        DB::table('customlist_object')
            ->where('real_object_id', $id)
            ->where('listtype_id', self::ARTICLES)
            ->delete();
    }

    /**
     * GET /api/user/articles
     * Returns current user's articles
     * @return JsonResponse
     */
    public function getUserArticles()
    {
        $articles = Article::where('user_id', auth()->user()->id)->get();

        // $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $jp_month = '月';
        $jp_day = '日';
        $jp_hour = '時';
        $jp_minute = '分';
        $jp_year = '年';
        foreach ($articles as $singleArticle) {
            $singleArticle->jp_year = $singleArticle->created_at->year.$jp_year;
            $singleArticle->jp_month = $singleArticle->created_at->month.$jp_month;
            $singleArticle->jp_day = $singleArticle->created_at->day.$jp_day;
            $singleArticle->jp_hour = $singleArticle->created_at->hour.$jp_hour;
            $singleArticle->jp_minute = $singleArticle->created_at->minute.$jp_minute;

            $singleArticle->likesTotal = getImpression('like', $objectTemplateId, $singleArticle, 'total');
            $singleArticle->downloadsTotal = getImpression('download', $objectTemplateId, $singleArticle, 'total');
            $singleArticle->viewsTotal = getImpression('view', $objectTemplateId, $singleArticle, 'total');
            $singleArticle->commentsTotal = getImpression('comment', $objectTemplateId, $singleArticle, 'total');
            $singleArticle->hashtags = array_slice(getUniquehashtags($singleArticle->id, $objectTemplateId), 0, 3);
        }

        if (isset($articles)) {
            return response()->json([
                'success' => true, 'articles' => $articles,
            ]);
        }

        return response()->json([
            'success' => false, 'message' => 'Requested Article does not exist or User has no articles',
        ]);
    }

    public function articleKanjis($id)
    {
        $articleKanjis = Article::find($id)->kanjis()->get();

        if (isset($articleKanjis)) {
            return response()->json([
                'success' => true, 'articleKanjis' => $articleKanjis,
            ]);
        }

        return response()->json([
            'success' => false, 'message' => 'Requested Article does not have kanjis',
        ]);
    }

    public function articleWords($id)
    {
        $articleWords = Article::find($id)->words()->get();

        if (isset($articleWords)) {
            return response()->json([
                'success' => true, 'articleWords' => $articleWords,
            ]);
        }

        return response()->json([
            'success' => false, 'message' => 'Requested Article does not have words',
        ]);
    }

    public function getArticleImpressionsSearch($articles)
    {
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $jp_month = '月';
        $jp_day = '日';
        $jp_hour = '時';
        $jp_minute = '分';
        $jp_year = '年';
        foreach ($articles as $singleArticle) {
            $singleArticle->jp_year = $singleArticle->created_at->year.$jp_year;
            $singleArticle->jp_month = $singleArticle->created_at->month.$jp_month;
            $singleArticle->jp_day = $singleArticle->created_at->day.$jp_day;
            $singleArticle->jp_hour = $singleArticle->created_at->hour.$jp_hour;
            $singleArticle->jp_minute = $singleArticle->created_at->minute.$jp_minute;

            $singleArticle->likesTotal = getImpression('like', $objectTemplateId, $singleArticle, 'total');
            $singleArticle->downloadsTotal = getImpression('download', $objectTemplateId, $singleArticle, 'total');
            $singleArticle->viewsTotal = getImpression('view', $objectTemplateId, $singleArticle, 'total');
            $singleArticle->commentsTotal = getImpression('comment', $objectTemplateId, $singleArticle, 'total');
            $singleArticle->hashtags = array_slice(getUniquehashtags($singleArticle->id, $objectTemplateId), 0, 3);
        }

        return $articles;
    }

    public function sortByViewsTotal($objectsCollection, $objectTemplateId)
    {
        // sort by popularity aka impressions / views
        // PROBLEM: I need to count views totals and join those viewsTotals to each post as $post->viewsTotal
        // to loop each post I need to make it to array, but when it becomes array->get(); I cannot use paginate
        // To make results right, I need to get views before pagination to apply sort order for all results
        // $rawStatement = "SELECT articles.*, (SELECT COUNT(*) FROM views WHERE template_id = 9 AND real_object_id = articles.id) AS viewsTotal FROM articles ORDER BY viewsTotal DESC";

        $objectsCollection = $objectsCollection
            ->select('articles.*')
            ->leftJoin('views', 'articles.id', '=', 'views.real_object_id')
            ->where('views.template_id', '=', $objectTemplateId)
            ->addSelect(DB::raw('count(views.real_object_id) as viewsTotal'))
            ->groupBy('articles.id')
            ->orderBy('viewsTotal', 'desc');

        return $objectsCollection;
    }

    public function generateQuery(Request $request)
    {
        $articles = new Article;
        $requestedQuery = '';
        if (isset($request->keyword)) {
            $request->keyword = trim($request->keyword);
            $singleTag = explode(' ', trim($request->keyword))[0];

            $search = '#';

            if (preg_match("/{$search}/i", $singleTag)) {
                $articles = $this->getUniquehashtagArticles($singleTag);
                $requestedQuery .= $singleTag.'. ';
            } else {
                $articles = Article::whereLike(['title_jp', 'content_jp'], $request->keyword)->where('publicity', 1)->where('status', '3');
                $requestedQuery .= 'keyword: '.$request->keyword.'. ';
            }
        }

        if (isset($request->sortByWhat)) {
            if ($request->sortByWhat === 'new') {
                $articles = $articles->orderBy('created_at', 'desc')->where('publicity', 1)->where('status', '3');
                $requestedQuery .= ' Sort by Newest. ';
            } elseif ($request->sortByWhat === 'pop') {
                $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

                $articles = $this->sortByViewsTotal($articles, $objectTemplateId)->where('publicity', 1)->where('status', '3');
                $requestedQuery .= ' Sort by Popular. ';
            }
        }

        if (isset($request->filterType) && $request->filterType != 20) { // 20 = all
            $articles = $articles->where($this->getArticleJlptTypes($request->filterType), '>', 0)->where('publicity', 1)->where('status', '3'); //->orderBy($this->getArticleJlptTypes($request->filterType), 'desc')
            $requestedQuery .= 'Filter by '.$this->getArticleJlptTypes($request->filterType).'.';
        }

        $articles = $articles->paginate(4);

        $articles = $this->getArticleImpressionsSearch($articles);

        return response()->json([
            'success' => true,
            'articles' => $articles,
            'requestedQuery' => $requestedQuery,
        ]);
    }

    public function generateWordsPdf($id)
    {
        if (! auth()->user()) {
            return response()->json([
                'message' => 'you are not a user',
            ]);
        }

        $article = Article::find($id);

        if (! $article) {
            return response()->json([
                'success' => false,
                'message' => 'requested article does not exist',
            ]);
        }
        $this->incrementDownload($article);
        $user = User::find($article->user_id);

        $wordList = $article->words()->get();

        $wordList = extractWordsListAttributes($wordList);

        $data = [
            'article_id' => $article->id,
            'title_jp' => $article->title_jp,
            'title_en' => $article->title_en,
            'content_jp' => $article->content_jp,
            'content_en' => $article->content_en,
            'author' => $user->name,
            'user_id' => $user->id,
            'date' => $article->created_at,
            'source_link' => $article->source_link,
            'wordList' => $wordList,
        ];

        $pdf = PDF::loadView('pdf.kanjis.article-words', $data);
        $pdf->setOptions([
            'footer-center' => '[page]',
            'page-size' => 'a4',
        ]);

        // https://wkhtmltopdf.org/usage/wkhtmltopdf.txt
        return $pdf->inline('article-words.pdf');
    }

    public function generateKanjisPdf($id)
    {
        if (! auth()->user()) {
            return response()->json([
                'message' => 'you are not a user',
            ]);
        }

        $article = Article::find($id);

        if (! $article) {
            return response()->json([
                'success' => false,
                'message' => 'requested article does not exist',
            ]);
        }

        $this->incrementDownload($article);

        $user = User::find($article->user_id);

        $kanjiList = $article->kanjis()->get();

        $data = [
            'article_id' => $article->id,
            'title_jp' => $article->title_jp,
            'title_en' => $article->title_en,
            'content_jp' => $article->content_jp,
            'content_en' => $article->content_en,
            'author' => $user->name,
            'user_id' => $user->id,
            'date' => $article->created_at,
            'source_link' => $article->source_link,
            'kanjiList' => $kanjiList,
        ];

        $pdf = PDF::loadView('pdf.kanjis.article-kanjis', $data);
        $pdf->setOptions([
            'footer-center' => '[page]',
            'page-size' => 'a4',
        ]);

        return $pdf->inline('article-kanjis.pdf');
    }

    /**
     * POST /api/article/{id}/togglepublicity
     * Toggles article's public visibility
     * @param int $id
     * @return JsonResponse
     */
    public function togglePublicity($id)
    {
        $article = Article::find($id);

        if (! $article || $article->user_id != auth()->user()->id || auth()->user()->role() != 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'requested article does not exist or user is unauthorized',
            ]);
        }

        if ($article->publicity == 1) {
            $article->publicity = 0;
            $article->update();

            return response()->json([
                'success' => true,
                'message' => 'Article of id: '.$id.' is now private',
            ]);
        } else {
            $article->publicity = 1;
            $article->update();

            return response()->json([
                'success' => true,
                'message' => 'Article of id: '.$id.' is now public',
            ]);
        }
    }

    //========================= Article Impressions

    public function incrementDownload(Article $article)
    {
        // if( !auth()->user() ){
        //     return response()->json([
        //         'message' => 'you are not a user'
        //     ]);
        // }
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $download = new Download;
        $download->user_id = auth()->user()->id;
        $download->template_id = $objectTemplateId;
        $download->real_object_id = $article->id;
        $download->save();
    }

    /**
     * POST /api/article/{id}/comment
     * Adds a comment to an article
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
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

        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

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
            'message' => 'You commented article of id: '.$id,
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

    /**
     * POST /api/article/{id}/like
     * Likes an article
     * @param int $id
     * @return JsonResponse
     */
    public function unlikeArticle($id)
    {
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $like = Like::where([
            'template_id' => $objectTemplateId,
            'real_object_id' => $id,
            'user_id' => auth()->user()->id,
        ]);

        $like->delete();

        return response()->json([
            'success' => true,
            'message' => 'like was deleted',
        ]);
    }

    public function likeArticle($id)
    {
        if (! auth()->user()) {
            return response()->json([
                'message' => 'you are not a user',
            ]);
        }
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

        $checkLike = Like::where([
            'template_id' => $objectTemplateId,
            'real_object_id' => $id,
            'user_id' => auth()->user()->id,
        ])->first();

        if ($checkLike) {
            return response()->json([
                'message' => 'you cannot like it twice!',
            ]);
        }

        $like = new Like;
        $like->user_id = auth()->user()->id;
        $like->template_id = $objectTemplateId;
        $like->real_object_id = $id;
        $like->value = 1;
        $like->save();

        return response()->json([
            'success' => true,
            'message' => 'You liked list of id: '.$id,
            'like' => $like,
        ]);
    }

    public function checkIfLikedArticle($id)
    {
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

        $checkLike = Like::where([
            'template_id' => $objectTemplateId,
            'real_object_id' => $id,
            'user_id' => auth()->user()->id,
        ])->first();

        if ($checkLike) {
            return response()->json([
                'userId' => auth()->user()->id,
                'isLiked' => true,
                'message' => 'you already liked this article',
            ]);
        }

        return response()->json([
            'userId' => auth()->user()->id,
            'isLiked' => false,
            'message' => 'you havent liked the article yet',
        ]);
    }

    public function checkIfLikedComment($id)
    {
        $objectTemplateId = ObjectTemplate::where('title', 'comment')->first()->id;

        $checkLike = Like::where([
            'template_id' => $objectTemplateId,
            'real_object_id' => $id,
            'user_id' => auth()->user()->id,
        ])->first();

        if ($checkLike) {
            return response()->json([
                'userId' => auth()->user()->id,
                'isLiked' => true,
                'message' => 'you already liked this comment',
            ]);
        }

        return response()->json([
            'userId' => auth()->user()->id,
            'isLiked' => false,
            'message' => 'you havent liked the comment yet',
        ]);
    }

    //======================== Administration

    public function getArticlesPending()
    {

        $articlesPending = Article::where('status', '0')->orWhere('status', '1')->orderBy('created_at', 'desc')->get();

        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

        $statusTitles = [
            'Pending',
            'Reviewing',
            'Rejected',
            'Approved',
        ];

        foreach ($articlesPending as $article) {
            $article->statusTitle = $statusTitles[intval($article->status)];
            $article->hashtags = getUniquehashtags($article->id, $objectTemplateId);
        }

        return response()->json([
            'success' => true,
            'articlesPending' => $articlesPending,
        ]);
    }

    public function getStatus($id)
    {
        $articleStatus = Article::find($id)->status;

        return response()->json([
            'success' => true,
            'articleStatus' => $articleStatus,
        ]);
    }

    public function setStatus(Request $request, $id)
    {
        $article = Article::find($id);
        $article->status = $request->get('status');

        if ($request->get('status') == 3) {
            $status = 'approved';
        } elseif ($request->get('status') == 2) {
            $status = 'rejected';
        } elseif ($request->get('status') == 1) {
            $status = 'reviewing';
        } elseif ($request->get('status') == 0) {
            $status = 'pending';
        }

        $article->update();

        return response()->json([
            'success' => true,
            'newStatus' => $request->status,
            'message' => 'Article of id: '.$id.' set to '.$status,
        ]);
    }

    //======================== Hashtags

    public function getUniquehashtagArticles($wantedTag)
    {
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        // get tag which was input id
        $uniqueTag = Uniquehashtag::where('content', $wantedTag)->first();
        if (! isset($uniqueTag)) {
            return null;
        }
        // get all hashtag foreign table rows
        $foundRows = DB::table('hashtags')->where('uniquehashtag_id', $uniqueTag->id)
            ->where('template_id', $objectTemplateId)->get();

        $ids = [];
        // get all articles with that tag id
        foreach ($foundRows as $articlelink) {
            $ids[] = $articlelink->real_object_id;
        }

        $articles = Article::whereIn('id', $ids);

        return $articles;
    }

    // public function getUniquehashtags($id, $objectTemplateId)
    // {
    //     $foundRows = DB::table('hashtags')->where('real_object_id', $id)
    //     ->where('template_id', $objectTemplateId)->get();
    //     $finalTags = [];

    //     foreach($foundRows as $taglink)
    //     {
    //         $uniqueTag = Uniquehashtag::find($taglink->uniquehashtag_id);
    //         $finalTags[] = $uniqueTag;
    //     }

    //     return $finalTags;
    // }

    // public function checkIfHashtagsAreUnique($tags)
    // {
    //     $finalTags = [];
    //     $same = 0;
    //     $unique = 0;
    //     foreach($tags as $tag)
    //     {
    //         $uniqueTag = Uniquehashtag::where("content", $tag)->first();
    //         if($uniqueTag)
    //         {
    //             // tag is not unique
    //             $finalTags[] = $uniqueTag;
    //             $same++;
    //         }
    //         else {
    //             // tag is unique
    //             $uniqueTag = new Uniquehashtag;
    //             $uniqueTag->content = $tag;
    //             $uniqueTag->save();
    //             $finalTags[] = $uniqueTag;
    //             $unique++;
    //         }
    //     }

    //     return $finalTags;
    // }

    // public function removeHashtags($id, $objectTemplateId)
    // {
    //     $oldTags = DB::table('hashtags')
    //         ->where('template_id', $objectTemplateId)
    //         ->where('real_object_id', $id)
    //         ->delete();
    // }

    // public function attachHashTags($tags, $object)
    // {
    //     $tags = $this->getHashtags($tags);
    //     $tags = $this->checkIfHashtagsAreUnique($tags);
    //     $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

    //     foreach($tags as $tag)
    //     {
    //         $row = [
    //             'template_id' => $objectTemplateId,
    //             'uniquehashtag_id' => $tag->id,
    //             'real_object_id' => $object->id,
    //             'user_id' => $object->user_id,
    //             'created_at' => date('Y-m-d H:i:s'),
    //             'updated_at' => date('Y-m-d H:i:s')
    //         ];

    //         $x = DB::table('hashtags')->insert($row);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => "hashtags were added."
    //     ]);
    // }

    // public function getHashtags($string) {
    //     $hashtags= FALSE;
    //     preg_match_all("/(#\w+)/u", $string, $matches);
    //     if ($matches) {
    //         $hashtagsArray = array_count_values($matches[0]);
    //         $hashtags = array_keys($hashtagsArray);
    //     }
    //     return $hashtags;
    // }
}
