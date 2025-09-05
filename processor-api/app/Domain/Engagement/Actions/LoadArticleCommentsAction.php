<?php
namespace App\Domain\Engagement\Actions;

use App\Domain\Articles\Http\Models\Article;
use App\Http\Models\{Comment, Like, ObjectTemplate};
use App\Http\User;
use Illuminate\Support\Facades\DB;

class LoadArticleCommentsAction
{
    /**
     * Load comments for an article with efficient batch loading to avoid N+1 queries.
     * This demonstrates the same pattern as your stats loading - batch the related data
     * instead of loading it individually for each comment.
     */
    public function execute(Article $article): void
    {
        $articleTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $commentTemplateId = ObjectTemplate::where('title', 'comment')->first()->id;

        // Load all comments for this article
        $comments = Comment::where([
            'template_id' => $articleTemplateId,
            'real_object_id' => $article->id
        ])->orderBy('created_at', 'DESC')->get();

        if ($comments->isEmpty()) {
            $article->comments = collect([]);
            return;
        }

        // Batch load user data to avoid N+1 queries
        $userIds = $comments->pluck('user_id')->unique();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        // Batch load like counts for all comments
        $commentIds = $comments->pluck('id')->toArray();
        $likeCounts = DB::table('likes')
            ->where('template_id', $commentTemplateId)
            ->whereIn('real_object_id', $commentIds)
            ->groupBy('real_object_id')
            ->pluck(DB::raw('count(*)'), 'real_object_id')
            ->toArray();

        // Attach the batched data to each comment
        foreach ($comments as $comment) {
            $comment->likesTotal = $likeCounts[$comment->id] ?? 0;
            $comment->userName = $users[$comment->user_id]->name ?? 'Unknown User';
        }

        $article->comments = $comments;
    }
}
