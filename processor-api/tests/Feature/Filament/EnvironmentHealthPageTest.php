<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Application\Operations\Interfaces\HealthCheckInterface;
use App\Application\Operations\Services\HealthCheckRunner;
use App\Application\Operations\Services\QueueWorkerVerifier;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Operations\DTOs\HealthCheckResultDTO;
use App\Domain\Operations\Enums\HealthStatus;
use App\Filament\Pages\EnvironmentHealth;
use App\Infrastructure\Persistence\Models\AdminActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RuntimeException;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\TestCase;

class EnvironmentHealthPageTest extends TestCase
{
    use InteractsWithAdminPanel;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array', 'queue.default' => 'sync']);
        $this->seedAdminPanel();
        $this->actingAsPanelUser($this->createAdmin());
    }

    public function test_page_shows_release_drivers_connectivity_worker_and_data(): void
    {
        config(['app.release' => 'release-2026-10-11']);

        Livewire::test(EnvironmentHealth::class)
            ->assertOk()
            ->assertSee('release-2026-10-11')
            ->assertSee('Drivers')
            ->assertSee('Database')
            ->assertSee('Answered a query')
            ->assertSee('Last smoke check')
            ->assertSee('Latest dictionary import');

        self::assertSame(ActivityAction::EnvironmentViewed, AdminActivity::query()->sole()->action);
    }

    public function test_no_secret_value_reaches_the_page(): void
    {
        $secrets = [
            'sentry.dsn' => 'https://sentry-secret-value@o0.ingest.sentry.io/1',
            'broadcasting.connections.reverb.secret' => 'reverb-secret-value',
            'horizon.basic_auth.password' => 'horizon-secret-value',
            'mail.mailers.smtp.password' => 'mail-secret-value',
        ];
        config($secrets);

        $html = Livewire::test(EnvironmentHealth::class)->assertOk()->html();

        foreach ([...array_values($secrets), (string) config('app.key')] as $value) {
            self::assertStringNotContainsString($value, $html);
        }

        self::assertStringContainsString('Sentry DSN: set', $html);
        self::assertStringContainsString('Horizon basic auth: set', $html);
    }

    public function test_a_failing_dependency_renders_as_failed_without_breaking_the_page(): void
    {
        $this->app->instance(HealthCheckRunner::class, new HealthCheckRunner([
            new class implements HealthCheckInterface
            {
                public function name(): string
                {
                    return 'Database';
                }

                public function check(): HealthCheckResultDTO
                {
                    throw new RuntimeException('connection refused');
                }
            },
        ]));

        Livewire::test(EnvironmentHealth::class)
            ->assertOk()
            ->assertSee('Failed')
            ->assertSee('RuntimeException: connection refused');
    }

    public function test_runner_reports_overruns_and_exceptions(): void
    {
        $results = (new HealthCheckRunner([
            new class implements HealthCheckInterface
            {
                public function name(): string
                {
                    return 'Slow';
                }

                public function check(): HealthCheckResultDTO
                {
                    usleep((HealthCheckRunner::BUDGET_MS + 50) * 1000);

                    return new HealthCheckResultDTO('Slow', HealthStatus::Ok, 'fine');
                }
            },
        ]))->runAll();

        self::assertSame(HealthStatus::Failed, $results[0]->status);
        self::assertStringContainsString('budget', $results[0]->detail);
    }

    public function test_worker_check_runs_the_shared_verifier_and_is_recorded(): void
    {
        Livewire::test(EnvironmentHealth::class)
            ->callAction('runWorkerCheck')
            ->assertNotified();

        $last = app(QueueWorkerVerifier::class)->lastResult();
        self::assertNotNull($last);
        self::assertTrue($last->succeeded);

        $row = AdminActivity::query()->where('action', ActivityAction::WorkerCheckRun)->sole();
        self::assertTrue($row->context['succeeded']);

        Livewire::test(EnvironmentHealth::class)->assertSee('Passed on queue default');
    }

    public function test_operators_without_manage_see_the_page_but_cannot_run_the_check(): void
    {
        $this->actingAsPanelUser($this->createUserWithPermissions(['operations.view']));

        Livewire::test(EnvironmentHealth::class)
            ->assertOk()
            ->assertActionHidden('runWorkerCheck');
    }

    public function test_page_is_forbidden_without_operations_view(): void
    {
        $this->actingAs($this->createUserWithPermissions(['articles.view']), 'web')
            ->get('/admin/operations/environment')
            ->assertForbidden();
    }
}
