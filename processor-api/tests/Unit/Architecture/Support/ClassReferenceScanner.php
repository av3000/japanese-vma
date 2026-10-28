<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture\Support;

use PhpToken;

/**
 * Finds the classes a PHP file really refers to, using PHP's own tokenizer instead of regexes
 * over lines. Comments and docblocks are skipped, `use` imports (aliases and group imports
 * included) are resolved, and every other name is resolved against the imports and the file's
 * namespace, the way PHP itself does.
 *
 * It answers "which classes does this file depend on" for architecture tests. It is not a parser:
 * an uppercase constant such as PHP_EOL resolves to a harmless name that no rule ever forbids.
 */
final class ClassReferenceScanner
{
    private const SKIPPED = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];

    private const NAME_TOKENS = [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED];

    /** A name right after one of these is a member, a declaration or a label, not a class. */
    private const NOT_A_CLASS_AFTER = ['->', '?->', '::', 'function', 'const', 'class', 'interface', 'trait', 'enum', 'goto', 'namespace'];

    /** A name right after one of these is a class even when a `(` follows. */
    private const CLASS_AFTER = ['new', 'instanceof', 'extends', 'implements'];

    /**
     * @return array<string, list<int>> fully qualified class name => lines it is referenced on
     */
    public function references(string $code): array
    {
        $tokens = $this->significantTokens($code);
        $namespace = '';
        $imports = [];
        $references = [];
        $depth = 0;

        for ($index = 0, $count = count($tokens); $index < $count; $index++) {
            $token = $tokens[$index];

            if ($token->text === '{' || $token->is([T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES])) {
                $depth++;

                continue;
            }

            if ($token->text === '}') {
                $depth--;

                continue;
            }

            if ($token->is(T_NAMESPACE) && isset($tokens[$index + 1]) && $tokens[$index + 1]->is(self::NAME_TOKENS)) {
                $namespace = $tokens[$index + 1]->text;
                $index++;

                continue;
            }

            if ($token->is(T_USE) && $depth === 0 && ($tokens[$index + 1]->text ?? '') !== '(') {
                $index = $this->readImport($tokens, $index + 1, $imports, $references);

                continue;
            }

            if (! $token->is(self::NAME_TOKENS) || ! $this->isClassPosition($tokens, $index)) {
                continue;
            }

            $name = $this->resolve($token, $namespace, $imports);

            if ($name !== null) {
                $references[$name][] = $token->line;
            }
        }

        return $references;
    }

    /**
     * Instance member names read or called through `->` or `?->`, such as `isAdmin` or `hasRole`.
     *
     * @return array<string, list<int>> member name => lines
     */
    public function memberAccesses(string $code): array
    {
        $tokens = $this->significantTokens($code);
        $accesses = [];

        foreach ($tokens as $index => $token) {
            if ($token->is([T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR]) && isset($tokens[$index + 1]) && $tokens[$index + 1]->is(T_STRING)) {
                $accesses[$tokens[$index + 1]->text][] = $tokens[$index + 1]->line;
            }
        }

        return $accesses;
    }

    /**
     * Class constants and enum cases, resolved: `UserRole::ADMIN` becomes `App\...\UserRole::ADMIN`.
     *
     * @return array<string, list<int>> "FQCN::NAME" => lines
     */
    public function constantAccesses(string $code): array
    {
        $tokens = $this->significantTokens($code);
        $namespace = '';
        $imports = [];
        $ignored = [];
        $accesses = [];
        $depth = 0;

        for ($index = 0, $count = count($tokens); $index < $count; $index++) {
            $token = $tokens[$index];

            if ($token->text === '{' || $token->is([T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES])) {
                $depth++;
            } elseif ($token->text === '}') {
                $depth--;
            } elseif ($token->is(T_NAMESPACE) && isset($tokens[$index + 1]) && $tokens[$index + 1]->is(self::NAME_TOKENS)) {
                $namespace = $tokens[++$index]->text;
            } elseif ($token->is(T_USE) && $depth === 0 && ($tokens[$index + 1]->text ?? '') !== '(') {
                $index = $this->readImport($tokens, $index + 1, $imports, $ignored);
            } elseif ($token->is(self::NAME_TOKENS)
                && ($tokens[$index + 1]->text ?? null) === '::'
                && ($tokens[$index + 2] ?? null)?->is(T_STRING)
                && ($tokens[$index + 3]->text ?? null) !== '(') {
                $class = $this->resolve($token, $namespace, $imports);

                if ($class !== null) {
                    $accesses[$class.'::'.$tokens[$index + 2]->text][] = $token->line;
                }
            }
        }

        return $accesses;
    }

    /**
     * @return list<PhpToken>
     */
    private function significantTokens(string $code): array
    {
        return array_values(array_filter(
            PhpToken::tokenize($code),
            static fn (PhpToken $token): bool => ! $token->is(self::SKIPPED),
        ));
    }

    /**
     * Reads one file-level `use` statement, starting after the keyword. Function and constant
     * imports are skipped; class imports go into the alias map and count as references.
     *
     * @param list<PhpToken> $tokens
     * @param array<string, string> $imports lowercase alias => FQCN
     * @param array<string, list<int>> $references
     *
     * @return int index of the closing `;`
     */
    private function readImport(array $tokens, int $index, array &$imports, array &$references): int
    {
        $count = count($tokens);

        if (isset($tokens[$index]) && $tokens[$index]->is([T_FUNCTION, T_CONST])) {
            while ($index < $count && $tokens[$index]->text !== ';') {
                $index++;
            }

            return $index;
        }

        $prefix = '';
        $name = null;
        $line = $tokens[$index]->line ?? 0;

        for (; $index < $count; $index++) {
            $token = $tokens[$index];

            if ($token->is(self::NAME_TOKENS)) {
                $name = ltrim($token->text, '\\');
                $line = $token->line;
            } elseif ($token->is(T_NS_SEPARATOR)) {
                continue;
            } elseif ($token->text === '{') {
                $prefix = ($name ?? '').'\\';
                $name = null;
            } elseif ($token->is(T_AS)) {
                $alias = $tokens[++$index]->text;
                $this->addImport($prefix.$name, $alias, $line, $imports, $references);
                $name = null;
            } elseif ($token->text === ',' || $token->text === '}' || $token->text === ';') {
                if ($name !== null) {
                    $full = $prefix.$name;
                    $this->addImport($full, substr($full, (int) strrpos('\\'.$full, '\\')), $line, $imports, $references);
                    $name = null;
                }

                if ($token->text === '}') {
                    $prefix = '';
                }

                if ($token->text === ';') {
                    return $index;
                }
            }
        }

        return $index;
    }

    /**
     * @param array<string, string> $imports
     * @param array<string, list<int>> $references
     */
    private function addImport(string $fqcn, string $alias, int $line, array &$imports, array &$references): void
    {
        $fqcn = ltrim($fqcn, '\\');
        $imports[strtolower($alias)] = $fqcn;
        $references[$fqcn][] = $line;
    }

    /**
     * @param list<PhpToken> $tokens
     */
    private function isClassPosition(array $tokens, int $index): bool
    {
        $previous = $tokens[$index - 1]->text ?? '';
        $next = $tokens[$index + 1]->text ?? '';

        if (in_array(strtolower($previous), self::NOT_A_CLASS_AFTER, true)) {
            return false;
        }

        if (in_array(strtolower($previous), self::CLASS_AFTER, true)) {
            return true;
        }

        // `case PENDING = 'x';` declares an enum case; `case Status::X:` in a switch names a class.
        if (strtolower($previous) === 'case') {
            return $next === '::';
        }

        // A function call (`app(`), a named argument (`name:`) or a label, not a class.
        if ($next === '(' || $next === ':') {
            return false;
        }

        // Unqualified lowercase names are keywords, built-in types or functions: true, int, array.
        return ! $tokens[$index]->is(T_STRING) || ctype_upper($tokens[$index]->text[0]);
    }

    /**
     * @param array<string, string> $imports
     */
    private function resolve(PhpToken $token, string $namespace, array $imports): ?string
    {
        if ($token->is(T_NAME_FULLY_QUALIFIED)) {
            return ltrim($token->text, '\\');
        }

        $segments = explode('\\', $token->text);
        $first = strtolower($segments[0]);

        if (in_array($first, ['self', 'static', 'parent'], true)) {
            return null;
        }

        if (isset($imports[$first])) {
            $segments[0] = $imports[$first];

            return implode('\\', $segments);
        }

        return ltrim($namespace.'\\'.$token->text, '\\');
    }
}
