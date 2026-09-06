<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\JapaneseDataImport\JapaneseDataImporter;
use Database\Seeders\UserTableSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Throwable;

/**
 * One-shot, idempotent bootstrap for a fresh database.
 *
 * Every step checks its own state first, so the command can be re-run safely
 * after a partial failure or on an already-initialised environment.
 */
class SetupApplication extends Command
{
    protected $signature = 'app:setup
        {--environment= : Logical environment name for the Japanese data import sentinel (defaults to APP_ENV)}
        {--with-dev-users : Also seed the local admin and test user accounts}
        {--skip-import : Skip the Japanese dictionary import}';

    protected $description = 'Migrate, seed, install Passport, and import Japanese data in one idempotent run';

    public function __construct(
        private readonly JapaneseDataImporter $importer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $environment = (string) ($this->option('environment') ?: config('app.env'));

        $steps = [
            'Application key' => fn (): string => $this->ensureAppKey(),
            'Database migrations' => fn (): string => $this->runMigrations(),
            'Reference data seeders' => fn (): string => $this->runSeeders(),
            'Passport keys' => fn (): string => $this->ensurePassportKeys(),
            'Passport personal access client' => fn (): string => $this->ensurePersonalAccessClient(),
        ];

        if ((bool) $this->option('with-dev-users')) {
            $steps['Development users'] = fn (): string => $this->seedDevUsers();
        }

        if (! (bool) $this->option('skip-import')) {
            $steps['Japanese data import'] = fn (): string => $this->importJapaneseData($environment);
        }

        $this->info("Setting up application for [{$environment}].");

        foreach ($steps as $label => $step) {
            $this->line("-> {$label}");

            try {
                $result = $step();
            } catch (Throwable $exception) {
                report($exception);
                $this->error("   FAILED: {$exception->getMessage()}");
                $this->error('Setup stopped. Fix the error above and run app:setup again; completed steps are skipped automatically.');

                return self::FAILURE;
            }

            $this->line("   {$result}");
        }

        $this->newLine();
        $this->info('Setup complete.');

        return self::SUCCESS;
    }

    private function ensureAppKey(): string
    {
        if (config('app.key') !== null && config('app.key') !== '') {
            return 'already set';
        }

        Artisan::call('key:generate', ['--force' => true]);

        return 'generated and written to .env';
    }

    private function runMigrations(): string
    {
        Artisan::call('migrate', ['--force' => true]);

        $output = trim(Artisan::output());

        return str_contains($output, 'Nothing to migrate') ? 'nothing to migrate' : 'applied pending migrations';
    }

    private function runSeeders(): string
    {
        Artisan::call('db:seed', ['--force' => true]);

        return 'permissions, roles and object templates are in place';
    }

    private function seedDevUsers(): string
    {
        Artisan::call('db:seed', ['--class' => UserTableSeeder::class, '--force' => true]);

        return 'admin@me.com and johndoe@me.com exist (password: secret123)';
    }

    private function ensurePassportKeys(): string
    {
        $privateKey = Passport::keyPath('oauth-private.key');
        $publicKey = Passport::keyPath('oauth-public.key');

        if (is_file($privateKey) && is_file($publicKey)) {
            return 'already present';
        }

        if (config('passport.private_key') && config('passport.public_key')) {
            return 'provided through environment variables';
        }

        Artisan::call('passport:keys', ['--force' => true]);

        return 'generated in storage/';
    }

    private function ensurePersonalAccessClient(): string
    {
        if (config('passport.personal_access_client.id') && config('passport.personal_access_client.secret')) {
            return 'provided through environment variables';
        }

        $exists = DB::table('oauth_clients')
            ->where('personal_access_client', true)
            ->where('revoked', false)
            ->exists();

        if ($exists) {
            return 'already present';
        }

        Artisan::call('passport:client', [
            '--personal' => true,
            '--name' => config('app.name').' Personal Access Client',
            '--provider' => config('auth.guards.api.provider', 'users'),
            '--no-interaction' => true,
        ]);

        return 'created';
    }

    private function importJapaneseData(string $environment): string
    {
        $result = $this->importer->import(
            environment: $environment,
            allowRerun: false,
            output: function (string $message): void {
                $this->line("   {$message}");
            },
        );

        if ($result['skipped'] === true) {
            return 'already imported, skipped';
        }

        $total = array_sum($result['datasets']);

        return "imported {$total} rows across ".count($result['datasets']).' tables';
    }
}
