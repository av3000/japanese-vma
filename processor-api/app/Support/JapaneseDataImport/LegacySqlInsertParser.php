<?php

namespace App\Support\JapaneseDataImport;

use InvalidArgumentException;

class LegacySqlInsertParser
{
    /**
     * @return array{table:string,columns:list<string>,rows:list<list<mixed>>}|null
     */
    public function parseInsertStatement(string $statement): ?array
    {
        $trimmedStatement = trim($statement);

        if ($trimmedStatement === '' || !str_starts_with($trimmedStatement, 'INSERT INTO')) {
            return null;
        }

        if (!preg_match('/^INSERT INTO\s+`?([^`\s]+)`?\s*\((.*?)\)\s*VALUES\s*(.+);$/is', $trimmedStatement, $matches)) {
            throw new InvalidArgumentException('Unsupported INSERT statement format.');
        }

        $columns = array_map(
            static fn (string $column): string => trim($column, " \t\n\r\0\x0B`"),
            explode(',', $matches[2]),
        );

        return [
            'table' => $matches[1],
            'columns' => $columns,
            'rows' => $this->parseValuesPayload($matches[3]),
        ];
    }

    /**
     * @return list<list<mixed>>
     */
    private function parseValuesPayload(string $payload): array
    {
        $rows = [];
        $currentRow = [];
        $currentValue = '';
        $currentValueIsString = false;
        $insideString = false;
        $escaping = false;
        $rowDepth = 0;

        $length = strlen($payload);

        for ($index = 0; $index < $length; $index++) {
            $character = $payload[$index];

            if ($insideString) {
                if ($escaping) {
                    $currentValue .= $this->decodeEscapedCharacter($character);
                    $escaping = false;
                    continue;
                }

                if ($character === '\\') {
                    $escaping = true;
                    continue;
                }

                if ($character === '\'') {
                    $insideString = false;
                    continue;
                }

                $currentValue .= $character;
                continue;
            }

            if ($character === '\'') {
                $insideString = true;
                $currentValueIsString = true;
                continue;
            }

            if ($character === '(') {
                if ($rowDepth === 0) {
                    $currentRow = [];
                    $currentValue = '';
                    $currentValueIsString = false;
                }

                $rowDepth++;
                continue;
            }

            if ($character === ')') {
                if ($rowDepth === 1) {
                    $currentRow[] = $this->normalizeValue($currentValue, $currentValueIsString);
                    $rows[] = $currentRow;
                    $currentRow = [];
                    $currentValue = '';
                    $currentValueIsString = false;
                }

                $rowDepth--;
                continue;
            }

            if ($character === ',' && $rowDepth === 1) {
                $currentRow[] = $this->normalizeValue($currentValue, $currentValueIsString);
                $currentValue = '';
                $currentValueIsString = false;
                continue;
            }

            if ($rowDepth === 0) {
                continue;
            }

            if (ctype_space($character) && ($currentValue === '' || $currentValueIsString)) {
                continue;
            }

            $currentValue .= $character;
        }

        return $rows;
    }

    private function decodeEscapedCharacter(string $character): string
    {
        return match ($character) {
            '0' => "\0",
            'n' => "\n",
            'r' => "\r",
            't' => "\t",
            'Z' => chr(26),
            '\\' => '\\',
            '\'' => '\'',
            '"' => '"',
            default => $character,
        };
    }

    private function normalizeValue(string $value, bool $isString): mixed
    {
        if ($isString) {
            return $value;
        }

        $trimmedValue = trim($value);

        if ($trimmedValue === '' || strtoupper($trimmedValue) === 'NULL') {
            return null;
        }

        if (preg_match('/^-?\d+$/', $trimmedValue) === 1) {
            return (int) $trimmedValue;
        }

        if (preg_match('/^-?\d+\.\d+$/', $trimmedValue) === 1) {
            return (float) $trimmedValue;
        }

        return $trimmedValue;
    }
}
