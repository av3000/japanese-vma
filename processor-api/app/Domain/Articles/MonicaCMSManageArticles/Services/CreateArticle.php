<?php

namespace App\Domains\Articles\ManageArticles\Services;

use App\Services\BaseService;
use App\Interfaces\ServiceInterface;
use App\Domain\Articles\Models\Article;

class CreateArticle extends BaseService implements ServiceInterface
{
    private Article $article;

    public function rules(): array
    {
        return [
            'author_id' => 'required|integer|exists:users,id',
            'title_jp' => 'required|string|max:255',
            'content_jp' => 'required|string|min:50',
            'title_en' => 'nullable|string|max:255',
            'content_en' => 'nullable|string',
            'source_link' => 'nullable|url|max:500',
            'publicity' => 'nullable|boolean',
        ];
    }

    // TODO: Implement general permissions
    public function permissions(): array
    {
        return [
            'author_must_be_authenticated',
            'author_can_create_articles',
        ];
    }

    /**
     * Execute the article creation process
     */
    public function execute(array $data): Article
    {
        $this->validateRules($data);
        $this->createArticle($data);
        // TODO: Handle any post-creation logic, such as auto-publishing or queuing jobs for processing japanese texts
        // $this->handlePostCreationLogic();

        return $this->article;
    }

    /**
     * Create the article record with validated data
     */
    private function createArticle(array $data): void
    {
        $this->article = Article::create([
            'user_id' => $this->author->id,
            'title_jp' => $data['title_jp'],
            'title_en' => $this->valueOrNull($data, 'title_en'),
            'content_jp' => $data['content_jp'],
            'content_en' => $this->valueOrNull($data, 'content_en'),
            'source_link' => $this->valueOrNull($data, 'source_link'),
            'publicity' => $this->valueOrFalse($data, 'publicity'),
        ]);
    }

    /**
     * Handle business logic that occurs after article creation
     */
    private function handlePostCreationLogic(): void
    {
        // Auto-publish if business conditions are met
        // if ($this->shouldAutoPublish()) {
        //     $this->article->publish(); // Using rich domain method
        // }

        // Future: Queue Japanese text processing
        // if ($this->shouldProcessJapaneseContent()) {
        //     ProcessJapaneseText::dispatch($this->article);
        // }
    }

    /**
     * Business rule: determine if article should be auto-published
     */
    // private function shouldAutoPublish(): bool
    // {
    //     return $this->article->canBePublished() &&
    //            $this->article->publicity &&
    //            $this->author->isVerified(); // Future: user verification system
    // }
}
