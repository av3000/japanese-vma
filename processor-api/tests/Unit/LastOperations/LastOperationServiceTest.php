<?php

declare(strict_types=1);

namespace Tests\Unit\LastOperations;

use App\Application\LastOperations\Interfaces\Repositories\LastOperationRepositoryInterface;
use App\Application\LastOperations\Services\LastOperationService;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\LastOperationState;
use Mockery;
use Tests\TestCase;

class LastOperationServiceTest extends TestCase
{
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
}
