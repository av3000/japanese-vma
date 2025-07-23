<?php
namespace App\Domain\Articles\Actions;

use App\Domain\Articles\DTOs\ArticleUpdateDTO;
use App\Domain\Articles\Models\Article;

class UpdateArticleFieldsAction
{
    public function execute(Article $article, ArticleUpdateDTO $data): void
    {
        $fields = ['title_jp', 'title_en', 'content_jp', 'content_en', 'source_link', 'publicity', 'status'];

        foreach ($fields as $field) {
            if ($data->$field !== null) {
                $article->$field = $data->$field;
            }
        }

        $article->save();
    }
}
