<?php

declare(strict_types=1);

namespace Tests\Unit\Catalogues;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Catalogues\Policies\CataloguePolicy;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use PHPUnit\Framework\TestCase;

final class CataloguePolicyTest extends TestCase
{
    public function test_public_catalogues_are_visible_to_anonymous_viewers(): void
    {
        self::assertTrue((new CataloguePolicy)->canView(null, $this->catalogue(PublicityStatus::PUBLIC, 10)));
    }

    public function test_private_catalogues_are_visible_to_the_owner_and_admin(): void
    {
        $policy = new CataloguePolicy;
        $catalogue = $this->catalogue(PublicityStatus::PRIVATE, 10);

        self::assertTrue($policy->canView($this->authenticatedUser(10), $catalogue));
        self::assertTrue($policy->canView($this->authenticatedUser(20, true), $catalogue));
        self::assertFalse($policy->canView($this->authenticatedUser(20), $catalogue));
    }

    public function test_only_the_owner_can_update_and_delete(): void
    {
        $policy = new CataloguePolicy;
        $catalogue = $this->catalogue(PublicityStatus::PRIVATE, 10);

        self::assertTrue($policy->canUpdate($this->authenticatedUser(10), $catalogue));
        self::assertFalse($policy->canUpdate($this->authenticatedUser(20, true), $catalogue));
        self::assertFalse($policy->canDelete($this->authenticatedUser(20), $catalogue));
    }

    private function authenticatedUser(int $id, bool $isAdmin = false): AuthenticatedUser
    {
        return new AuthenticatedUser(
            UserId::from($id),
            EntityId::generate(),
            UserName::from('Test User'),
            $isAdmin,
        );
    }

    private function catalogue(PublicityStatus $publicity, int $ownerId): Catalogue
    {
        $catalogue = $this->createMock(Catalogue::class);
        $catalogue->method('getPublicity')->willReturn($publicity);
        $catalogue->method('getOwnerId')->willReturn(UserId::from($ownerId));

        return $catalogue;
    }
}
