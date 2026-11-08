<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Translations\Interfaces\Sources\EnglishSourceCatalogueInterface;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use JsonException;

/**
 * Writes the flattened English catalogue to `client/src/i18n/en.generated.json`, the snapshot the
 * React client renders before any bundle has arrived (epic #659, plan decision P1).
 *
 * The file is committed, like the generated Orval client. Run this in the same commit as any
 * change to `resources/lang/en/ui.php`; `--check` exits non-zero when the file is out of date.
 */
class ExportTranslationSource extends Command
{
    protected $signature = 'translations:export-source
        {--check : Do not write; exit 1 when the snapshot differs from the catalogue}
        {--path= : The snapshot file (defaults to client/src/i18n/en.generated.json in the repository)}';

    protected $description = 'Export the English UI catalogue to the client snapshot, or verify the snapshot is current';

    public function handle(EnglishSourceCatalogueInterface $catalogue, Filesystem $files): int
    {
        $explicitPath = (string) $this->option('path');
        $path = $explicitPath !== '' ? $explicitPath : base_path('../client/src/i18n/en.generated.json');
        $messages = $catalogue->messages();

        if ($this->option('check')) {
            return $this->check($files, $path, $messages);
        }

        // The deployed container and the Docker lane hold processor-api/ only. Without this guard
        // the default path would create ../client/... outside the app and still report success.
        if ($explicitPath === '' && ! $files->isDirectory(dirname($path, 2))) {
            $this->error(dirname($path, 2).' does not exist: run this from a full checkout, or pass --path.');

            return self::FAILURE;
        }

        $files->ensureDirectoryExists(dirname($path));
        $files->put($path, $this->encode($messages));

        $this->info(sprintf('Wrote %d English keys to %s.', count($messages), $path));

        return self::SUCCESS;
    }

    /**
     * Compares the decoded content, not the bytes, so a formatter that re-indents the file does
     * not fail the check. Key order still counts: it is the catalogue's order.
     *
     * @param array<string, string> $messages
     */
    private function check(Filesystem $files, string $path, array $messages): int
    {
        if (! $files->exists($path)) {
            $this->error("{$path} does not exist. Run `php artisan translations:export-source`.");

            return self::FAILURE;
        }

        try {
            $snapshot = json_decode($files->get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->error("{$path} is not valid JSON. Run `php artisan translations:export-source`.");

            return self::FAILURE;
        }

        if ($snapshot !== $messages) {
            $this->error("{$path} is out of date. Run `php artisan translations:export-source` and commit it.");

            return self::FAILURE;
        }

        $this->info('The English snapshot is up to date.');

        return self::SUCCESS;
    }

    /**
     * Two-space indentation and a final newline, the way Prettier formats JSON in the client.
     *
     * @param array<string, string> $messages
     */
    private function encode(array $messages): string
    {
        $json = json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return preg_replace_callback(
            '/^ +/m',
            static fn (array $indent): string => str_repeat(' ', intdiv(strlen($indent[0]), 2)),
            $json,
        )."\n";
    }
}
