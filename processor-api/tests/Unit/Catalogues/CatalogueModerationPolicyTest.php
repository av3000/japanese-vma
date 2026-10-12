<?php

declare(strict_types=1);

namespace Tests\Unit\Catalogues;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Catalogues\Policies\CatalogueModerationPolicy;
use App\Application\Catalogues\Policies\CataloguePolicy;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\Services\TemplateTypeClassifier;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use PHPUnit\Framework\TestCase;

final class CatalogueModerationPolicyTest extends TestCase
{
    public function test_staff_remove_custom_lists_but_never_known_lists(): void
    {
        $policy = new CatalogueModerationPolicy(new TemplateTypeClassifier);
        $staff = $this->authenticatedUser(20, true);

        self::assertTrue($policy->canRemove($staff, $this->catalogue(10, SavedListType::WORDS)));
        self::assertFalse($policy->canRemove($staff, $this->catalogue(10, SavedListType::KNOWNWORDS)));
    }

    public function test_only_staff_moderate(): void
    {
        $policy = new CatalogueModerationPolicy(new TemplateTypeClassifier);

        self::assertTrue($policy->canModerate($this->authenticatedUser(20, true)));
        self::assertFalse($policy->canModerate($this->authenticatedUser(10)));
        self::assertFalse($policy->canModerate(null));
        self::assertFalse($policy->canRemove($this->authenticatedUser(10), $this->catalogue(10, SavedListType::WORDS)));
    }

    public function test_the_public_policy_gives_staff_no_rights_over_other_users_catalogues(): void
    {
        $staff = $this->authenticatedUser(20, true);
        $catalogue = $this->catalogue(10, SavedListType::WORDS);

        self::assertFalse((new CataloguePolicy)->canDelete($staff, $catalogue));
        self::assertFalse((new CataloguePolicy)->canUpdate($staff, $catalogue));
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

    private function catalogue(int $ownerId, SavedListType $type): Catalogue
    {
        $catalogue = $this->createMock(Catalogue::class);
        $catalogue->method('getOwnerId')->willReturn(UserId::from($ownerId));
        $catalogue->method('getType')->willReturn($type);

        return $catalogue;
    }
}
