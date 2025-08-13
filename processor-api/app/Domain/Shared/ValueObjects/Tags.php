<?php
namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;

readonly class Tags
{
    private const MAX_LENGTH = 255;

    /** @var array<string> */
    public array $tags;

    public function __construct(public string $rawValue)
    {
        if (mb_strlen($this->rawValue) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Tags string cannot exceed ' . self::MAX_LENGTH . ' characters');
        }

        // Extract hashtags
        preg_match_all("/(#\w+)/u", $this->rawValue, $matches);
        $this->tags = $matches ? array_values(array_unique($matches[0])) : [];
    }

    public static function fromString(?string $input): ?self
    {
        if ($input === null || trim($input) === '') {
            return null;
        }

        return new self(trim($input));
    }

    public static function fromArray(array $tags): self
    {
        $tagsString = implode(' ', array_map(function($tag) {
            return strpos($tag, '#') === 0 ? $tag : "#$tag";
        }, $tags));

        return new self($tagsString);
    }

    public function getTagsArray(): array
    {
        return $this->tags;
    }

    public function hasTag(string $tag): bool
    {
        $normalizedTag = strpos($tag, '#') === 0 ? $tag : "#$tag";
        return in_array($normalizedTag, $this->tags);
    }

    public function __toString(): string
    {
        return $this->rawValue;
    }
}
