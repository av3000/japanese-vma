<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\Unit\Architecture\Support\ClassReferenceScanner;

/**
 * MCP is a second delivery adapter next to app/Http/v1 (ADR 0003). Its tools call the
 * application's `*ServiceInterface` methods and shape the result themselves, so app/Mcp may use
 * App\Application, App\Domain and App\Shared and nothing that reaches persistence or the website's
 * HTTP layer: App\Infrastructure, App\Http (which also holds the legacy Eloquent models) and
 * Eloquent itself.
 *
 * The rule runs on resolved class references, so comments do not count and aliased or grouped
 * imports do. The snippet tests prove the rule fails on a violation and passes on the allowed
 * layers, so an empty app/Mcp scan can never pass because the rule matches nothing.
 */
final class McpLayerDependencyTest extends TestCase
{
    private const FORBIDDEN = [
        'App\\Infrastructure\\' => 'persistence belongs behind a service',
        'App\\Http\\' => 'a tool is not a controller and reuses no v1 resources',
        'Illuminate\\Database\\Eloquent\\' => 'tools never touch Eloquent models',
    ];

    public function test_mcp_code_imports_only_the_allowed_layers(): void
    {
        $files = iterator_to_array($this->mcpFiles(), false);
        $this->assertNotEmpty($files, 'app/Mcp should contain code to check.');

        $violations = [];

        foreach ($files as $file) {
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($this->appPath()) + 1));

            array_push($violations, ...$this->violations($relative, (string) file_get_contents($file->getPathname())));
        }

        $this->assertSame([], $violations, "app/Mcp must not depend on Infrastructure, Http or Eloquent:\n".implode("\n", $violations));
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function forbiddenSnippets(): array
    {
        return [
            'an Infrastructure repository' => ['use App\Infrastructure\Persistence\Repositories\WordRepository;', 1],
            'an Infrastructure model' => ['use App\Infrastructure\Persistence\Models\Word;', 1],
            'an Http resource' => ['use App\Http\v1\JapaneseMaterial\Words\Resources\WordListResource;', 1],
            'a legacy Http model' => ['use App\Http\Models\Kanji;', 1],
            'the Eloquent base model' => ['use Illuminate\Database\Eloquent\Model;', 1],
            'an Eloquent builder' => ['use Illuminate\Database\Eloquent\Builder;', 1],
            'an aliased Infrastructure import' => ['use App\Infrastructure\Persistence\Models\Word as WordRow;', 1],
            'a grouped import' => ['use App\Infrastructure\Persistence\Models\{Word, Kanji};', 2],
            'a fully qualified name in code' => ['$row = \App\Infrastructure\Persistence\Models\Word::query()->first();', 1],
        ];
    }

    #[DataProvider('forbiddenSnippets')]
    public function test_the_rule_fails_on_a_forbidden_reference(string $statement, int $expectedViolations): void
    {
        $violations = $this->violations('Mcp/Tools/Example.php', $this->snippet($statement));

        $this->assertCount($expectedViolations, $violations, implode("\n", $violations));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function allowedSnippets(): array
    {
        return [
            'an Application service interface' => ['use App\Application\JapaneseMaterial\Words\Services\WordServiceInterface;'],
            'a Domain query' => ['use App\Domain\JapaneseMaterial\Words\Queries\WordQueryCriteria;'],
            'a Shared result' => ['use App\Shared\Results\Result;'],
            'the MCP library' => ['use Laravel\Mcp\Response;'],
            'framework validation' => ['use Illuminate\Validation\ValidationException;'],
            'a mention in a comment' => ['// Never use App\Infrastructure\Persistence\Models\Word here.'],
            'a mention in a docblock' => ["/** @see \\Illuminate\\Database\\Eloquent\\Model */\n"],
        ];
    }

    #[DataProvider('allowedSnippets')]
    public function test_the_rule_passes_on_an_allowed_reference(string $statement): void
    {
        $this->assertSame([], $this->violations('Mcp/Tools/Example.php', $this->snippet($statement)));
    }

    /**
     * @return array<int, string> "path:line reason"
     */
    private function violations(string $relativePath, string $code): array
    {
        $violations = [];

        foreach ((new ClassReferenceScanner())->references($code) as $class => $lines) {
            foreach (self::FORBIDDEN as $prefix => $reason) {
                if (str_starts_with($class, $prefix)) {
                    foreach ($lines as $line) {
                        $violations[] = "{$relativePath}:{$line} uses {$class} ({$reason})";
                    }
                }
            }
        }

        return $violations;
    }

    private function snippet(string $statement): string
    {
        return "<?php\n\nnamespace App\\Mcp\\Tools;\n\n{$statement}\n\nclass Example\n{\n}\n";
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function mcpFiles(): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->appPath().'/Mcp', RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->getExtension() === 'php') {
                yield $file;
            }
        }
    }

    private function appPath(): string
    {
        return dirname(__DIR__, 3).'/app';
    }
}
