<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SetupApplicationCommandTest extends TestCase
{
    use RefreshDatabase;

    public function testSetupSeedsReferenceDataAndPassportClient(): void
    {
        $exitCode = Artisan::call('app:setup', [
            '--skip-import' => true,
            '--no-interaction' => true,
        ]);

        $this->assertSame(0, $exitCode, Artisan::output());

        foreach (UserRole::cases() as $role) {
            $this->assertTrue(Role::where('name', $role->value)->exists(), "Role [{$role->value}] missing.");
        }

        $this->assertSame(
            count(ObjectTemplateType::cases()),
            DB::table('objecttemplates')->count(),
        );

        $this->assertSame(
            1,
            DB::table('oauth_clients')->where('personal_access_client', true)->where('revoked', false)->count(),
        );
    }

    public function testSetupIsIdempotent(): void
    {
        Artisan::call('app:setup', ['--skip-import' => true, '--no-interaction' => true]);
        $exitCode = Artisan::call('app:setup', ['--skip-import' => true, '--no-interaction' => true]);

        $this->assertSame(0, $exitCode, Artisan::output());
        $this->assertStringContainsString('already present', Artisan::output());

        $this->assertSame(
            count(ObjectTemplateType::cases()),
            DB::table('objecttemplates')->count(),
        );

        $this->assertSame(
            1,
            DB::table('oauth_clients')->where('personal_access_client', true)->where('revoked', false)->count(),
        );
    }

    public function testSetupCanSeedDevelopmentUsers(): void
    {
        $exitCode = Artisan::call('app:setup', [
            '--skip-import' => true,
            '--with-dev-users' => true,
            '--no-interaction' => true,
        ]);

        $this->assertSame(0, $exitCode, Artisan::output());
        $this->assertDatabaseHas('users', ['email' => 'admin@me.com']);
        $this->assertDatabaseHas('users', ['email' => 'johndoe@me.com']);
    }
}
