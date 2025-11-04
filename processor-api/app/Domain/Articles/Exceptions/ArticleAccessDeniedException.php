<?php
namespace App\Domain\Articles\Exceptions;

class ArticleAccessDeniedException extends \Exception
{
    public function __construct(string $identifier)
    {
        parent::__construct("Article {$identifier} access denied");
    }
}
