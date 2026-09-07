<?php

declare(strict_types=1);

namespace Tests\Unit\Articles;

use App\Domain\Articles\DTOs\ArticleCriteriaDTO;
use App\Domain\Articles\DTOs\ArticleListDTO;
use PHPUnit\Framework\TestCase;

/**
 * The two include_words defects are latent: the Article list path never eager-loads
 * `words` and ArticleListResource has no `words` field, so neither is observable
 * through the HTTP response. They are proven here instead.
 *
 * Whether the list should return words at all is an AFM-02/AFM-03 question.
 */
class ArticleListIncludeFlagsTest extends TestCase
{
    public function test_from_request_reads_include_words_from_its_own_key(): void
    {
        $dto = ArticleListDTO::fromRequest([
            'include_kanjis' => false,
            'include_words' => true,
        ]);

        $this->assertTrue($dto->include_words);
        $this->assertFalse($dto->include_kanjis);
    }

    public function test_from_request_does_not_couple_include_words_to_include_kanjis(): void
    {
        $dto = ArticleListDTO::fromRequest(['include_kanjis' => false]);

        $this->assertFalse($dto->include_kanjis);
        $this->assertTrue($dto->include_words, 'include_words must keep its own default when only include_kanjis is sent');
    }

    public function test_from_request_defaults_every_include_flag_to_true(): void
    {
        $dto = ArticleListDTO::fromRequest([]);

        $this->assertTrue($dto->include_stats_counts);
        $this->assertTrue($dto->include_hashtags);
        $this->assertTrue($dto->include_kanjis);
        $this->assertTrue($dto->include_words);
    }

    public function test_criteria_include_words_returns_its_own_flag(): void
    {
        $criteria = new ArticleCriteriaDTO(
            sort: null,
            include_kanjis: false,
            include_words: true,
        );

        $this->assertTrue($criteria->includeWords());
        $this->assertFalse($criteria->includeKanjis());
    }

    public function test_criteria_include_flags_are_independent(): void
    {
        $criteria = new ArticleCriteriaDTO(
            sort: null,
            include_kanjis: true,
            include_words: false,
        );

        $this->assertFalse($criteria->includeWords());
        $this->assertTrue($criteria->includeKanjis());
    }
}
