<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\Viewer;
use PHPUnit\Framework\TestCase;

final class ViewerTest extends TestCase
{
    public function test_it_carries_typed_authenticated_identity_and_ip_address(): void
    {
        $viewer = new Viewer(UserId::from(123), '127.0.0.1');

        self::assertTrue($viewer->isAuthenticated());
        self::assertSame(123, $viewer->userId()?->value());
        self::assertSame('127.0.0.1', $viewer->ipAddress());
    }

    public function test_it_represents_an_anonymous_viewer_without_framework_access(): void
    {
        $viewer = new Viewer(null, '127.0.0.1');

        self::assertFalse($viewer->isAuthenticated());
        self::assertNull($viewer->userId());
    }
}
