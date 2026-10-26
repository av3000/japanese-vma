<?php

declare(strict_types=1);

namespace App\Application\Users\Services;

use App\Application\Activity\ActivityEntries;
use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Users\Interfaces\Repositories\UserAccountRepositoryInterface;
use App\Application\Users\Interfaces\Repositories\UserRepositoryInterface;
use App\Application\Users\Policies\UserAdminPolicy;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Activity\Enums\ActivitySubjectType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Users\DTOs\UserAccountDTO;
use App\Domain\Users\DTOs\UserAccountWriteDTO;
use App\Domain\Users\Errors\UserErrors;
use App\Shared\Results\Result;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class UserAdminService implements UserAdminServiceInterface
{
    public function __construct(
        private readonly UserAccountRepositoryInterface $accounts,
        private readonly UserRepositoryInterface $users,
        private readonly ActivityRecorderInterface $activityRecorder,
        private readonly UserAdminPolicy $policy,
    ) {
    }

    public function createUser(UserAccountWriteDTO $account, AuthenticatedUser $actor): Result
    {
        if (! $this->policy->canCreate($actor)) {
            return Result::failure(UserErrors::adminAccessDenied());
        }

        if ($this->accounts->emailBelongsToAnotherAccount($account->email)) {
            return Result::failure(UserErrors::emailAlreadyExists($account->email));
        }

        $created = DB::transaction(function () use ($account, $actor): UserAccountDTO {
            $created = $this->accounts->create($account);

            $this->activityRecorder->record(ActivityEntries::by(
                $actor,
                ActivityAction::UserCreated,
                ActivitySubjectType::User,
                $created->uuid->value(),
                $created->name,
                after: $created->activitySnapshot(),
            ));

            return $created;
        });

        return Result::success($created);
    }

    public function updateUser(EntityId $uuid, UserAccountWriteDTO $account, AuthenticatedUser $actor): Result
    {
        if (! $this->policy->canUpdate($actor)) {
            return Result::failure(UserErrors::adminAccessDenied());
        }

        $current = $this->accounts->find($uuid);

        if ($current === null) {
            return Result::failure(UserErrors::notFound($uuid->value()));
        }

        if ($this->accounts->emailBelongsToAnotherAccount($account->email, $uuid)) {
            return Result::failure(UserErrors::emailAlreadyExists($account->email));
        }

        $emailChanged = mb_strtolower($current->email) !== mb_strtolower($account->email);

        $updated = DB::transaction(function () use ($uuid, $account, $actor, $current, $emailChanged): UserAccountDTO {
            $updated = $this->accounts->update($uuid, $account, clearEmailVerification: $emailChanged);
            $passwordChanged = $account->password !== null;

            if ($current->activitySnapshot() !== $updated->activitySnapshot() || $passwordChanged) {
                $this->activityRecorder->record(ActivityEntries::by(
                    $actor,
                    ActivityAction::UserUpdated,
                    ActivitySubjectType::User,
                    $updated->uuid->value(),
                    $updated->name,
                    before: $current->activitySnapshot(),
                    after: $updated->activitySnapshot(),
                    context: $passwordChanged ? ['password_changed' => true] : [],
                ));
            }

            return $updated;
        });

        return Result::success($updated);
    }

    public function markEmailVerified(EntityId $uuid, AuthenticatedUser $actor): Result
    {
        if (! $this->policy->canUpdate($actor)) {
            return Result::failure(UserErrors::adminAccessDenied());
        }

        $current = $this->accounts->find($uuid);

        if ($current === null) {
            return Result::failure(UserErrors::notFound($uuid->value()));
        }

        if ($current->emailVerifiedAt !== null) {
            return Result::success($current);
        }

        $verified = DB::transaction(function () use ($uuid, $actor, $current): UserAccountDTO {
            $verified = $this->accounts->markEmailVerified($uuid, CarbonImmutable::now());

            $this->activityRecorder->record(ActivityEntries::by(
                $actor,
                ActivityAction::UserEmailVerified,
                ActivitySubjectType::User,
                $verified->uuid->value(),
                $verified->name,
                before: $current->activitySnapshot(),
                after: $verified->activitySnapshot(),
            ));

            return $verified;
        });

        return Result::success($verified);
    }

    public function revokeAllTokens(EntityId $uuid, AuthenticatedUser $actor): Result
    {
        if (! $this->policy->canUpdate($actor)) {
            return Result::failure(UserErrors::adminAccessDenied());
        }

        $current = $this->accounts->find($uuid);

        if ($current === null) {
            return Result::failure(UserErrors::notFound($uuid->value()));
        }

        DB::transaction(function () use ($current, $actor): void {
            $this->users->revokeAllTokens($current->id);

            $this->activityRecorder->record(ActivityEntries::by(
                $actor,
                ActivityAction::UserTokensRevoked,
                ActivitySubjectType::User,
                $current->uuid->value(),
                $current->name,
            ));
        });

        return Result::success();
    }
}
