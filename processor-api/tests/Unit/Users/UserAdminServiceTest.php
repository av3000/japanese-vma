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
use App\Domain\Users\DTOs\UserAccountUpdateDTO;
use App\Domain\Users\DTOs\UserAccountWriteDTO;
use App\Shared\Enums\HttpStatus;
use App\Shared\Results\Result;
use Closure;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

/**
 * Account administration has no API route; the panel shows it only with `users.update`. Every
 * method checks that permission before it reads or writes anything, and turns a database failure
 * into a typed, logged 500 instead of an exception escaping into the panel.
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
            'update' => [static fn (UserAdminService $service, AuthenticatedUser $actor): Result => $service->updateUser(EntityId::generate(), new UserAccountUpdateDTO('Someone', 'someone@example.com'), $actor)],
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

    /**
     * @param Closure(UserAdminService, AuthenticatedUser): Result $call
     */
    #[DataProvider('useCases')]
    public function test_a_repository_failure_is_a_logged_typed_failure(Closure $call): void
    {
        Log::spy();
        $accounts = $this->createMock(UserAccountRepositoryInterface::class);
        $accounts->method(self::anything())->willThrowException(new RuntimeException('connection lost'));
        $recorder = $this->createMock(ActivityRecorderInterface::class);
        $recorder->expects(self::never())->method('record');

        $checker = $this->createMock(ActorPermissionCheckerInterface::class);
        $checker->method('allows')->willReturn(true);

        $result = $call(
            new UserAdminService($accounts, $this->createMock(UserRepositoryInterface::class), $recorder, new UserAdminPolicy($checker)),
            $this->actor(),
        );

        self::assertSame('Users.AccountUpdateFailed', $result->getError()->code);
        self::assertSame(HttpStatus::INTERNAL_SERVER_ERROR, $result->getError()->toHttpStatus());
        self::assertStringNotContainsString('connection lost', (string) $result->getError()->detail);
        Log::shouldHaveReceived('error')->once();
    }

    private function actor(): AuthenticatedUser
    {
        return new AuthenticatedUser(UserId::from(7), EntityId::generate(), UserName::from('Support'), isAdmin: false);
    }
}
