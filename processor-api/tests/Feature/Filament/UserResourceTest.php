<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Domain\Shared\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Infrastructure\Persistence\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => UserRole::COMMON->value, 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => UserRole::ADMIN->value, 'guard_name' => 'api']);

        $this->admin = $this->createUser([UserRole::ADMIN->value]);

        $this->actingAs($this->admin, 'web');
        Filament::setCurrentPanel('admin');
    }

    public function test_admin_can_list_users(): void
    {
        $users = [
            $this->createUser(),
            $this->createUser([UserRole::ADMIN->value]),
        ];

        Livewire::test(ListUsers::class)
            ->assertOk()
            ->assertCanSeeTableRecords($users);
    }

    public function test_admin_can_create_user_with_roles(): void
    {
        $adminRoleId = (int) Role::findByName(UserRole::ADMIN->value, 'api')->getKey();
        $commonRoleId = (int) Role::findByName(UserRole::COMMON->value, 'api')->getKey();

        Livewire::test(CreateUser::class)
            ->assertOk()
            ->fillForm([
                'name' => 'New Admin User',
                'email' => 'new-admin@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'roles' => [$commonRoleId, $adminRoleId],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();

        $createdUser = User::query()->where('email', 'new-admin@example.com')->firstOrFail();

        self::assertSame('New Admin User', $createdUser->name);
        self::assertTrue($createdUser->hasRole(UserRole::COMMON->value));
        self::assertTrue($createdUser->hasRole(UserRole::ADMIN->value));
        self::assertTrue(Hash::check('password123', $createdUser->password));
    }

    public function test_admin_can_edit_safe_user_fields_and_roles(): void
    {
        $user = $this->createUser();
        $adminRoleId = (int) Role::findByName(UserRole::ADMIN->value, 'api')->getKey();
        $commonRoleId = (int) Role::findByName(UserRole::COMMON->value, 'api')->getKey();

        Livewire::test(EditUser::class, [
            'record' => $user->getKey(),
        ])
            ->assertOk()
            ->fillForm([
                'name' => 'Updated User Name',
                'email' => 'updated-user@example.com',
                'password' => '',
                'password_confirmation' => '',
                'roles' => [$commonRoleId, $adminRoleId],
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $user->refresh();

        self::assertSame('Updated User Name', $user->name);
        self::assertSame('updated-user@example.com', $user->email);
        self::assertTrue($user->hasRole(UserRole::COMMON->value));
        self::assertTrue($user->hasRole(UserRole::ADMIN->value));
    }

    public function test_non_admin_cannot_access_the_user_resource_route(): void
    {
        $nonAdmin = $this->createUser();

        $this->actingAs($nonAdmin, 'web')
            ->get('/admin/users')
            ->assertForbidden();
    }

    /**
     * @param array<int, string> $roles
     */
    private function createUser(array $roles = []): User
    {
        $user = User::create([
            'name' => 'Filament Resource Test User',
            'email' => Str::uuid().'@example.com',
            'password' => Hash::make('password'),
            'uuid' => (string) Str::uuid(),
        ]);

        if ($roles !== []) {
            $user->syncRoles($roles);
        }

        return $user;
    }
}
