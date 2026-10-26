<?php

declare(strict_types=1);

namespace Tests\Unit\Users;

use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Auth\Interfaces\Providers\ActorPermissionCheckerInterface;
use App\Application\Users\Interfaces\Repositories\UserAccountRepositoryInterface;
use App\Application\Users\Interfaces\Repositories\UserRepositoryInterface;
use App\Application\Users\Policies\UserAdminPolicy;
use App\Application\Users\Services\UserAdminService;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use App\Domain\Users\DTOs\UserAccountWriteDTO;
use App\Shared\Enums\HttpStatus;
use App\Shared\Results\Result;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Account administration has no API route; the panel shows it only with `users.update`. Every
 * method checks that permission before it reads or writes anything.
 */
final class UserAdminServiceTest extends TestCase
{
    /**
     * @return array<string, array{Closure(UserAdminService, AuthenticatedUser): Result}>
     */
    public static function useCases(): array
    {
        $account = new UserAccountWriteDTO(name: 'Someone', email: 'someone@example.com', password: 'password123');

        return [
            'create' => [static fn (UserAdminService $service, AuthenticatedUser $actor): Result => $service->createUser($account, $actor)],
            'update' => [static fn (UserAdminService $service, AuthenticatedUser $actor): Result => $service->updateUser(EntityId::generate(), $account, $actor)],
            'mark verified' => [static fn (UserAdminService $service, AuthenticatedUser $actor): Result => $service->markEmailVerified(EntityId::generate(), $actor)],
            'revoke tokens' => [static fn (UserAdminService $service, AuthenticatedUser $actor): Result => $service->revokeAllTokens(EntityId::generate(), $actor)],
        ];
    }

    /**
     * @param Closure(UserAdminService, AuthenticatedUser): Result $call
     */
    #[DataProvider('useCases')]
    public function test_each_use_case_is_refused_without_users_update(Closure $call): void
    {
        $accounts = $this->createMock(UserAccountRepositoryInterface::class);
        $accounts->expects(self::never())->method(self::anything());
        $users = $this->createMock(UserRepositoryInterface::class);
        $users->expects(self::never())->method(self::anything());
        $recorder = $this->createMock(ActivityRecorderInterface::class);
        $recorder->expects(self::never())->method('record');

        $checker = $this->createMock(ActorPermissionCheckerInterface::class);
        $checker->expects(self::once())
            ->method('allows')
            ->with(self::isInstanceOf(AuthenticatedUser::class), 'users.update')
            ->willReturn(false);

        $result = $call(new UserAdminService($accounts, $users, $recorder, new UserAdminPolicy($checker)), $this->actor());

        self::assertTrue($result->isFailure());
        self::assertSame('Users.AdminAccessDenied', $result->getError()->code);
        self::assertSame(HttpStatus::FORBIDDEN, $result->getError()->toHttpStatus());
    }

    private function actor(): AuthenticatedUser
    {
        return new AuthenticatedUser(UserId::from(7), EntityId::generate(), UserName::from('Support'), isAdmin: false);
    }
}
