<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Sentences\Services;

use App\Application\Activity\ActivityEntries;
use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\JapaneseMaterial\Sentences\Actions\CleanupSentenceDependenciesAction;
use App\Application\JapaneseMaterial\Sentences\Actions\DeriveSentenceRelationshipsAction;
use App\Application\JapaneseMaterial\Sentences\Interfaces\Repositories\SentenceRepositoryInterface;
use App\Application\JapaneseMaterial\Sentences\Policies\SentencePolicy;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Activity\Enums\ActivitySubjectType;
use App\Domain\JapaneseMaterial\Sentences\DTOs\SentenceWriteDTO;
use App\Domain\JapaneseMaterial\Sentences\Errors\SentenceErrors;
use App\Domain\JapaneseMaterial\Sentences\Queries\SentenceQueryCriteria;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SentenceService implements SentenceServiceInterface
{
    public function __construct(
        private readonly SentenceRepositoryInterface $sentenceRepository,
        private readonly SentencePolicy $sentencePolicy,
        private readonly DeriveSentenceRelationshipsAction $deriveRelationships,
        private readonly CleanupSentenceDependenciesAction $cleanupSentenceDependencies,
        private readonly ActivityRecorderInterface $activityRecorder,
    ) {
    }

    public function find(SentenceQueryCriteria $criteria): Result
    {
        return Result::success($this->sentenceRepository->find($criteria));
    }

    public function findByIdentifier(string $identifier, bool $withKanjis = true, bool $withWords = true): Result
    {
        if (EntityId::isValid($identifier)) {
            $sentence = $this->sentenceRepository->findByUuid(EntityId::from($identifier), $withKanjis, $withWords);

            return $sentence
                ? Result::success($sentence)
                : Result::failure(SentenceErrors::notFound($identifier));
        }

        if (ctype_digit($identifier) && (int) $identifier > 0) {
            $sentence = $this->sentenceRepository->findByLegacyId((int) $identifier, $withKanjis, $withWords);

            return $sentence
                ? Result::success($sentence)
                : Result::failure(SentenceErrors::notFound($identifier));
        }

        return Result::failure(SentenceErrors::invalidIdentifier());
    }

    public function create(SentenceWriteDTO $dto, AuthenticatedUser $actor): Result
    {
        try {
            $relationshipIds = $this->deriveRelationships->execute($dto->content);
            $uuid = EntityId::from((string) Str::uuid());

            $sentence = DB::transaction(function () use ($dto, $actor, $relationshipIds, $uuid) {
                $created = $this->sentenceRepository->create($dto, $actor->id, $uuid);
                $this->sentenceRepository->syncKanjis($created->getIdValue(), $relationshipIds->kanjiIds);
                $this->sentenceRepository->syncWords($created->getIdValue(), $relationshipIds->wordIds);

                return $this->sentenceRepository->findByUuid($uuid, withKanjis: true, withWords: true)
                    ?? throw new \RuntimeException('Created sentence could not be reloaded.');
            });

            return Result::success($sentence);
        } catch (\Throwable $exception) {
            Log::error('Sentence creation failed', [
                'user_id' => $actor->id->value(),
                'error' => $exception->getMessage(),
            ]);

            return Result::failure(SentenceErrors::creationFailed());
        }
    }

    public function update(EntityId $uuid, SentenceWriteDTO $dto, AuthenticatedUser $actor): Result
    {
        $sentence = $this->sentenceRepository->findByUuid($uuid);

        if ($sentence === null) {
            return Result::failure(SentenceErrors::notFound($uuid->value()));
        }

        if ($this->sentencePolicy->isImmutable($sentence)) {
            return Result::failure(SentenceErrors::immutableImported($uuid->value()));
        }

        if (! $this->sentencePolicy->canUpdate($actor, $sentence)) {
            return Result::failure(SentenceErrors::accessDenied($uuid->value()));
        }

        try {
            $relationshipIds = $this->deriveRelationships->execute($dto->content);

            $updatedSentence = DB::transaction(function () use ($sentence, $dto, $relationshipIds, $uuid, $actor) {
                // A moderator rewriting someone else's sentence is recorded; authors editing their
                // own are not.
                if ($actor->id->value() !== $sentence->getUserId() && $dto->content !== $sentence->getContent()) {
                    $this->activityRecorder->record(ActivityEntries::by(
                        $actor,
                        ActivityAction::SentenceUpdated,
                        ActivitySubjectType::Sentence,
                        $uuid->value(),
                        mb_substr($dto->content, 0, 120),
                        before: ['content' => $sentence->getContent()],
                        after: ['content' => $dto->content],
                    ));
                }

                $this->sentenceRepository->updateContent($sentence->getIdValue(), $dto);
                $this->sentenceRepository->syncKanjis($sentence->getIdValue(), $relationshipIds->kanjiIds);
                $this->sentenceRepository->syncWords($sentence->getIdValue(), $relationshipIds->wordIds);

                return $this->sentenceRepository->findByUuid($uuid, withKanjis: true, withWords: true)
                    ?? throw new \RuntimeException('Updated sentence could not be reloaded.');
            });

            return Result::success($updatedSentence);
        } catch (\Throwable $exception) {
            Log::error('Sentence update failed', [
                'user_id' => $actor->id->value(),
                'sentence_uuid' => $uuid->value(),
                'error' => $exception->getMessage(),
            ]);

            return Result::failure(SentenceErrors::updateFailed());
        }
    }

    public function delete(EntityId $uuid, AuthenticatedUser $actor): Result
    {
        $sentence = $this->sentenceRepository->findByUuid($uuid);

        if ($sentence === null) {
            return Result::failure(SentenceErrors::notFound($uuid->value()));
        }

        if ($this->sentencePolicy->isImmutable($sentence)) {
            return Result::failure(SentenceErrors::immutableImported($uuid->value()));
        }

        if (! $this->sentencePolicy->canDelete($actor, $sentence)) {
            return Result::failure(SentenceErrors::accessDenied($uuid->value()));
        }

        try {
            DB::transaction(function () use ($sentence, $actor): void {
                if ($actor->id->value() !== $sentence->getUserId()) {
                    $this->activityRecorder->record(ActivityEntries::by(
                        $actor,
                        ActivityAction::SentenceDeleted,
                        ActivitySubjectType::Sentence,
                        $sentence->getUuid()->value(),
                        mb_substr($sentence->getContent(), 0, 120),
                        before: ['content' => $sentence->getContent(), 'author_id' => $sentence->getUserId()],
                    ));
                }

                $this->cleanupSentenceDependencies->execute($sentence->getIdValue());
                $this->sentenceRepository->delete($sentence->getIdValue());
            });

            return Result::success();
        } catch (\Throwable $exception) {
            Log::error('Sentence deletion failed', [
                'user_id' => $actor->id->value(),
                'sentence_uuid' => $uuid->value(),
                'error' => $exception->getMessage(),
            ]);

            return Result::failure(SentenceErrors::deletionFailed());
        }
    }
}
