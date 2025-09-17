<?php
namespace App\Domain\Articles\Exceptions;

class ArticleNotFoundException extends \Exception
{
    public function __construct(string $identifier)
    {
        parent::__construct("Article not found: {$identifier}");
    }
}
