<?php

declare(strict_types=1);

namespace Tests\Unit\JapaneseMaterial\Sentences;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\JapaneseMaterial\Sentences\Policies\SentencePolicy;
use App\Domain\JapaneseMaterial\Sentences\Models\Sentence;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use PHPUnit\Framework\TestCase;

class SentencePolicyTest extends TestCase
{
    public function test_owner_can_mutate_user_authored_sentence(): void
    {
        self::assertTrue($this->policy()->canUpdate($this->actor(10), $this->sentence(10)));
        self::assertTrue($this->policy()->canDelete($this->actor(10), $this->sentence(10)));
    }

    public function test_non_owner_cannot_mutate_user_authored_sentence(): void
    {
        self::assertFalse($this->policy()->canUpdate($this->actor(11), $this->sentence(10)));
        self::assertFalse($this->policy()->canDelete($this->actor(11), $this->sentence(10)));
    }

    public function test_admin_can_mutate_user_authored_sentence(): void
    {
        self::assertTrue($this->policy()->canUpdate($this->actor(11, isAdmin: true), $this->sentence(10)));
        self::assertTrue($this->policy()->canDelete($this->actor(11, isAdmin: true), $this->sentence(10)));
    }

    public function test_guest_cannot_mutate_user_authored_sentence(): void
    {
        self::assertFalse($this->policy()->canUpdate(null, $this->sentence(10)));
        self::assertFalse($this->policy()->canDelete(null, $this->sentence(10)));
    }

    public function test_imported_sentence_is_immutable_for_admin(): void
    {
        self::assertTrue($this->policy()->isImmutable($this->sentence(null)));
        self::assertFalse($this->policy()->canUpdate($this->actor(11, isAdmin: true), $this->sentence(null)));
        self::assertFalse($this->policy()->canDelete($this->actor(11, isAdmin: true), $this->sentence(null)));
    }

    public function test_user_authored_sentence_is_not_immutable(): void
    {
        self::assertFalse($this->policy()->isImmutable($this->sentence(10)));
    }

    private function policy(): SentencePolicy
    {
        return new SentencePolicy;
    }

    private function actor(int $id, bool $isAdmin = false): AuthenticatedUser
    {
        return new AuthenticatedUser(
            id: new UserId($id),
            uuid: new EntityId('8f077119-6e82-4c4e-a68e-2cb7f657ee1a'),
            name: new UserName('Actor'),
            isAdmin: $isAdmin,
        );
    }

    private function sentence(?int $ownerId): Sentence
    {
        return new Sentence(
            id: 1,
            uuid: new EntityId('6bb2dde9-c61b-41b0-a7c7-c46e6b9a0332'),
            userId: $ownerId,
            tatoebaEntry: $ownerId === null ? '1001' : null,
            content: '私は学生です。',
        );
    }
}
