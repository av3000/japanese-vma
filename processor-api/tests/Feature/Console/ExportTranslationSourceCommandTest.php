<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Application\Translations\Interfaces\Sources\EnglishSourceCatalogueInterface;
use Tests\TestCase;

class ExportTranslationSourceCommandTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = sys_get_temp_dir().'/jvma-en-snapshot-'.bin2hex(random_bytes(6)).'/en.generated.json';
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }

        if (is_dir(dirname($this->path))) {
            rmdir(dirname($this->path));
        }

        parent::tearDown();
    }

    /** @return array<string, string> */
    private function catalogue(): array
    {
        return app(EnglishSourceCatalogueInterface::class)->messages();
    }

    public function test_export_writes_the_flattened_catalogue_as_json(): void
    {
        $this->artisan('translations:export-source', ['--path' => $this->path])->assertSuccessful();

        $written = file_get_contents($this->path);

        $this->assertSame($this->catalogue(), json_decode($written, true));
        $this->assertStringEndsWith("}\n", $written);
        $this->assertStringContainsString("\n  \"account.log_in\": \"Log In\",\n", $written);
    }

    public function test_check_passes_when_the_snapshot_is_current(): void
    {
        $this->artisan('translations:export-source', ['--path' => $this->path])->assertSuccessful();

        $this->artisan('translations:export-source', ['--check' => true, '--path' => $this->path])->assertSuccessful();
    }

    public function test_check_fails_when_a_value_is_stale_and_leaves_the_file_alone(): void
    {
        $stale = $this->catalogue();
        $stale['account.log_in'] = 'Sign in';
        $this->writeSnapshot(json_encode($stale));

        $this->artisan('translations:export-source', ['--check' => true, '--path' => $this->path])->assertFailed();

        $this->assertSame($stale, json_decode(file_get_contents($this->path), true));
    }

    public function test_check_fails_when_a_key_is_missing_from_the_snapshot(): void
    {
        $stale = $this->catalogue();
        array_pop($stale);
        $this->writeSnapshot(json_encode($stale));

        $this->artisan('translations:export-source', ['--check' => true, '--path' => $this->path])->assertFailed();
    }

    public function test_check_fails_when_the_snapshot_does_not_exist(): void
    {
        $this->artisan('translations:export-source', ['--check' => true, '--path' => $this->path])->assertFailed();

        $this->assertFileDoesNotExist($this->path);
    }

    public function test_check_fails_when_the_snapshot_is_not_json(): void
    {
        $this->writeSnapshot('not json');

        $this->artisan('translations:export-source', ['--check' => true, '--path' => $this->path])->assertFailed();
    }

    public function test_check_ignores_how_a_formatter_laid_the_file_out(): void
    {
        $this->writeSnapshot(json_encode($this->catalogue(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); // four-space indent

        $this->artisan('translations:export-source', ['--check' => true, '--path' => $this->path])->assertSuccessful();
    }

    public function test_the_committed_client_snapshot_is_current(): void
    {
        $committed = base_path('../client/src/i18n/en.generated.json');

        if (! is_file($committed)) {
            $this->markTestSkipped('The client directory is not mounted in this environment (the Docker lane mounts processor-api only).');
        }

        $this->artisan('translations:export-source', ['--check' => true])->assertSuccessful();
    }

    private function writeSnapshot(string $contents): void
    {
        mkdir(dirname($this->path), 0777, true);
        file_put_contents($this->path, $contents);
    }
}
