<?php

declare(strict_types=1);

namespace Tests\Unit\Users;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Users\Policies\UserViewPolicy;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use App\Domain\Users\Models\User;
use PHPUnit\Framework\TestCase;

final class UserViewPolicyTest extends TestCase
{
    public function test_profiles_remain_publicly_viewable(): void
    {
        self::assertTrue((new UserViewPolicy)->view(null, $this->createMock(User::class)));
    }

    public function test_it_recognizes_the_authenticated_users_own_profile(): void
    {
        $uuid = EntityId::generate();
        $authenticatedUser = new AuthenticatedUser(
            UserId::from(10),
            $uuid,
            UserName::from('Current User'),
            false,
        );

        self::assertTrue((new UserViewPolicy)->isOwnProfile($authenticatedUser, $uuid));
        self::assertFalse((new UserViewPolicy)->isOwnProfile($authenticatedUser, EntityId::generate()));
        self::assertFalse((new UserViewPolicy)->isOwnProfile(null, $uuid));
    }
}
