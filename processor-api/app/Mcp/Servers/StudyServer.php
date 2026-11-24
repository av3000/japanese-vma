<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\LookupWordTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Japanese study')]
#[Version('1.0.0')]
#[Instructions(<<<'TEXT'
Read-only access to a Japanese learning platform's public study data: dictionary words, with more tools to come.
Nothing here needs a login and no tool changes anything.
JLPT levels are N5 (easiest) to N1. An item without an assigned level has level null, which for dictionary words is currently every word.
Lists are paged: a tool takes limit (at most 20) and page, and answers has_more when another page exists.
TEXT)]
class StudyServer extends Server
{
    /** @var array<int, class-string<Server\Tool>> */
    protected array $tools = [
        LookupWordTool::class,
    ];
}
