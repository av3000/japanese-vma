<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Collection;

class MigrateLegacyUserRoles extends Command
{
    protected $signature = 'roles:migrate-legacy-users {--dry-run : Preview without executing}';

    protected $description = 'ONE-TIME: Migrate existing user-role assignments from legacy tables to Spatie';

    private bool $isDryRun;
    private Collection $legacyRoles;
    private Collection $spatieRoles;
    private array $roleMapping = [];

    public function handle(): int
    {
        $this->isDryRun = $this->option('dry-run');

        if ($this->isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No data will be modified');
            $this->newLine();
        }

        if (!$this->checkLegacyTablesExist()) {
            return self::FAILURE;
        }

        $this->loadData();
        $this->displayLegacyData();
        $this->displaySpatieRoles();

        if (!$this->buildRoleMapping()) {
            return self::FAILURE;
        }

        $result = $this->migrateUserAssignments();
        $this->displaySummary($result);

        return self::SUCCESS;
    }

    private function checkLegacyTablesExist(): bool
    {
        $hasLegacyTables = DB::getSchemaBuilder()->hasTable('roles_legacy')
            && DB::getSchemaBuilder()->hasTable('user_role_legacy');

        if (!$hasLegacyTables) {
            $this->error('❌ Legacy tables (roles_legacy, user_role_legacy) not found.');
            $this->info('Migration already completed or tables were dropped.');
        }

        return $hasLegacyTables;
    }

    private function loadData(): void
    {
        $this->legacyRoles = DB::table('roles_legacy')->get(['id', 'name']);
        $this->spatieRoles = Role::where('guard_name', 'api')->get(['id', 'name']);

        $this->info('Starting user role assignment migration...');
        $this->newLine();
    }

    private function displayLegacyData(): void
    {
        $legacyAssignmentsCount = DB::table('user_role_legacy')->count();

        $this->info("📊 Legacy Data:");
        $this->table(
            ['ID', 'Role Name'],
            $this->legacyRoles->map(fn($r) => [$r->id, $r->name])->toArray()
        );
        $this->info("Total legacy roles: {$this->legacyRoles->count()}");
        $this->info("Total legacy user assignments: {$legacyAssignmentsCount}");
        $this->newLine();
    }

    private function displaySpatieRoles(): void
    {
        $this->info("📊 Spatie Roles (target):");
        $this->table(
            ['ID', 'Role Name', 'Guard'],
            $this->spatieRoles->map(fn($r) => [$r->id, $r->name, 'api'])->toArray()
        );
        $this->newLine();
    }

    private function buildRoleMapping(): bool
    {
        // Custom mapping: legacy name → Spatie name
        $nameMappings = [
            'admin' => 'admin',
            'testuser' => 'common',
        ];

        $unmappedRoles = [];

        foreach ($this->legacyRoles as $legacyRole) {
            $targetName = $nameMappings[$legacyRole->name] ?? null;

            if (!$targetName) {
                $unmappedRoles[] = $legacyRole->name;
                $this->error("  ❌ No mapping defined for: '{$legacyRole->name}'");
                continue;
            }

            $spatieRole = $this->spatieRoles->firstWhere('name', $targetName);

            if ($spatieRole) {
                $this->roleMapping[$legacyRole->id] = $spatieRole;
                $this->line("  ✅ Mapped: '{$legacyRole->name}' (legacy ID {$legacyRole->id}) → '{$spatieRole->name}' (Spatie ID {$spatieRole->id})");
            } else {
                $unmappedRoles[] = $legacyRole->name;
                $this->error("  ❌ Spatie role '{$targetName}' not found for legacy '{$legacyRole->name}'");
            }
        }

        if (!empty($unmappedRoles)) {
            $this->newLine();
            $this->error('⚠️  Cannot proceed: Some legacy roles cannot be mapped.');
            $this->error('Unmapped roles: ' . implode(', ', $unmappedRoles));
            return false;
        }

        $this->newLine();
        return true;
    }

    private function migrateUserAssignments(): array
    {
        $this->info("👥 User Role Assignments to Migrate:");

        $legacyAssignments = DB::table('user_role_legacy')->get();
        $insertData = [];
        $migratedCount = 0;
        $skippedCount = 0;

        foreach ($legacyAssignments as $assignment) {
            $spatieRole = $this->roleMapping[$assignment->role_id] ?? null;

            if (!$spatieRole) {
                $this->warn("  ⚠️  Skipping user {$assignment->user_id}: No role mapping for role_id {$assignment->role_id}");
                $skippedCount++;
                continue;
            }

            $this->line("  User {$assignment->user_id} → {$spatieRole->name}");

            if (!$this->isDryRun) {
                $insertData[] = [
                    'role_id' => $spatieRole->id,
                    'model_type' => 'App\Http\User',
                    'model_id' => $assignment->user_id,
                ];
            }

            $migratedCount++;
        }

        $this->newLine();

        if (!$this->isDryRun && !empty($insertData)) {
            DB::table('model_has_roles')->insertOrIgnore($insertData);
            $this->info("✅ Inserted {$migratedCount} role assignments into model_has_roles");
        }

        return [
            'total' => $legacyAssignments->count(),
            'migrated' => $migratedCount,
            'skipped' => $skippedCount,
        ];
    }

    private function displaySummary(array $result): void
    {
        $this->info('📈 Migration Summary:');

        if (!$this->isDryRun) {
            $finalCount = DB::table('model_has_roles')
                ->where('model_type', 'App\Http\User')
                ->count();

            $this->table(
                ['Metric', 'Count'],
                [
                    ['Legacy user assignments', $result['total']],
                    ['Successfully migrated', $result['migrated']],
                    ['Skipped', $result['skipped']],
                    ['Current Spatie assignments', $finalCount],
                    ['Match', $result['total'] === $finalCount ? '✅ Yes' : '⚠️  No'],
                ]
            );

            $this->newLine();
            $this->info('✅ Migration completed successfully!');
            $this->newLine();
            $this->info('Next steps:');
            $this->line('  1. Test role checks in your application');
            $this->line('  2. After verification, drop legacy tables');
        } else {
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Legacy roles', $this->legacyRoles->count()],
                    ['Legacy user assignments', $result['total']],
                    ['Would migrate', $result['migrated']],
                    ['Would skip', $result['skipped']],
                ]
            );

            $this->newLine();
            $this->warn('🔍 Dry run completed. Run without --dry-run to execute migration.');
        }
    }
}
