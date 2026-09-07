<?php

declare(strict_types=1);

namespace Tests\Unit\LastOperations;

use App\Application\LastOperations\Events\AsyncLastOperationStatusUpdated;
use App\Application\LastOperations\Interfaces\Repositories\LastOperationRepositoryInterface;
use App\Application\LastOperations\Services\LastOperationService;
use App\Domain\Shared\Enums\LastOperationStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\LastOperationState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class LastOperationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_operation_passes_entity_type_and_task_type_to_repository(): void
    {
        $repository = Mockery::mock(LastOperationRepositoryInterface::class);
        $entityId = new EntityId('11111111-1111-4111-8111-111111111111');
        $state = new LastOperationState;

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
            fn (AsyncLastOperationStatusUpdated $event): bool => $event->operationState->is($state),
        );
    }
}
