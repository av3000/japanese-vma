<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Collects every character the PDF exports can ever have to render, so a CJK font can be subset
 * down to exactly that set without losing the coverage guarantee JP-101 (b8defad2) added.
 *
 * Nothing in the application uses the resulting font yet, and the slow PDF exports this was
 * written for turned out to have a different cause entirely. Read
 * `resources/fonts/README.md` and issue #329 before building on this command.
 *
 * Run it whenever the dictionary import brings in new data, then re-subset the font with
 * `resources/fonts/subset-font.sh`.
 */
class ExtractPdfFontCharacterSet extends Command
{
    protected $signature = 'pdf:extract-font-character-set
        {--output= : Where to write the character set (defaults to resources/fonts/character-set.txt)}
        {--chunk=1000 : Rows to read per query}';

    protected $description = 'Collect every character the PDF exports can render into a text file for font subsetting';

    /**
     * Every column that can hold Japanese text. The kanji/word/radical banks are what the
     * kanji and word exports print; tatoeba sentences and article bodies are what an article
     * export can quote.
     *
     * @var array<string, list<string>>
     */
    private const SOURCE_COLUMNS = [
        'japanese_kanji_bank_long' => ['kanji', 'onyomi', 'kunyomi', 'meaning', 'nanori', 'radicals', 'radical_parts'],
        'japanese_word_bank_long' => ['word', 'furigana', 'word_k_ele', 'furigana_r_ele', 'sense'],
        'japanese_radicals_bank_long' => ['radical', 'meaning', 'hiragana'],
        'japanese_tatoeba_sentences' => ['content'],
        'articles' => ['title_jp', 'content_jp'],
    ];

    /**
     * Ranges that must be covered whether or not any stored row happens to use them yet. Kana
     * and Japanese punctuation are structural - a user typing a new article will reach for them
     * immediately - and Latin covers the English meanings printed alongside every entry.
     *
     * @var list<array{int, int}>
     */
    private const BASELINE_RANGES = [
        [0x0020, 0x007E], // Basic Latin (printable) - English meanings, labels, digits
        [0x00A0, 0x00FF], // Latin-1 Supplement - accented letters in loanword glosses
        [0x2010, 0x2027], // General punctuation - dashes, curly quotes, ellipsis
        [0x2030, 0x203A], // ...continued past the line/paragraph and bidi format controls
        [0x3000, 0x3029], // CJK symbols and punctuation - 、。「」『』・
        [0x3030, 0x303F], // ...continued past the combining tone marks - 〜〽
        [0x3041, 0x3096], // Hiragana
        [0x3099, 0x309F], // ...continued past two unassigned slots - combining dakuten, ゝゞ
        [0x30A0, 0x30FF], // Katakana, incl. ー and ・
        [0xFF01, 0xFF60], // Fullwidth forms - fullwidth digits and punctuation in imported data
        [0xFFE0, 0xFFE6], // Fullwidth currency signs
    ];

    /**
     * Code points that carry no glyph in any font: bidi and line/paragraph format controls,
     * zero-width spaces and the byte order mark. A few of these do occur in imported `sense`
     * and `content` values; asking a subsetter for them is pointless noise.
     *
     * @var list<array{int, int}>
     */
    private const NON_PRINTING_RANGES = [
        [0x200B, 0x200F], // Zero-width space/joiners, LTR/RTL marks
        [0x2028, 0x202E], // Line/paragraph separators, bidi embedding controls
        [0x2060, 0x206F], // Word joiner, invisible operators, deprecated format controls
        [0xFEFF, 0xFEFF], // Byte order mark / zero-width no-break space
    ];

    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $outputPath = (string) ($this->option('output') ?: resource_path('fonts/character-set.txt'));

        $characters = $this->baselineCharacters();
        $baselineCount = count($characters);
        $this->info("Seeded {$baselineCount} baseline characters (kana, Japanese punctuation, Latin).");

        foreach (self::SOURCE_COLUMNS as $table => $columns) {
            $before = count($characters);

            DB::table($table)
                ->select(array_merge(['id'], $columns))
                ->orderBy('id')
                ->chunkById($chunkSize, function ($rows) use ($columns, &$characters): void {
                    foreach ($rows as $row) {
                        foreach ($columns as $column) {
                            $this->collect((string) ($row->{$column} ?? ''), $characters);
                        }
                    }
                });

            $added = count($characters) - $before;
            $this->line("  {$table}: +{$added} new characters (running total ".count($characters).').');
        }

        $codePoints = array_map(
            static fn (string $character): int => mb_ord($character, 'UTF-8'),
            array_keys($characters),
        );
        sort($codePoints);

        $lines = array_map(
            static fn (int $codePoint): string => mb_chr($codePoint, 'UTF-8'),
            $codePoints,
        );

        File::ensureDirectoryExists(dirname($outputPath));
        // One character per line, sorted by code point: `pyftsubset --text-file` ignores the
        // newlines, and a new character shows up as a single added line in review.
        File::put($outputPath, implode("\n", $lines)."\n");

        $this->info('Wrote '.count($lines)." characters to {$outputPath}.");

        return self::SUCCESS;
    }

    /**
     * @param array<string, true> $characters
     */
    private function collect(string $value, array &$characters): void
    {
        if ($value === '') {
            return;
        }

        foreach (mb_str_split($value, 1, 'UTF-8') as $character) {
            $codePoint = mb_ord($character, 'UTF-8');

            if ($codePoint === false || $this->isNonPrinting($codePoint)) {
                continue;
            }

            $characters[$character] = true;
        }
    }

    /**
     * Control characters - newlines and tabs inside `sense` and `content_jp` - plus the
     * explicitly invisible format code points.
     */
    private function isNonPrinting(int $codePoint): bool
    {
        if ($codePoint < 0x20 || $codePoint === 0x7F) {
            return true;
        }

        foreach (self::NON_PRINTING_RANGES as [$start, $end]) {
            if ($codePoint >= $start && $codePoint <= $end) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, true>
     */
    private function baselineCharacters(): array
    {
        $characters = [];

        foreach (self::BASELINE_RANGES as [$start, $end]) {
            for ($codePoint = $start; $codePoint <= $end; $codePoint++) {
                $characters[mb_chr($codePoint, 'UTF-8')] = true;
            }
        }

        return $characters;
    }
}
