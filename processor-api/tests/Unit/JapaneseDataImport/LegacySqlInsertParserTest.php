<?php

namespace Tests\Unit\JapaneseDataImport;

use App\Support\JapaneseDataImport\LegacySqlInsertParser;
use PHPUnit\Framework\TestCase;

class LegacySqlInsertParserTest extends TestCase
{
    public function testItParsesEscapedAndNullValues(): void
    {
        $parser = new LegacySqlInsertParser();

        $parsed = $parser->parseInsertStatement(<<<'SQL'
INSERT INTO `demo` (`id`, `name`, `notes`) VALUES
(1, 'Alice', 'Line one\nLine two'),
(2, 'Bob\'s', NULL);
SQL);

        $this->assertNotNull($parsed);
        $this->assertSame('demo', $parsed['table']);
        $this->assertSame(['id', 'name', 'notes'], $parsed['columns']);
        $this->assertSame(
            [
                [1, 'Alice', "Line one\nLine two"],
                [2, "Bob's", null],
            ],
            $parsed['rows'],
        );
    }

    public function testItParsesMultilineStringValues(): void
    {
        $parser = new LegacySqlInsertParser();

        $parsed = $parser->parseInsertStatement(<<<'SQL'
INSERT INTO `demo` (`id`, `name`, `notes`) VALUES
(3, 'Multi
line', 'value');
SQL);

        $this->assertNotNull($parsed);
        $this->assertSame(
            [
                [3, "Multi\nline", 'value'],
            ],
            $parsed['rows'],
        );
    }
}
