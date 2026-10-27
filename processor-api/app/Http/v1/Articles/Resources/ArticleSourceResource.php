<?php

declare(strict_types=1);

namespace App\Http\v1\Articles\Resources;

use App\Domain\Articles\ValueObjects\ArticleSource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The Content Source an Imported Article credits.
 *
 * @property ArticleSource $resource
 */
class ArticleSourceResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(ArticleSource $source)
    {
        parent::__construct($source);
    }

    /**
     * @return array{key: string, name: string, homepage_url: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'key' => (string) $this->resource->key,
            'name' => (string) $this->resource->name,
            'homepage_url' => (string) $this->resource->homepageUrl,
        ];
    }
}
