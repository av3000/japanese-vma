<?php

declare(strict_types=1);

namespace Tests\Unit\Processing;

use App\Application\Processing\Events\ProcessingStatusUpdated;
use App\Domain\Processing\DTOs\ProcessingStateDTO;
use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingStatus;
use App\Http\v1\Processing\Resources\ProcessingStatusResource;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;
use ReflectionClass;
use ReflectionNamedType;
use Tests\TestCase;

class ProcessingStatusUpdatedTest extends TestCase
{
    private const UUID = '11111111-1111-4111-8111-111111111111';

    public function test_broadcasts_immediately_on_the_private_article_channel_with_the_stable_alias(): void
    {
        $event = ProcessingStatusUpdated::fromDto($this->dto());

        $this->assertInstanceOf(ShouldBroadcastNow::class, $event);

        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-processing_states.'.self::UUID, (string) $channels[0]);

        $this->assertSame('ProcessingStatusUpdated', $event->broadcastAs());
    }

    public function test_broadcast_payload_is_byte_for_byte_the_rest_processing_status(): void
    {
        $dto = $this->dto();
        $event = ProcessingStatusUpdated::fromDto($dto);

        $this->assertSame(
            json_encode((new ProcessingStatusResource($dto))->resolve(), JSON_THROW_ON_ERROR),
            json_encode($event->broadcastWith(), JSON_THROW_ON_ERROR),
        );
        $this->assertSame(ProcessingStatus::PROCESSING, $event->status());
        $this->assertSame('{}', json_encode($event->broadcastWith()['metadata']), 'Empty metadata is an object, never a list.');
    }

    public function test_from_dto_snapshots_the_row_as_a_plain_payload(): void
    {
        $event = ProcessingStatusUpdated::fromDto($this->dto(metadata: ['kanji_count' => 3]));

        $this->assertSame(self::UUID, $event->entityUuid);
        $this->assertSame(42, $event->snapshot['id']);
        $this->assertSame(self::UUID, $event->snapshot['entity_id']);
        $this->assertSame(2, $event->snapshot['attempt']);
        $this->assertSame('article_content_processing', $event->snapshot['type']);
        $this->assertSame('processing', $event->snapshot['status']);
        $this->assertSame(['kanji_count' => 3], (array) $event->snapshot['metadata']);
        $this->assertSame('2026-09-18T11:59:00+00:00', $event->snapshot['created_at']);
        $this->assertSame('2026-09-18T12:00:00+00:00', $event->snapshot['updated_at']);
    }

    public function test_event_no_longer_carries_an_eloquent_model_or_serializes_models(): void
    {
        $reflection = new ReflectionClass(ProcessingStatusUpdated::class);

        $this->assertNotContains(SerializesModels::class, $reflection->getTraitNames());

        // Trait properties (InteractsWithSockets::$socket) are reported as declared here, so check
        // types rather than declaring class: nothing may hold an Eloquent model.
        foreach ($reflection->getProperties() as $property) {
            $type = $property->getType();

            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                $this->assertFalse(
                    is_a($type->getName(), Model::class, true),
                    "Property {$property->getName()} must not hold an Eloquent model.",
                );
            }
        }

        $this->assertSame('string', (string) $reflection->getProperty('entityUuid')->getType());
        $this->assertSame('array', (string) $reflection->getProperty('snapshot')->getType());
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function dto(array $metadata = []): ProcessingStateDTO
    {
        return new ProcessingStateDTO(
            id: 42,
            entityType: ProcessingEntityType::Article,
            entityId: self::UUID,
            taskType: 'article_content_processing',
            status: ProcessingStatus::PROCESSING,
            sequence: 7,
            attempt: 2,
            maxAttempts: 3,
            contentVersion: 4,
            metadata: $metadata,
            errorCode: null,
            errorMessage: null,
            startedAt: new \DateTimeImmutable('2026-09-18 12:00:00+00:00'),
            finishedAt: null,
            createdAt: new \DateTimeImmutable('2026-09-18 11:59:00+00:00'),
            updatedAt: new \DateTimeImmutable('2026-09-18 12:00:00+00:00'),
        );
    }
}
