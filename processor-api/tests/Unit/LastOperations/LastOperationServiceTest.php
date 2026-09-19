<?php

declare(strict_types=1);

namespace Tests\Unit\LastOperations;

use App\Application\LastOperations\Events\AsyncLastOperationStatusUpdated;
use App\Application\LastOperations\Interfaces\Repositories\LastOperationRepositoryInterface;
use App\Application\LastOperations\Services\LastOperationService;
use App\Domain\Shared\Enums\LastOperationStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\LastOperationState;
use App\Infrastructure\Persistence\Repositories\LastOperationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class LastOperationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_operation_passes_entity_type_and_task_type_to_repository(): void
    {
        Event::fake([AsyncLastOperationStatusUpdated::class]);

        $repository = Mockery::mock(LastOperationRepositoryInterface::class);
        $entityId = new EntityId('11111111-1111-4111-8111-111111111111');
        $state = new LastOperationState(['id' => 7, 'processable_id' => $entityId->value(), 'task_type' => 'words_extraction', 'status' => LastOperationStatus::PENDING]);

        $repository
            ->shouldReceive('start')
            ->once()
            ->with($entityId, 'article', 'words_extraction')
            ->andReturn($state);

        $service = new LastOperationService($repository);

        $this->assertSame(
            $state,
            $service->startOperation($entityId, 'article', 'words_extraction')
        );
    }

    public function test_start_operation_broadcasts_pending_once(): void
    {
        Event::fake([AsyncLastOperationStatusUpdated::class]);

        $entityId = new EntityId('11111111-1111-4111-8111-111111111111');
        $state = LastOperationState::create([
            'processable_id' => $entityId->value(),
            'processable_type' => 'article',
            'task_type' => 'words_extraction',
            'status' => LastOperationStatus::PENDING,
        ]);
        $repository = Mockery::mock(LastOperationRepositoryInterface::class);
        $repository->shouldReceive('start')->once()->andReturn($state);

        (new LastOperationService($repository))->startOperation($entityId, 'article', 'words_extraction');

        Event::assertDispatchedTimes(AsyncLastOperationStatusUpdated::class, 1);
        Event::assertDispatched(
            AsyncLastOperationStatusUpdated::class,
            fn (AsyncLastOperationStatusUpdated $event): bool => $event->entityUuid === $entityId->value()
                && $event->snapshot['id'] === $state->id
                && $event->status() === LastOperationStatus::PENDING,
        );
    }

    public function test_update_status_broadcasts_exactly_once_with_the_status_just_written(): void
    {
        Event::fake([AsyncLastOperationStatusUpdated::class]);

        $state = LastOperationState::create([
            'processable_id' => '11111111-1111-4111-8111-111111111111',
            'processable_type' => 'article',
            'task_type' => 'words_extraction',
            'status' => LastOperationStatus::PENDING,
            'metadata' => ['attempts' => 1],
        ]);

        $service = new LastOperationService(new LastOperationRepository);

        $service->updateStatus($state->id, LastOperationStatus::PROCESSING);
        $service->updateStatus($state->id, LastOperationStatus::COMPLETED, ['word_count' => 2]);

        Event::assertDispatchedTimes(AsyncLastOperationStatusUpdated::class, 2);

        $statuses = [];
        Event::assertDispatched(AsyncLastOperationStatusUpdated::class, function (AsyncLastOperationStatusUpdated $event) use (&$statuses): bool {
            $statuses[] = $event->status();

            return true;
        });
        $this->assertSame([LastOperationStatus::PROCESSING, LastOperationStatus::COMPLETED], $statuses);

        Event::assertDispatched(
            AsyncLastOperationStatusUpdated::class,
            fn (AsyncLastOperationStatusUpdated $event): bool => $event->status() === LastOperationStatus::COMPLETED
                && $event->snapshot['metadata'] === ['attempts' => 1, 'word_count' => 2]
                && $event->snapshot['updated_at'] !== null,
        );
    }

    public function test_update_status_broadcasts_the_refreshed_operation_state(): void
    {
        Event::fake([AsyncLastOperationStatusUpdated::class]);

        $state = LastOperationState::create([
            'processable_id' => '11111111-1111-4111-8111-111111111111',
            'processable_type' => 'article',
            'task_type' => 'words_extraction',
            'status' => LastOperationStatus::PENDING,
        ]);
        $repository = Mockery::mock(LastOperationRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with($state->id)->andReturn($state);
        $repository->shouldReceive('update')->once()->with($state, LastOperationStatus::COMPLETED, []);

        (new LastOperationService($repository))->updateStatus($state->id, LastOperationStatus::COMPLETED);

        Event::assertDispatched(
            AsyncLastOperationStatusUpdated::class,
            fn (AsyncLastOperationStatusUpdated $event): bool => $event->snapshot['id'] === $state->id
                && $event->entityUuid === $state->processable_id,
        );
    }
}
