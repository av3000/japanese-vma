<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Activity\Enums\ActivitySource;
use App\Domain\Activity\Enums\ActivitySubjectType;
use App\Domain\Shared\Enums\UserRole;
use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Filament\Resources\Activities\Pages\ViewActivity;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Infrastructure\Persistence\Models\AdminActivity;
use App\Infrastructure\Persistence\Models\User;
use Database\Seeders\PermissionSeeder;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        Role::firstOrCreate(['name' => UserRole::COMMON->value, 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => UserRole::ADMIN->value, 'guard_name' => 'api']);

        $this->admin = $this->createUser([UserRole::ADMIN->value], 'Panel Admin');
    }

    public function test_panel_role_create_update_and_delete_each_write_one_row(): void
    {
        $this->actingAsPanelUser($this->admin);

        Livewire::test(CreateRole::class)
            ->fillForm([
                'name' => 'editor',
                'guard_name' => 'api',
                'permission_groups.content' => ['articles.view'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $role = Role::findByName('editor', 'api');

        Livewire::test(EditRole::class, ['record' => $role->getKey()])
            ->fillForm([
                'name' => 'content-editor',
                'permission_groups.content' => ['articles.view', 'articles.update'],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        Livewire::test(EditRole::class, ['record' => $role->getKey()])
            ->callAction(DeleteAction::class);

        $rows = AdminActivity::query()->orderBy('id')->get();

        self::assertSame(
            [ActivityAction::RoleCreated, ActivityAction::RoleUpdated, ActivityAction::RoleDeleted],
            $rows->pluck('action')->all(),
        );
        self::assertTrue($rows->every(fn (AdminActivity $row): bool => $row->actor_id === $this->admin->id
            && $row->actor_name === 'Panel Admin'
            && $row->source === ActivitySource::Panel
            && $row->subject_type === ActivitySubjectType::Role));

        self::assertSame(['name' => 'editor', 'guard_name' => 'api', 'permissions' => ['articles.view']], $rows[0]->changes['after']);
        self::assertSame(
            ['name' => 'editor', 'permissions' => ['articles.view']],
            $rows[1]->changes['before'],
        );
        self::assertSame(
            ['name' => 'content-editor', 'permissions' => ['articles.update', 'articles.view']],
            $rows[1]->changes['after'],
        );
        self::assertSame('content-editor', $rows[2]->subject_id);
    }

    public function test_saving_a_role_without_changes_writes_nothing(): void
    {
        $this->actingAsPanelUser($this->admin);
        $role = Role::create(['name' => 'editor', 'guard_name' => 'api']);

        Livewire::test(EditRole::class, ['record' => $role->getKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        self::assertSame(0, AdminActivity::query()->count());
    }

    public function test_api_role_create_writes_a_row_with_the_api_source(): void
    {
        Passport::actingAs($this->admin, ['*'], 'api');

        $this->postJson('/api/v1/admin/roles', ['name' => 'reviewer', 'guard_name' => 'api'])
            ->assertOk();

        $row = AdminActivity::query()->sole();

        self::assertSame(ActivityAction::RoleCreated, $row->action);
        self::assertSame(ActivitySource::Api, $row->source);
        self::assertSame($this->admin->id, $row->actor_id);
    }

    public function test_api_role_assignment_writes_a_row_on_the_user(): void
    {
        Passport::actingAs($this->admin, ['*'], 'api');
        Role::create(['name' => 'reviewer', 'guard_name' => 'api']);
        $member = $this->createUser([], 'Member');

        $this->postJson("/api/v1/admin/users/{$member->uuid}/roles", ['role' => 'reviewer'])
            ->assertOk();

        $row = AdminActivity::query()->sole();

        self::assertSame(ActivityAction::UserRoleAssigned, $row->action);
        self::assertSame((string) $member->uuid, $row->subject_id);
        self::assertSame(['role' => 'reviewer'], $row->context);
    }

    public function test_api_role_delete_writes_a_row_with_the_api_source(): void
    {
        Passport::actingAs($this->admin, ['*'], 'api');
        Role::create(['name' => 'reviewer', 'guard_name' => 'api']);

        $this->deleteJson('/api/v1/admin/roles/reviewer')
            ->assertOk();

        $row = AdminActivity::query()->sole();

        self::assertSame(ActivityAction::RoleDeleted, $row->action);
        self::assertSame(ActivitySource::Api, $row->source);
        self::assertSame($this->admin->id, $row->actor_id);
        self::assertSame('reviewer', $row->changes['before']['name']);
    }

    public function test_api_role_removal_writes_a_row_on_the_user(): void
    {
        Passport::actingAs($this->admin, ['*'], 'api');
        Role::create(['name' => 'reviewer', 'guard_name' => 'api']);
        $member = $this->createUser([], 'Member');
        $member->assignRole('reviewer');

        $this->deleteJson("/api/v1/admin/users/{$member->uuid}/roles", ['role' => 'reviewer'])
            ->assertOk();

        $row = AdminActivity::query()->sole();

        self::assertSame(ActivityAction::UserRoleRemoved, $row->action);
        self::assertSame(ActivitySource::Api, $row->source);
        self::assertSame((string) $member->uuid, $row->subject_id);
        self::assertSame(['role' => 'reviewer'], $row->context);
    }

    public function test_panel_user_create_and_update_write_rows_without_password_values(): void
    {
        $this->actingAsPanelUser($this->admin);
        $commonRoleId = (int) Role::findByName(UserRole::COMMON->value, 'api')->getKey();

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Created Person',
                'email' => 'created@example.com',
                'password' => 'secret-password-1',
                'password_confirmation' => 'secret-password-1',
                'roles' => [$commonRoleId],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = User::query()->where('email', 'created@example.com')->sole();

        Livewire::test(EditUser::class, ['record' => $created->getKey()])
            ->fillForm([
                'name' => 'Renamed Person',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $rows = AdminActivity::query()->orderBy('id')->get();

        self::assertSame([ActivityAction::UserCreated, ActivityAction::UserUpdated], $rows->pluck('action')->all());
        self::assertSame(['name' => 'Created Person'], $rows[1]->changes['before']);
        self::assertSame(['name' => 'Renamed Person'], $rows[1]->changes['after']);
        self::assertNull($rows[1]->context);
        self::assertTrue(Hash::check('secret-password-1', $created->fresh()->password));

        foreach ($rows as $row) {
            $serialised = json_encode([$row->changes, $row->context]);
            self::assertStringNotContainsString('secret-password', (string) $serialised);
            self::assertStringNotContainsString('password":', (string) json_encode($row->changes));
        }
    }

    public function test_changing_email_in_the_panel_clears_its_verification(): void
    {
        $this->actingAsPanelUser($this->admin);
        $member = $this->createUser([], 'Member');
        $member->forceFill(['email_verified_at' => now()])->save();

        Livewire::test(EditUser::class, ['record' => $member->getKey()])
            ->fillForm(['email' => 'new-address@example.com'])
            ->call('save')
            ->assertHasNoFormErrors();

        self::assertNull($member->fresh()->email_verified_at);
        self::assertNull(AdminActivity::query()->sole()->changes['after']['email_verified_at']);
    }

    public function test_activity_view_permission_lists_and_views_rows_without_mutation_actions(): void
    {
        $auditorRole = Role::create(['name' => 'auditor', 'guard_name' => 'api']);
        $auditorRole->givePermissionTo('activity.view');
        $auditor = $this->createUser(['auditor'], 'Auditor');
        $row = $this->recordRow(ActivityAction::RoleCreated, 'editor');

        $this->actingAsPanelUser($auditor);

        Livewire::test(ListActivities::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$row])
            ->assertActionDoesNotExist('create');

        Livewire::test(ViewActivity::class, ['record' => $row->getKey()])
            ->assertOk()
            ->assertSee('editor')
            ->assertActionDoesNotExist('edit')
            ->assertActionDoesNotExist('delete');
    }

    public function test_activity_log_is_forbidden_without_activity_view(): void
    {
        $supportRole = Role::create(['name' => 'support', 'guard_name' => 'api']);
        $supportRole->givePermissionTo('users.view');
        $support = $this->createUser(['support'], 'Support');

        $this->actingAs($support, 'web')->get('/admin/activity')->assertForbidden();
    }

    public function test_activity_log_filters_by_action_and_subject(): void
    {
        $this->actingAsPanelUser($this->admin);
        $created = $this->recordRow(ActivityAction::RoleCreated, 'editor');
        $assigned = $this->recordRow(ActivityAction::UserRoleAssigned, 'Member', ActivitySubjectType::User);

        Livewire::test(ListActivities::class)
            ->filterTable('action', ActivityAction::RoleCreated->value)
            ->assertCanSeeTableRecords([$created])
            ->assertCanNotSeeTableRecords([$assigned]);

        Livewire::test(ListActivities::class)
            ->filterTable('subject_type', ActivitySubjectType::User->value)
            ->assertCanSeeTableRecords([$assigned])
            ->assertCanNotSeeTableRecords([$created]);
    }

    private function recordRow(
        ActivityAction $action,
        string $label,
        ActivitySubjectType $subjectType = ActivitySubjectType::Role,
    ): AdminActivity {
        return AdminActivity::query()->create([
            'actor_id' => $this->admin->id,
            'actor_name' => 'Panel Admin',
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $label,
            'subject_label' => $label,
            'changes' => ['before' => [], 'after' => ['name' => $label]],
            'source' => ActivitySource::Panel,
        ]);
    }

    private function actingAsPanelUser(User $user): void
    {
        $this->actingAs($user, 'web');
        Filament::setCurrentPanel('admin');
    }

    /**
     * @param array<int, string> $roles
     */
    private function createUser(array $roles, string $name): User
    {
        $user = User::create([
            'name' => $name,
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
