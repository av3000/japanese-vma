<?php

namespace App\Domain\Articles\ValueObjects;
use App\Domain\Articles\ValueObjects\ArticleContent;
use App\Domain\Articles\ValueObjects\ArticleSourceUrl;
use App\Domain\Shared\ValueObjects\Tags;
use App\Domain\Shared\Enums\PublicityStatus;


class Article
{
    public function __construct(
        public ArticleTitle $title_jp,
        public ?ArticleTitle $title_en,
        public ArticleContent $content_jp,
        public ?ArticleContent $content_en,
        public ArticleSourceUrl $source_link,
        public PublicityStatus $publicity,
        public ?Tags $tags = null
    ) {
    }

    public function toArray(): array
    {
        return [
            // should we not use string casting here?
            'title_jp' => (string)$this->title_jp,
            'title_en' => $this->title_en ? (string)$this->title_en : null,
            'content_jp' => (string)$this->content_jp,
            'content_en' => $this->content_en ? (string)$this->content_en : null,
            'source_link' => (string)$this->source_link,
            'publicity' => $this->publicity->value,
            'tags' => $this->tags ? (string)$this->tags : null,
        ];
    }
}
