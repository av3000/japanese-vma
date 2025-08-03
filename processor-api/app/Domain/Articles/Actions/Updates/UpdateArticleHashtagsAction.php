<?php
namespace App\Domain\Articles\Actions;

use App\Domain\Articles\Models\Article;
use App\Http\Models\{ObjectTemplate, Uniquehashtag};
use Illuminate\Support\Facades\DB;

class UpdateArticleHashtagsAction
{
    public function execute(Article $article, array $tags): void
    {
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

        $this->removeHashtags($article->id, $objectTemplateId);

        if (!empty($tags)) {
            $this->attachHashtags($article, $tags, $objectTemplateId);
        }
    }

    private function removeHashtags(int $articleId, int $objectTemplateId): void
    {
        DB::table('hashtags')
            ->where('template_id', $objectTemplateId)
            ->where('real_object_id', $articleId)
            ->delete();
    }

    private function attachHashtags(Article $article, array $tags, int $objectTemplateId): void
    {
        $tagsString = implode(' ', $tags);
        $hashtags = $this->extractHashtags($tagsString);
        $uniqueHashtags = $this->ensureHashtagsExist($hashtags);

        foreach ($uniqueHashtags as $hashtag) {
            DB::table('hashtags')->insert([
                'template_id' => $objectTemplateId,
                'uniquehashtag_id' => $hashtag->id,
                'real_object_id' => $article->id,
                'user_id' => $article->user_id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    private function extractHashtags(string $string): array
    {
        preg_match_all("/(#\w+)/u", $string, $matches);
        return $matches ? array_keys(array_count_values($matches[0])) : [];
    }

    private function ensureHashtagsExist(array $hashtags): array
    {
        $uniqueHashtags = [];

        foreach ($hashtags as $hashtag) {
            $existingTag = Uniquehashtag::where('content', $hashtag)->first();

            if ($existingTag) {
                $uniqueHashtags[] = $existingTag;
            } else {
                $newTag = Uniquehashtag::create(['content' => $hashtag]);
                $uniqueHashtags[] = $newTag;
            }
        }

        return $uniqueHashtags;
    }
}
