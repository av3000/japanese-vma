<?php

namespace App\Domain\Articles\Actions;

use App\Domain\Articles\Models\Article;
use App\Http\Models\{View, ObjectTemplate};

class IncrementView
{
    public function execute(Article $article): void
    {
        if (!auth()->user()) {
            return;
        }

        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

        $existingView = View::where([
            'template_id' => $objectTemplateId,
            'real_object_id' => $article->id,
            'user_id' => auth()->id()
        ])->first();

        if ($existingView) {
            $existingView->touch();
        } else {
            View::create([
                'user_id' => auth()->id(),
                'user_ip' => request()->ip(),
                'template_id' => $objectTemplateId,
                'real_object_id' => $article->id,
            ]);
        }
    }
}
