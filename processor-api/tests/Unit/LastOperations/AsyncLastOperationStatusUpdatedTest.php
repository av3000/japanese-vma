<?php

declare(strict_types=1);

namespace Tests\Unit\LastOperations;

use App\Application\LastOperations\Events\AsyncLastOperationStatusUpdated;
use App\Domain\Shared\Enums\LastOperationStatus;
use App\Http\v1\LastOperations\Resources\ProcessingStatusResource;
use App\Infrastructure\Persistence\Models\LastOperationState;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use ReflectionClass;
use ReflectionNamedType;
use Tests\TestCase;

class AsyncLastOperationStatusUpdatedTest extends TestCase
{
    private const UUID = '11111111-1111-4111-8111-111111111111';

    public function test_broadcasts_immediately_on_the_private_article_channel_with_the_stable_alias(): void
    {
        $event = new AsyncLastOperationStatusUpdated(self::UUID, $this->snapshot());

        $this->assertInstanceOf(ShouldBroadcastNow::class, $event);

        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-last_operations.'.self::UUID, (string) $channels[0]);

        $this->assertSame('OperationStatusUpdated', $event->broadcastAs());
    }

    public function test_broadcast_payload_equals_the_processing_status_resource_for_the_snapshot(): void
    {
        $snapshot = $this->snapshot();
        $event = new AsyncLastOperationStatusUpdated(self::UUID, $snapshot);

        $this->assertEquals(
            (new ProcessingStatusResource($snapshot))->resolve(),
            $event->broadcastWith(),
        );
        $this->assertSame(LastOperationStatus::PROCESSING, $event->status());
    }

    public function test_from_state_snapshots_the_model_at_construction_time(): void
    {
        Carbon::setTestNow('2026-09-18 12:00:00');

        $state = new LastOperationState([
            'id' => 42,
            'processable_id' => self::UUID,
            'task_type' => 'kanji_extraction',
            'status' => LastOperationStatus::PROCESSING,
            'metadata' => ['attempts' => 2],
        ]);
        $state->created_at = Carbon::now()->subMinute();
        $state->updated_at = Carbon::now();

        $event = AsyncLastOperationStatusUpdated::fromState($state);

        // Mutating the model afterwards must not leak into the event.
        $state->status = LastOperationStatus::COMPLETED;
        $state->metadata = ['attempts' => 3];

        $this->assertSame(self::UUID, $event->entityUuid);
        $this->assertSame(42, $event->snapshot['id']);
        $this->assertSame('kanji_extraction', $event->snapshot['type']);
        $this->assertSame(LastOperationStatus::PROCESSING, $event->snapshot['status']);
        $this->assertSame(['attempts' => 2], $event->snapshot['metadata']);
        $this->assertSame('2026-09-18T11:59:00+00:00', $event->snapshot['created_at']);
        $this->assertSame('2026-09-18T12:00:00+00:00', $event->snapshot['updated_at']);

        Carbon::setTestNow();
    }

    public function test_event_no_longer_carries_an_eloquent_model_or_serializes_models(): void
    {
        $reflection = new ReflectionClass(AsyncLastOperationStatusUpdated::class);

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
     * @return array{id: int, type: string, status: LastOperationStatus, metadata: array<string, mixed>, created_at: ?string, updated_at: ?string}
     */
    private function snapshot(): array
    {
        return [
            'id' => 7,
            'type' => 'words_extraction',
            'status' => LastOperationStatus::PROCESSING,
            'metadata' => ['attempts' => 1],
            'created_at' => '2026-09-18T11:59:00+00:00',
            'updated_at' => '2026-09-18T12:00:00+00:00',
        ];
    }
}
