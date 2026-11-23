<?php

namespace App\Domain\Articles\DTOs;

interface ArticleIncludeOptionsInterface
{
    public function includeKanjis(): bool;

    public function includeWords(): bool;
}
