<?php
namespace App\Domain\Articles\DTOs;

use App\Domain\Shared\ValueObjects\ArticleTitle;
use App\Domain\Shared\ValueObjects\ArticleContent;
use App\Domain\Shared\ValueObjects\SourceUrl;
use App\Domain\Shared\ValueObjects\Tags;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\ArticleStatus;

readonly class ArticleUpdateDTO
{
    public function __construct(
        public ?ArticleTitle $title_jp = null,
        public ?ArticleTitle $title_en = null,
        public ?ArticleContent $content_jp = null,
        public ?ArticleContent $content_en = null,
        public ?SourceUrl $source_link = null,
        public ?PublicityStatus $publicity = null,
        public ?ArticleStatus $status = null,
        public ?Tags $tags = null,
        public bool $reattach = false
    ) {}

    /**
     * Create update DTO from request data with validation
     * Handle partial updates where null means 'dont update'
     */
    public static function fromRequest(array $validated): self
    {
        return new self(
            title_jp: isset($validated['title_jp'])
                ? new ArticleTitle($validated['title_jp'])
                : null,
            title_en: isset($validated['title_en'])
                ? ($validated['title_en'] ? new ArticleTitle($validated['title_en']) : null)
                : null,
            content_jp: isset($validated['content_jp'])
                ? new ArticleContent($validated['content_jp'])
                : null,
            content_en: isset($validated['content_en'])
                ? ($validated['content_en'] ? new ArticleContent($validated['content_en']) : null)
                : null,
            source_link: isset($validated['source_link'])
                ? new SourceUrl($validated['source_link'])
                : null,
            publicity: isset($validated['publicity'])
                ? PublicityStatus::from((int)$validated['publicity'])
                : null,
            status: isset($validated['status'])
                ? ArticleStatus::from((int)$validated['status'])
                : null,
            tags: isset($validated['tags'])
                ? new Tags($validated['tags'])
                : null,
            reattach: $validated['reattach'] ?? false
        );
    }

    public function hasContentChanges(): bool
    {
        return $this->reattach || $this->content_jp !== null;
    }

    /**
     * Get all non-null fields as key-value pairs for model updating
     */
    public function getUpdateableFields(): array
    {
        $fields = [];

        if ($this->title_jp) {
            $fields['title_jp'] = (string)$this->title_jp;
        }

        if ($this->title_en !== null) {
            $fields['title_en'] = $this->title_en ? (string)$this->title_en : '';
        }

        if ($this->content_jp) {
            $fields['content_jp'] = (string)$this->content_jp;
        }

        if ($this->content_en !== null) {
            $fields['content_en'] = $this->content_en ? (string)$this->content_en : '';
        }

        if ($this->source_link) {
            $fields['source_link'] = (string)$this->source_link;
        }

        if ($this->publicity !== null) {
            $fields['publicity'] = $this->publicity->value;
        }

        if ($this->status !== null) {
            $fields['status'] = $this->status->value;
        }

        return $fields;
    }
}
