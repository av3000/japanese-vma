<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Infrastructure\Auth\Providers\PassportCurrentUserProvider;
use App\Infrastructure\Persistence\Models\User as PersistenceUser;
use App\Infrastructure\Persistence\Repositories\UserMapper;
use Illuminate\Http\Request;
use Laravel\Passport\Token;
use PHPUnit\Framework\TestCase;
use Spatie\Permission\Models\Role;

final class PassportCurrentUserProviderTest extends TestCase
{
    public function test_it_returns_null_context_for_an_anonymous_request(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('user')
            ->with('api')
            ->willReturn(null);

        $adapter = new PassportCurrentUserProvider($request, new UserMapper);

        self::assertNull($adapter->currentAuthenticatedUser());
        self::assertNull($adapter->currentUser());
        self::assertNull($adapter->currentAccessTokenId());
    }

    public function test_it_builds_and_caches_an_authenticated_user_without_mapping_the_full_user(): void
    {
        $user = $this->persistenceUser();
        $user->setRelation('roles', collect([$this->role('admin')]));

        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('user')
            ->with('api')
            ->willReturn($user);

        $adapter = new PassportCurrentUserProvider($request, new UserMapper);

        $firstAuthenticatedUser = $adapter->currentAuthenticatedUser();
        $secondAuthenticatedUser = $adapter->currentAuthenticatedUser();

        self::assertSame($firstAuthenticatedUser, $secondAuthenticatedUser);
        self::assertSame(123, $firstAuthenticatedUser?->id->value());
        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $firstAuthenticatedUser?->uuid->value());
        self::assertSame('Current User', $firstAuthenticatedUser?->name->value());
        self::assertTrue($firstAuthenticatedUser?->isAdmin);
    }

    public function test_it_maps_and_caches_the_domain_user_only_when_requested(): void
    {
        $user = $this->persistenceUser();
        $user->setRelation('roles', collect([$this->role('common')]));

        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('user')
            ->with('api')
            ->willReturn($user);

        $adapter = new PassportCurrentUserProvider($request, new UserMapper);

        $firstUser = $adapter->currentUser();
        $secondUser = $adapter->currentUser();

        self::assertSame($firstUser, $secondUser);
        self::assertSame(123, $firstUser?->getId()->value());
        self::assertSame('current@example.com', $firstUser?->getEmail()->value());
    }

    public function test_it_returns_the_current_passport_access_token_id(): void
    {
        $token = new Token;
        $token->id = 'passport-token-id';

        $user = $this->createMock(PersistenceUser::class);
        $user->expects($this->once())->method('token')->willReturn($token);

        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('user')
            ->with('api')
            ->willReturn($user);

        $adapter = new PassportCurrentUserProvider($request, new UserMapper);

        self::assertSame('passport-token-id', $adapter->currentAccessTokenId());
    }

    private function persistenceUser(): PersistenceUser
    {
        $user = new PersistenceUser;
        $user->setRawAttributes([
            'id' => 123,
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'name' => 'Current User',
            'email' => 'current@example.com',
            'created_at' => new \DateTime,
        ]);

        return $user;
    }

    private function role(string $name): Role
    {
        /** @var Role $role */
        $role = (new \ReflectionClass(Role::class))->newInstanceWithoutConstructor();
        $role->setRawAttributes([
            'name' => $name,
            'guard_name' => 'api',
        ]);

        return $role;
    }
}
