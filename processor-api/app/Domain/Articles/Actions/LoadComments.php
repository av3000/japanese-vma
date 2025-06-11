<?php

namespace App\Domain\Articles\Actions;

use App\Domain\Articles\Models\Article;
use App\Http\Models\{Comment, Like, ObjectTemplate};
use App\Http\User;

class LoadComments
{
    public function execute(Article $article): void
    {
        $articleTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $commentTemplateId = ObjectTemplate::where('title', 'comment')->first()->id;

        $comments = Comment::where([
            'template_id' => $articleTemplateId,
            'real_object_id' => $article->id
        ])->orderBy('created_at', 'DESC')->get();

        foreach ($comments as $comment) {
            $comment->likesTotal = Like::where([
                'template_id' => $commentTemplateId,
                'real_object_id' => $comment->id
            ])->count();

            $comment->userName = User::find($comment->user_id)->name;
        }

        $article->comments = $comments;
    }
}
