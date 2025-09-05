<?php
namespace App\Domain\Articles\Actions\Deletion;

use App\Infrastructure\Persistence\Models\Article;
use App\Http\Models\{Like, View, Comment, Download, ObjectTemplate};

class CleanupArticleEngagementAction
{
    public function execute(Article $article): void
    {
        $articleTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $commentTemplateId = ObjectTemplate::where('title', 'comment')->first()->id;

        // Get comments before deleting engagement data
        $comments = Comment::where('template_id', $articleTemplateId)
            ->where('real_object_id', $article->id)
            ->get();

        // Delete article engagement data
        Like::where('template_id', $articleTemplateId)
            ->where('real_object_id', $article->id)
            ->delete();

        View::where('template_id', $articleTemplateId)
            ->where('real_object_id', $article->id)
            ->delete();

        Download::where('template_id', $articleTemplateId)
            ->where('real_object_id', $article->id)
            ->delete();

        // Delete comments and their engagement data
        foreach ($comments as $comment) {
            // Delete likes on this comment
            Like::where('template_id', $commentTemplateId)
                ->where('real_object_id', $comment->id)
                ->delete();

            $comment->delete();
        }
    }
}
