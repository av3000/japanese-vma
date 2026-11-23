<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Activity;

use App\Domain\Activity\DTOs\ActivityEntry;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Activity\Enums\ActivitySubjectType;
use PHPUnit\Framework\TestCase;

class ActivityEntryTest extends TestCase
{
    public function test_attributes_outside_the_subject_allowlist_are_dropped(): void
    {
        $entry = new ActivityEntry(
            actorId: 1,
            actorName: 'Admin',
            action: ActivityAction::UserUpdated,
            subjectType: ActivitySubjectType::User,
            subjectId: 'uuid',
            before: ['name' => 'Old', 'password' => 'hash-1', 'remember_token' => 'x'],
            after: ['name' => 'New', 'password' => 'hash-2'],
        );

        self::assertSame(
            ['before' => ['name' => 'Old'], 'after' => ['name' => 'New']],
            $entry->loggableChanges(),
        );
    }

    public function test_unchanged_attributes_are_omitted_when_both_sides_exist(): void
    {
        $entry = new ActivityEntry(
            actorId: null,
            actorName: 'system',
            action: ActivityAction::RoleUpdated,
            subjectType: ActivitySubjectType::Role,
            subjectId: 'editor',
            before: ['name' => 'editor', 'guard_name' => 'api', 'permissions' => ['a']],
            after: ['name' => 'editor', 'guard_name' => 'api', 'permissions' => ['a', 'b']],
        );

        self::assertSame(
            ['before' => ['permissions' => ['a']], 'after' => ['permissions' => ['a', 'b']]],
            $entry->loggableChanges(),
        );
    }

    public function test_one_sided_snapshots_are_kept_whole(): void
    {
        $entry = new ActivityEntry(
            actorId: null,
            actorName: 'system',
            action: ActivityAction::RoleDeleted,
            subjectType: ActivitySubjectType::Role,
            subjectId: 'editor',
            before: ['name' => 'editor', 'guard_name' => 'api', 'permissions' => []],
        );

        self::assertSame(
            ['before' => ['name' => 'editor', 'guard_name' => 'api', 'permissions' => []], 'after' => []],
            $entry->loggableChanges(),
        );
    }
}
