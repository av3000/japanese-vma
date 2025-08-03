<?php
namespace App\Domain\Articles\Actions\Creation;

use App\Domain\Articles\Models\Article;
use App\Http\Models\ObjectTemplate;

class AttachHashtagsAction
{
    /**
     * Process and attach hashtags to an article.
     * This wraps the global hashtag functionality in an action for reusability.
     */
    public function execute(Article $article, array $tags): void
    {
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $tagsString = implode(' ', $tags);

        // Using the existing global function for now
        // This could be refactored further if needed
        attachHashTags($tagsString, $article, $objectTemplateId);
    }
}
