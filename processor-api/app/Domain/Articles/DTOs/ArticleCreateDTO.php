<?php
namespace App\Domain\Articles\DTOs;

use App\Domain\Articles\ValueObjects\ArticleTitle;
use App\Domain\Articles\ValueObjects\ArticleContent;
use App\Domain\Articles\ValueObjects\ArticleSourceUrl;
use App\Domain\Shared\ValueObjects\Tags;
use App\Domain\Shared\Enums\PublicityStatus;
use InvalidArgumentException;

readonly class ArticleCreateDTO
{
    public function __construct(
        public ArticleTitle $title_jp,
        public ?ArticleTitle $title_en,
        public ArticleContent $content_jp,
        public ?ArticleContent $content_en,
        public ArticleSourceUrl $source_link,
        public PublicityStatus $publicity,
        public ?Tags $tags = null
    ) {}
    /**
     * Create an instance from request data.
     *
     * @param array $data
     * @return self
     * @throws InvalidArgumentException
     */
    public static function fromRequest(array $data): self
    {
        try {
            return new self(
                title_jp: new ArticleTitle($data['title_jp']),
                title_en: isset($data['title_en']) && !empty($data['title_en'])
                    ? new ArticleTitle($data['title_en'])
                    : null,
                content_jp: new ArticleContent($data['content_jp']),
                content_en: isset($data['content_en']) && !empty($data['content_en'])
                    ? new ArticleContent($data['content_en'])
                    : null,
                source_link: new ArticleSourceUrl($data['source_link']),
                publicity: isset($data['publicity'])
                    ? PublicityStatus::from((int)$data['publicity'])
                    : PublicityStatus::PRIVATE,
                tags: isset($data['tags']) && !empty($data['tags'])
                    ? (is_array($data['tags'])
                        ? Tags::fromArray($data['tags'])
                        : new Tags($data['tags']))
                    : null
            );
        } catch (\Throwable $e) {
            throw new InvalidArgumentException(
                "Invalid article data: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
