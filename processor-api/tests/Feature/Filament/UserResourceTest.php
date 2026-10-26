<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\RelationManagers\ArticlesRelationManager;
use App\Filament\Resources\Users\RelationManagers\CataloguesRelationManager;
use App\Filament\Resources\Users\RelationManagers\CommentsRelationManager;
use App\Filament\Resources\Users\RelationManagers\PostsRelationManager;
use App\Infrastructure\Persistence\Models\AdminActivity;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\Post;
use App\Infrastructure\Persistence\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Token;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use InteractsWithAdminPanel;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAdminPanel();
        $this->admin = $this->createAdmin();
        $this->actingAsPanelUser($this->admin);
    }

    public function test_admin_can_list_users(): void
    {
        $users = [
            $this->createPanelUser(),
            $this->createPanelUser('Second admin', [UserRole::ADMIN->value]),
        ];

        Livewire::test(ListUsers::class)
            ->assertOk()
            ->assertCanSeeTableRecords($users);
    }

    public function test_created_user_starts_with_the_base_role(): void
    {
        Livewire::test(CreateUser::class)
            ->assertOk()
            ->assertFormFieldDoesNotExist('roles')
            ->fillForm([
                'name' => 'New User',
                'email' => 'new-user@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();

        $createdUser = User::query()->where('email', 'new-user@example.com')->firstOrFail();

        self::assertSame(['common'], $createdUser->roles->pluck('name')->all());
        self::assertTrue(Hash::check('password123', $createdUser->password));
    }

    public function test_admin_can_edit_safe_user_fields(): void
    {
        $user = $this->createPanelUser();

        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->assertOk()
            ->fillForm([
                'name' => 'Updated User Name',
                'email' => 'updated-user@example.com',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $user->refresh();

        self::assertSame('Updated User Name', $user->name);
        self::assertSame('updated-user@example.com', $user->email);
    }

    public function test_admin_can_view_user_details(): void
    {
        $user = $this->createPanelUser('Viewed Person');

        Livewire::test(ViewUser::class, ['record' => $user->getKey()])
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email)
            ->assertSee(UserRole::COMMON->value);
    }

    public function test_non_admin_cannot_access_the_user_resource_route(): void
    {
        $nonAdmin = $this->createPanelUser();

        $this->actingAs($nonAdmin, 'web')
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_users_can_be_filtered_by_role_verification_registration_and_content(): void
    {
        $verified = $this->createPanelUser('Verified');
        $verified->forceFill(['email_verified_at' => now(), 'created_at' => now()->subDays(40)])->save();
        $author = $this->createPanelUser('Author');
        Article::factory()->create(['user_id' => $author->id]);
        $poster = $this->createPanelUser('Poster');
        Post::factory()->create(['user_id' => $poster->id]);
        $editorRole = Role::create(['name' => 'editor', 'guard_name' => 'api']);
        $editor = $this->createPanelUser('Editor', [$editorRole->name]);

        Livewire::test(ListUsers::class)
            ->filterTable('roles', $editorRole->getKey())
            ->assertCanSeeTableRecords([$editor])
            ->assertCanNotSeeTableRecords([$author, $poster]);

        Livewire::test(ListUsers::class)
            ->filterTable('email_verified_at', true)
            ->assertCanSeeTableRecords([$verified])
            ->assertCanNotSeeTableRecords([$author]);

        Livewire::test(ListUsers::class)
            ->filterTable('created_at_range', ['until' => now()->subDays(30)->toDateString()])
            ->assertCanSeeTableRecords([$verified])
            ->assertCanNotSeeTableRecords([$author]);

        Livewire::test(ListUsers::class)
            ->filterTable('has_articles')
            ->assertCanSeeTableRecords([$author])
            ->assertCanNotSeeTableRecords([$poster]);

        Livewire::test(ListUsers::class)
            ->filterTable('has_posts')
            ->assertCanSeeTableRecords([$poster])
            ->assertCanNotSeeTableRecords([$author]);
    }

    public function test_view_page_lists_the_users_content(): void
    {
        $author = $this->createPanelUser('Author');
        $article = Article::factory()->create(['user_id' => $author->id, 'status' => ArticleStatus::PENDING, 'title_jp' => '著者の記事']);
        $catalogue = Catalogue::factory()->create(['user_id' => $author->id, 'title' => 'Author list']);
        $post = Post::factory()->create(['user_id' => $author->id, 'title' => 'Author post']);

        $relations = [
            ArticlesRelationManager::class => $article,
            CataloguesRelationManager::class => $catalogue,
            PostsRelationManager::class => $post,
        ];

        foreach ($relations as $relationManager => $record) {
            Livewire::test($relationManager, ['ownerRecord' => $author, 'pageClass' => ViewUser::class])
                ->assertOk()
                ->assertCanSeeTableRecords([$record]);
        }

        Livewire::test(CommentsRelationManager::class, ['ownerRecord' => $author, 'pageClass' => ViewUser::class])
            ->assertOk();
    }

    public function test_mark_verified_sets_the_timestamp_and_records_activity(): void
    {
        CarbonImmutable::setTestNow('2026-10-11 09:30:00');
        $user = $this->createPanelUser();

        Livewire::test(ViewUser::class, ['record' => $user->getKey()])
            ->callAction('markEmailVerified')
            ->assertNotified('Email address marked as verified.');

        self::assertSame('2026-10-11 09:30:00', $user->fresh()->email_verified_at?->toDateTimeString());
        self::assertSame(ActivityAction::UserEmailVerified, AdminActivity::query()->sole()->action);

        CarbonImmutable::setTestNow();
    }

    public function test_a_non_admin_holding_users_update_can_mark_an_address_verified(): void
    {
        $this->actingAsPanelUser($this->createUserWithPermissions(['users.view', 'users.update'], 'Support'));
        $user = $this->createPanelUser();

        Livewire::test(ViewUser::class, ['record' => $user->getKey()])
            ->callAction('markEmailVerified')
            ->assertNotified('Email address marked as verified.');

        self::assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_revoke_tokens_revokes_every_token_and_records_activity(): void
    {
        $user = $this->createPanelUser();
        foreach (['one', 'two'] as $name) {
            Token::query()->forceCreate([
                'id' => Str::random(80),
                'user_id' => $user->id,
                'client_id' => 1,
                'name' => $name,
                'scopes' => [],
                'revoked' => false,
                'expires_at' => now()->addDay(),
            ]);
        }

        Livewire::test(ViewUser::class, ['record' => $user->getKey()])
            ->callAction('revokeTokens')
            ->assertNotified('All sessions were revoked.');

        self::assertSame(0, Token::query()->where('user_id', $user->id)->where('revoked', false)->count());
        self::assertSame(ActivityAction::UserTokensRevoked, AdminActivity::query()->sole()->action);
    }

    public function test_assign_and_remove_role_go_through_the_role_service(): void
    {
        Role::create(['name' => 'editor', 'guard_name' => 'api']);
        $user = $this->createPanelUser();

        Livewire::test(ViewUser::class, ['record' => $user->getKey()])
            ->callAction('assignRole', ['role' => 'editor'])
            ->assertNotified('Assigned the editor role.');

        self::assertTrue($user->fresh()->hasRole('editor'));

        Livewire::test(ViewUser::class, ['record' => $user->getKey()])
            ->callAction('removeRole', ['role' => 'editor'])
            ->assertNotified('Removed the editor role.');

        self::assertFalse($user->fresh()->hasRole('editor'));
        self::assertSame(
            [ActivityAction::UserRoleAssigned, ActivityAction::UserRoleRemoved],
            AdminActivity::query()->orderBy('id')->pluck('action')->all(),
        );
    }

    public function test_removing_protected_roles_is_rejected_with_the_service_message(): void
    {
        $user = $this->createPanelUser();
        $otherAdmin = $this->createPanelUser('Other admin', [UserRole::ADMIN->value]);

        Livewire::test(ViewUser::class, ['record' => $user->getKey()])
            ->callAction('removeRole', ['role' => UserRole::COMMON->value])
            ->assertNotified('Cannot remove protected role.');

        Livewire::test(ViewUser::class, ['record' => $otherAdmin->getKey()])
            ->callAction('removeRole', ['role' => UserRole::ADMIN->value])
            ->assertNotified('Cannot remove admin role from an admin.');

        self::assertTrue($user->fresh()->hasRole(UserRole::COMMON->value));
        self::assertTrue($otherAdmin->fresh()->hasRole(UserRole::ADMIN->value));
        self::assertSame(0, AdminActivity::query()->count());
    }

    public function test_role_assigner_without_admin_cannot_grant_admin(): void
    {
        $assigner = $this->createUserWithPermissions(['users.view', 'roles.assign']);
        $target = $this->createPanelUser('Target');
        $this->actingAsPanelUser($assigner);

        Livewire::test(ViewUser::class, ['record' => $target->getKey()])
            ->assertActionVisible('assignRole')
            ->callAction('assignRole', ['role' => UserRole::ADMIN->value])
            ->assertHasActionErrors(['role']);

        self::assertFalse($target->fresh()->hasRole(UserRole::ADMIN->value));
    }

    public function test_support_actions_follow_permissions(): void
    {
        $viewer = $this->createUserWithPermissions(['users.view']);
        $target = $this->createPanelUser('Target');
        $this->actingAsPanelUser($viewer);

        Livewire::test(ViewUser::class, ['record' => $target->getKey()])
            ->assertOk()
            ->assertActionHidden('assignRole')
            ->assertActionHidden('removeRole')
            ->assertActionHidden('markEmailVerified')
            ->assertActionHidden('revokeTokens');

        $this->get("/admin/users/{$target->getKey()}/edit")->assertForbidden();
    }

    public function test_users_are_never_deletable_from_the_panel(): void
    {
        $target = $this->createPanelUser();

        Livewire::test(EditUser::class, ['record' => $target->getKey()])
            ->assertActionDoesNotExist('delete');

        self::assertFalse($this->admin->can('delete', $target));
        self::assertFalse($this->admin->can('deleteAny', User::class));
    }
}
