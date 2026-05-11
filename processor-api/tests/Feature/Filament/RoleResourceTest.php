<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Application\Users\Support\PermissionCatalog;
use App\Domain\Shared\Enums\UserRole;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Infrastructure\Persistence\Models\User;
use Database\Seeders\PermissionSeeder;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        Role::firstOrCreate(['name' => UserRole::COMMON->value, 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => UserRole::ADMIN->value, 'guard_name' => 'api']);

        $this->admin = $this->createUser([UserRole::ADMIN->value]);

        $this->actingAs($this->admin, 'web');
        Filament::setCurrentPanel('admin');
    }

    public function test_admin_can_access_role_resource_index_route(): void
    {
        $this->get('/admin/roles')->assertOk();
    }

    public function test_non_admin_cannot_access_role_resource_route(): void
    {
        $nonAdmin = $this->createUser();

        $this->actingAs($nonAdmin, 'web')
            ->get('/admin/roles')
            ->assertForbidden();
    }

    public function test_admin_can_list_roles(): void
    {
        $roles = [
            Role::findByName(UserRole::ADMIN->value, 'api'),
            Role::findByName(UserRole::COMMON->value, 'api'),
            Role::create(['name' => 'editor', 'guard_name' => 'api']),
        ];

        Livewire::test(ListRoles::class)
            ->assertOk()
            ->assertCanSeeTableRecords($roles);
    }

    public function test_admin_can_create_a_role_with_grouped_permissions(): void
    {
        Livewire::test(CreateRole::class)
            ->assertOk()
            ->assertSchemaComponentExists('permission-group-admin')
            ->assertSchemaComponentExists('permission-group-content')
            ->fillForm([
                'name' => 'editor',
                'guard_name' => 'api',
                'permission_groups.admin' => [
                    'roles.view',
                ],
                'permission_groups.content' => [
                    'articles.view',
                    'articles.update',
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();

        $role = Role::findByName('editor', 'api');

        self::assertSame(
            ['articles.update', 'articles.view', 'roles.view'],
            $role->permissions()->pluck('name')->sort()->values()->all(),
        );
    }

    public function test_admin_can_edit_a_non_system_role_and_change_permissions(): void
    {
        $role = Role::create(['name' => 'editor', 'guard_name' => 'api']);
        $role->givePermissionTo(['roles.view', 'articles.view']);

        Livewire::test(EditRole::class, [
            'record' => $role->getKey(),
        ])
            ->assertOk()
            ->assertSchemaStateSet([
                'name' => 'editor',
                'guard_name' => 'api',
                'permission_groups.admin' => ['roles.view'],
                'permission_groups.content' => ['articles.view'],
            ])
            ->fillForm([
                'name' => 'content-editor',
                'guard_name' => 'api',
                'permission_groups.admin' => [],
                'permission_groups.content' => [
                    'articles.view',
                    'articles.update',
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $updatedRole = Role::findByName('content-editor', 'api');

        self::assertSame(
            ['articles.update', 'articles.view'],
            $updatedRole->permissions()->pluck('name')->sort()->values()->all(),
        );
    }

    public function test_system_role_identity_fields_are_locked_and_delete_action_is_hidden(): void
    {
        $adminRole = Role::findByName(UserRole::ADMIN->value, 'api');

        Livewire::test(EditRole::class, [
            'record' => $adminRole->getKey(),
        ])
            ->assertOk()
            ->assertFormFieldDisabled('name')
            ->assertFormFieldDisabled('guard_name')
            ->assertActionHidden(DeleteAction::class);
    }

    public function test_role_with_active_assignments_cannot_be_deleted(): void
    {
        $role = Role::create(['name' => 'moderator', 'guard_name' => 'api']);
        $assignedUser = $this->createUser();
        $assignedUser->assignRole('moderator');

        Livewire::test(EditRole::class, [
            'record' => $role->getKey(),
        ])
            ->assertOk()
            ->assertActionHidden(DeleteAction::class);
    }

    public function test_permission_catalog_groups_are_available_to_the_resource(): void
    {
        $groupKeys = array_keys(PermissionCatalog::groups());

        Livewire::test(CreateRole::class)
            ->assertOk();

        self::assertSame(['admin', 'content'], $groupKeys);
    }

    /**
     * @param array<int, string> $roles
     */
    private function createUser(array $roles = []): User
    {
        $user = User::create([
            'name' => 'Filament Role Test User',
            'email' => Str::uuid() . '@example.com',
            'password' => Hash::make('password'),
            'uuid' => (string) Str::uuid(),
        ]);

        if ($roles !== []) {
            $user->syncRoles($roles);
        }

        return $user;
    }
}
