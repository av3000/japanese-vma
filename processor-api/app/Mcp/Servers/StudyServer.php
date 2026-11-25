<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\FindArticlesTool;
use App\Mcp\Tools\FindDecksTool;
use App\Mcp\Tools\GetFlashcardsTool;
use App\Mcp\Tools\LookupKanjiTool;
use App\Mcp\Tools\LookupWordTool;
use App\Mcp\Tools\ReadArticleTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Japanese study')]
#[Version('1.0.0')]
#[Instructions(<<<'TEXT'
Read-only access to a Japanese learning platform's public study data. Nothing here needs a login and no tool changes anything.
Tools: lookup_word (dictionary words), lookup_kanji (one kanji with example words, or kanji by meaning), find_articles and read_article (reading texts), find_decks and get_flashcards (public flashcard lists).
Two chains: find_articles → read_article, and find_decks → get_flashcards. Search first, then open one result by its uuid.
JLPT levels are N5 (easiest) to N1. An item without an assigned level has level null. Word levels may be missing until dictionary levels are backfilled, so a level filter on words can find nothing.
Lists are paged: a tool takes limit (at most 20) and page, and answers has_more when another page exists.
TEXT)]
class StudyServer extends Server
{
    /** @var array<int, class-string<Server\Tool>> */
    protected array $tools = [
        LookupWordTool::class,
        LookupKanjiTool::class,
        FindArticlesTool::class,
        ReadArticleTool::class,
        FindDecksTool::class,
        GetFlashcardsTool::class,
    ];
}
