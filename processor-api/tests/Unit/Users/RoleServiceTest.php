<?php

declare(strict_types=1);

namespace Tests\Unit\Users;

use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Users\Interfaces\Repositories\RoleRepositoryInterface;
use App\Application\Users\Interfaces\Repositories\UserRepositoryInterface;
use App\Application\Users\Services\RoleService;
use App\Domain\Activity\DTOs\ActivityEntry;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use App\Domain\Users\Models\User;
use App\Shared\Enums\HttpStatus;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

/**
 * `roles.assign` lets a support role hand out roles, but granting `admin` makes someone a
 * superuser, so only an admin may do it. The panel hides the option; this service is where the
 * rule holds for every caller.
 */
final class RoleServiceTest extends TestCase
{
    public function test_a_non_admin_cannot_grant_the_admin_role(): void
    {
        $roles = $this->roles();
        $roles->expects(self::never())->method('assignRole');
        $recorder = $this->createMock(ActivityRecorderInterface::class);
        $recorder->expects(self::never())->method('record');

        $result = (new RoleService($this->users(), $roles, $recorder))
            ->assignRole(EntityId::generate(), 'admin', $this->actor(isAdmin: false));

        self::assertSame('Roles.AdminRoleRequiresAdmin', $result->getError()->code);
        self::assertSame(HttpStatus::FORBIDDEN, $result->getError()->toHttpStatus());
    }

    public function test_an_admin_can_grant_the_admin_role(): void
    {
        $roles = $this->roles();
        $roles->expects(self::once())->method('assignRole')->willReturn(true);

        $result = (new RoleService($this->users(), $roles, $this->recorderExpectingActor()))
            ->assignRole(EntityId::generate(), 'admin', $this->actor(isAdmin: true));

        self::assertTrue($result->isSuccess());
    }

    public function test_a_non_admin_can_still_grant_other_roles(): void
    {
        $roles = $this->roles();
        $roles->expects(self::once())->method('assignRole')->willReturn(true);

        $result = (new RoleService($this->users(), $roles, $this->recorderExpectingActor()))
            ->assignRole(EntityId::generate(), 'reviewer', $this->actor(isAdmin: false));

        self::assertTrue($result->isSuccess());
    }

    private function users(): UserRepositoryInterface
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(UserId::from(42));
        $user->method('getUuid')->willReturn(EntityId::generate());
        $user->method('getName')->willReturn(UserName::from('Learner'));

        $users = $this->createMock(UserRepositoryInterface::class);
        $users->method('findByUuid')->willReturn($user);

        return $users;
    }

    private function roles(): RoleRepositoryInterface&MockObject
    {
        $roles = $this->createMock(RoleRepositoryInterface::class);
        $roles->method('exists')->willReturn(true);
        $roles->method('userHasRole')->willReturn(false);

        return $roles;
    }

    private function recorderExpectingActor(): ActivityRecorderInterface
    {
        $recorder = $this->createMock(ActivityRecorderInterface::class);
        $recorder->expects(self::once())
            ->method('record')
            ->with(self::callback(static fn (ActivityEntry $entry): bool => $entry->actorId === 7));

        return $recorder;
    }

    private function actor(bool $isAdmin): AuthenticatedUser
    {
        return new AuthenticatedUser(UserId::from(7), EntityId::generate(), UserName::from('Support'), $isAdmin);
    }
}
