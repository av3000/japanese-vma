<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Auth\Interfaces\Providers\ActorPermissionCheckerInterface;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use App\Infrastructure\Auth\Providers\PassportCurrentUserProvider;
use App\Infrastructure\Auth\Providers\SpatieActorPermissionChecker;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\TestCase;

/**
 * The application layer asks the same question the panel does: does this person hold the panel
 * permission, with the `admin` role passing every check.
 */
final class SpatieActorPermissionCheckerTest extends TestCase
{
    use InteractsWithAdminPanel;
    use RefreshDatabase;

    private ActorPermissionCheckerInterface $checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAdminPanel();
        $this->checker = app(ActorPermissionCheckerInterface::class);
    }

    public function test_the_container_binds_the_spatie_checker(): void
    {
        self::assertInstanceOf(SpatieActorPermissionChecker::class, $this->checker);
    }

    public function test_a_role_holding_the_permission_is_allowed_and_nothing_else(): void
    {
        $actor = $this->actorFor($this->createUserWithPermissions(['hashtags.manage']));

        self::assertTrue($this->checker->allows($actor, 'hashtags.manage'));
        self::assertFalse($this->checker->allows($actor, 'operations.manage'));
    }

    public function test_the_admin_role_passes_every_permission(): void
    {
        $actor = $this->actorFor($this->createAdmin());

        self::assertTrue($this->checker->allows($actor, 'operations.manage'));
        self::assertTrue($this->checker->allows($actor, 'users.update'));
    }

    public function test_a_plain_member_is_refused(): void
    {
        self::assertFalse($this->checker->allows($this->actorFor($this->createPanelUser()), 'hashtags.manage'));
    }

    public function test_an_unknown_permission_is_refused_rather_than_thrown(): void
    {
        $actor = $this->actorFor($this->createUserWithPermissions(['hashtags.manage']));

        self::assertFalse($this->checker->allows($actor, 'hashtags.destroy'));
    }

    public function test_an_actor_whose_account_is_gone_is_refused(): void
    {
        $actor = new AuthenticatedUser(UserId::from(999_999), EntityId::generate(), UserName::from('Ghost'), isAdmin: true);

        self::assertFalse($this->checker->allows($actor, 'hashtags.manage'));
    }

    private function actorFor(User $user): AuthenticatedUser
    {
        return PassportCurrentUserProvider::authenticatedUserFor($user);
    }
}
